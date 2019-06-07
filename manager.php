<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

error_reporting(E_ALL);
set_time_limit(100);
//ini_set('memory_limit', '-1');

include_once("common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

class Mode extends ModeTemplate
{
   	const MAIN = null;
   	const FORCE_CRAWLER = "f";
   	const RESTART_CRAWLER = "r";
   	const STOP_CRAWLER = "s";
}

Mode::PrintUsage(array(
	Mode::MAIN=>" - main mode meaning triggering crawlers along their schedule",
	Mode::FORCE_CRAWLER=>"=<crawler_id> - force <crawler_id> to run ignoring its run_time_span and crawler process quota",
	Mode::RESTART_CRAWLER=>"=<crawler_id> - restart <crawler_id> to run ignoring its run_time_span and crawler process quota",
	Mode::STOP_CRAWLER=>"=<crawler_id> - stop <crawler_id>",
	)
);

include_once("common/logger.php");
include_once("constants.php");
Logger::Init(Constants::LogDirectory."/_manager", 1);
Logger::$CopyToConsole = true;

include_once("common/db.php");
include_once("common/misc.php");

const RESTART_DELAY_FOR_BROKEN_CRAWLER_IN_SECS = 600;

function SendMessage($m, $crawler_id=null, $error=true, $admin_emails=null)
{	
	if($error) Logger::Error_($m);
	else Logger::Write($m);
	if(!$admin_emails)
	{
		if($crawler_id) $admin_emails = Db::GetSingleValue("SELECT admin_emails FROM crawlers WHERE id='$crawler_id'");
	}
	if(!is_array($admin_emails)) $admin_emails = preg_split("@(\s|,)@is", $admin_emails);
	if(!$admin_emails) $admin_emails[] = Constants::AdminEmail;
	$subject = "Crawler Manager:";
	if($crawler_id) $subject .= " $crawler_id";
	if($error) $subject .= " error";
	$subject .= " notification";
	foreach($admin_emails as $ae)
	{
		$ae = trim($ae);
		if(!$ae) continue;
		mail($ae, $subject, $m) or Logger::Error("Could not email to $ae");
	}
}

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");
Logger::Write2("MODE: ".Mode::Name());

////////////////////////////////////////////////////////////
//Setting MODE
////////////////////////////////////////////////////////////

switch(Mode::This())
{
	case Mode::FORCE_CRAWLER:
		Logger::$CopyToConsole = true;
		$crawler_id =  Shell::GetCommandOpt("f");
		if(!Db::GetSingleValue("SELECT id FROM crawlers WHERE state<>'disabled' AND id='$crawler_id'")) Logger::Error_("$crawler_id does not exist or it is disabled.");
		Db::Query("UPDATE crawlers SET command='force' WHERE id='$crawler_id'");
		
		break;
	case Mode::RESTART_CRAWLER:
		Logger::$CopyToConsole = true;
		$crawler_id =  Shell::GetCommandOpt("r");
		if(!Db::GetSingleValue("SELECT id FROM crawlers WHERE state<>'disabled' AND id='$crawler_id'")) Logger::Error_("$crawler_id does not exist or it is disabled.");
		Db::Query("UPDATE crawlers SET command='restart' WHERE id='$crawler_id'");
		
		break;	
	case Mode::STOP_CRAWLER:
		Logger::$CopyToConsole = true;
		$crawler_id =  Shell::GetCommandOpt("s");
		if(!Db::GetSingleValue("SELECT id FROM crawlers WHERE id='$crawler_id'")) Logger::Error_("$crawler_id does not exist.");
		Db::Query("UPDATE crawlers SET command='stop' WHERE id='$crawler_id'");
		
		break;
	case Mode::MAIN:	
			
		break;			
	default:		
		
		Logger::Quit("Unknown mode: ".Mode::This());	
			
		break;
}

$crawler_process_number = 0;
////////////////////////////////////////////////////////////
//Killing disabled crawler processes
////////////////////////////////////////////////////////////
$result = Db::GetArray("SELECT (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(_last_start_time)) AS duration, run_time_span, id AS crawler_id, _last_start_time, _last_process_id, _last_log, admin_emails, _last_session_state FROM crawlers WHERE _last_session_state='started' AND state='disabled'");	
foreach($result as $r)
{
	$crawler_id = $r['crawler_id'];	
		
	$command = "kill -9 ".$r['_last_process_id'];
	Logger::Warning_("Killing $crawler_id as disabled:\n$command\n>".shell_exec($command));
	sleep(2);
	if(!Shell::IsProcessAlive($r['_last_process_id'])) Db::Query("UPDATE crawlers SET _last_session_state='killed', _last_end_time=NOW() WHERE id='".$r['crawler_id']."'");	
	else SendMessage("Could not kill $crawler_id", $crawler_id);	
}

////////////////////////////////////////////////////////////
//Process crawler commands
////////////////////////////////////////////////////////////
$result = Db::GetArray("SELECT (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(_last_start_time)) AS duration, run_time_span, id AS crawler_id, _last_start_time, _last_process_id, _last_log, admin_emails, _last_session_state, command FROM crawlers WHERE state<>'disabled' AND command<>''");	
foreach($result as $r)
{
	$crawler_id = $r['crawler_id'];	
	
	switch($r['command'])
	{
		case 'restart':	
					
			if($r["_last_session_state"] != "started" or !Shell::IsProcessAlive($r['_last_process_id'])) 
			{
				Db::Query("UPDATE crawlers SET command='', _next_start_time=ADDDATE(NOW(), INTERVAL -1 SECOND) WHERE id='".$r['crawler_id']."'");
				break;
			}
			$command = "kill -9 ".$r['_last_process_id'];
			Logger::Warning_("Killing $crawler_id as marked ".$r['command'].":\n$command\n>".shell_exec($command));
			sleep(2);
			if(!Shell::IsProcessAlive($r['_last_process_id'])) Db::Query("UPDATE crawlers SET command='force' WHERE id='".$r['crawler_id']."'");
			else SendMessage("Could not kill $crawler_id", $crawler_id);
			
			break;			
		case 'stop':		
		
			if($r["_last_session_state"] != "started" or !Shell::IsProcessAlive($r['_last_process_id'])) break;
			$command = "kill -9 ".$r['_last_process_id'];
			Logger::Warning_("Killing $crawler_id as marked ".$r['command'].":\n$command\n>".shell_exec($command));
			sleep(2);
			if(!Shell::IsProcessAlive($r['_last_process_id'])) Db::Query("UPDATE crawlers SET _last_session_state='killed', _last_end_time=NOW() WHERE id='".$r['crawler_id']."'");	
			else SendMessage("Could not kill $crawler_id", $crawler_id);			
			
			break;
		case 'force':		
			
			//processed below
				
			break;			
		default:		
		
			Logger::Quit("Crawler command ".$r['command']." is not defined.");	
				
			break;
	}	
}

////////////////////////////////////////////////////////////
//Checking previous started sessions
////////////////////////////////////////////////////////////
$running_crawlers = array();
$running_crawlers_ms = array();

$result = Db::GetArray("SELECT (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(_last_start_time)) AS duration, run_time_span, id AS crawler_id, state, _last_start_time, _last_process_id, _last_log, admin_emails, _last_session_state, crawl_product_timeout FROM crawlers WHERE _last_session_state IN ('started', '_error', '_completed', 'debug_completed')");	
foreach($result as $r)
{
	$crawler_id = $r['crawler_id'];	
	$m1 = "\nStarted: ".$r['_last_start_time']."\nLog: ".$r['_last_log']."\nLogs url: ".Constants::LogUrl."/".$crawler_id;
		
	if($r["_last_session_state"] == "_completed" or $r["_last_session_state"] == "debug_completed")
	{
		$m = "Crawler $crawler_id completed successfully.\nTotal duration: ".Misc::GetDurationAsString($r['duration']).$m1;
		if($r["_last_session_state"] == "debug_completed") SendMessage($m, $crawler_id, false);
		Db::Query("UPDATE crawlers SET _last_session_state='completed' WHERE id='".$r['crawler_id']."'");	
		continue;
	}
	
	if($r["_last_session_state"] == "_error")
	{
		SendMessage("Crawler $crawler_id exited with error.$m1", $crawler_id);
		//Db::Query("UPDATE crawlers SET _last_session_state='error', _next_start_time=ADDDATE(NOW(), INTERVAL ".RESTART_DELAY_FOR_BROKEN_CRAWLER_IN_SECS." SECOND) WHERE id='".$r['crawler_id']."'");
		Db::Query("UPDATE crawlers SET _last_session_state='error' WHERE id='".$r['crawler_id']."'");
		continue;
	}
	
	if(!Shell::IsProcessAlive($r['_last_process_id']))
	{
		SendMessage("Crawler $crawler_id was broken by unknown reason.$m1", $crawler_id);
		Db::Query("UPDATE crawlers SET _last_session_state='broken', _next_start_time=ADDDATE(NOW(), INTERVAL ".RESTART_DELAY_FOR_BROKEN_CRAWLER_IN_SECS." SECOND), _last_end_time=NOW() WHERE id='".$r['crawler_id']."'");	
		continue;
	}
	
	$products_table = "products_".$r['crawler_id'];
	if(!Db::GetSingleValue("SHOW TABLES LIKE '$products_table'"))
	{
		Logger::Warning_("No table $products_table exists.");
		continue;
	}
	
	if($r['duration'] >= $r['crawl_product_timeout'])
	{
		$last_crawled_product_elapsed_time = Db::GetSingleValue("SELECT UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(_last_product_time) FROM crawlers WHERE id='".addslashes($r['crawler_id'])."'");
		if($last_crawled_product_elapsed_time > 10000) $last_crawled_product_elapsed_time = Db::GetSingleValue("SELECT MIN(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(crawl_time)) FROM $products_table"); //done for compatibility with old crawlers
		if($last_crawled_product_elapsed_time === null or $last_crawled_product_elapsed_time > $r['crawl_product_timeout'])
		{
			if(!$last_crawled_product_elapsed_time) $last_crawled_product_elapsed_time = -1;
			SendMessage("Crawler $crawler_id is running but not crawling products during $last_crawled_product_elapsed_time seconds. It will be killed. Total duration: ".Misc::GetDurationAsString($r['duration'])." $m1", $crawler_id);
			$command = "kill -9 ".$r['_last_process_id'];
			Logger::Write("Killing $crawler_id:\n$command\n>".shell_exec($command));
			sleep(2);
			if(!Shell::IsProcessAlive($r['_last_process_id']))
			{	
				Db::Query("UPDATE crawlers SET _last_session_state='killed', _next_start_time=ADDDATE(NOW(), INTERVAL ".RESTART_DELAY_FOR_BROKEN_CRAWLER_IN_SECS." SECOND), _last_end_time=NOW() WHERE id='".$r['crawler_id']."'");
				continue;		
			}
			Logger::Error("Could not kill $crawler_id");		
		}		
	}
	
	$crawler_process_number++;
	$running_crawlers[] = $crawler_id;
	$running_crawlers_ms[] = "$crawler_id, process id: ".$r['_last_process_id'];	
}
if(count($running_crawlers_ms)) Logger::Write("Already running: ".Misc::GetArrayAsString($running_crawlers_ms));

////////////////////////////////////////////////////////////
//Starting new sessions
////////////////////////////////////////////////////////////
$remaining_crawlers = array();
$result = Db::GetArray("SELECT id AS crawler_id, state, command, admin_emails FROM crawlers WHERE (state<>'disabled' AND NOW()>=_next_start_time AND command<>'stop') OR command='force' ORDER BY command, _next_start_time");
foreach($result as $r)
{ 
	$crawler_id = $r['crawler_id'];
	
	if($r['command'] == "force")
	{
		Logger::Write2("Forcing $crawler_id");
		if($r['state'] == "disabled")
		{
			Logger::Error_("$crawler_id is disabled.");
			continue;
		}
		if(in_array($crawler_id, $running_crawlers))
		{
			Logger::Warning_("$crawler_id is running already.");
			Db::Query("UPDATE crawlers SET command='' WHERE id='$crawler_id'");
			continue;
		}
		if(launch_crawler($crawler_id))
		{
			$crawler_process_number++;	
			Db::Query("UPDATE crawlers SET command='' WHERE id='$crawler_id'");
		}
		continue;
	}
	
	if(in_array($crawler_id, $running_crawlers)) continue;
	if($crawler_process_number >= Constants::CrawlerProcessMaxNumber)
	{
		$remaining_crawlers[] = $crawler_id;
		continue;
	}	
	if(launch_crawler($crawler_id)) $crawler_process_number++;
}

if(count($remaining_crawlers)) Logger::Warning_("crawler_process_number reached ".Constants::CrawlerProcessMaxNumber." so no more crawler will be started.\nCrawlers remaining to start: ".Misc::GetArrayAsString($remaining_crawlers));		

if($crawler_process_number) Logger::Write2("Currently running crawlers: $crawler_process_number");
else Logger::Write2("Currently no crawler runs.");

Logger::Write2("COMPLETED");

function launch_crawler($crawler_id)
{	
	$r = Db::GetRowArray("SELECT * FROM crawlers WHERE id='$crawler_id'");
	if(!$r['id'])
	{
		Logger::Error_("Crawler does not exist: $crawler_id");
		return false;
	}
	if($r['state'] == "enabled") $option = "-r";
	elseif($r['state'] == "debug") $option = "-d";
	else
	{
		Logger::Error_("Unknown option: ".$r['state']);
		return false;
	}
	$admin_emails = $r["admin_emails"];
	
	$crawler_file = dirname(__FILE__)."/crawlers/$crawler_id.php";	
	if(!file_exists($crawler_file))
	{
	    SendMessage("Crawler '$crawler_id' does not exist", $crawler_id);
		return false;		
	}
	$command = "nohup php $crawler_file $option >/dev/null 2>&1 & echo $!";
	Logger::Write("Starting crawler $crawler_id: $command");
	$o = array();
	$pid = exec($command, $o);
	//Logger::Write($o);
	if(!$pid or $pid == "$!")
	{
		SendMessage("Could not run $command", $crawler_id);
		return false;
	}
	sleep(2);
	if(!Shell::IsProcessAlive($pid))
	{
	    SendMessage("$crawler_id could not start.\nLogs url: ".Constants::LogUrl."/".$crawler_id, $crawler_id);
		return false;
	}	
	Logger::Write("Process id: ".$pid);
	return true;
}

?>