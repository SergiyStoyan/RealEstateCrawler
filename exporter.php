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
set_time_limit(0);
ini_set('memory_limit', '-1');

include_once("common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

class Mode extends ModeTemplate
{
   	const MAIN = null;
   	const EXPORT_TABLE = "c";
   	const REEXPORT_ENTIRE_TABLE = "s";
   	const REEXPORT_ENTIRE_DB = "u";
}

Mode::PrintUsage(array(
	Mode::MAIN=>" - main mode",
	Mode::EXPORT_TABLE=>"=<crawler_id> - export the table of <crawler_id>",
	Mode::REEXPORT_ENTIRE_TABLE=>"<crawler_id> - re-export the entire table of <crawler_id>. Used when synchronization was lost",
	Mode::REEXPORT_ENTIRE_DB=>" - set re-export the entire db. Used when synchronization was lost"
	)
);

include_once("common/logger.php");
include_once("constants.php");
Logger::Init(Constants::LogDirectory."/_exporter", 10);
Logger::$CopyToConsole = false;

include_once("common/db.php");
include_once("common/misc.php");
include_once("base/crawler6/regex.php");
include_once("base/crawler6/table_routines.php");
		
Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");
Logger::Write2("MODE: ".Mode::Name());

const SESSION_USERNAME = "devuser";
const SESSION_PASSWORD = "devuser#123";

function get_context()
{
	return stream_context_create(
		array(
    		'http'=>array(
       			'header'=>"Authorization: Basic ".base64_encode(SESSION_USERNAME.":".SESSION_PASSWORD)
    		)
		)
	);
}

const REFRESH_SESSION_TIMEOUT = 100; 

function refresh_session()
{	
	static $next_time2open = 0;
	if(time() < $next_time2open) return;
	for($i = 0; $i < 30; $i++)
	{
		//$p = file_get_contents("http://stage.tycoonsystem.com/crawler/start?key=secret", false, get_context());
		$p = file_get_contents("http://www.tycoonsystem.com/crawler/start?key=secret", false, get_context());
		if(preg_match("@^[^\w]*OK[^\w]*$@is", $p))
		{
			$next_time2open = time() + REFRESH_SESSION_TIMEOUT;
			Logger::Write("Session refreshed. Drupal returned: $p");
			return;	
		}
		Logger::Error("Drupal returned: $p");
		sleep(60);
	}
	print_report();
	Logger::Quit("Could not get OK response from Drupal.");
}

function close_session()
{	
	//$p = file_get_contents("http://stage.tycoonsystem.com/crawler/done?key=secret", false, get_context());
	$p = file_get_contents("http://www.tycoonsystem.com/crawler/done?key=secret", false, get_context());
	if(preg_match("@^[^\w]*OK[^\w]*$@is", $p)) Logger::Write2("Session has been closed.");
	else Logger::Error("Could not close session. Drupal returned: $p");
}

$RecordStats = array('total_updated_local_record_count'=>0, 'total_invalidated_local_record_count'=>0, 'total_deleted_local_record_count'=>0, 'total_inserted_remote_record_count'=>0, 'total_changed_remote_record_count'=>0, 'total_same_remote_record_count'=>0, 'total_deleted_remote_record_count'=>0);

function query($sql, $exit_on_no_affect_error=true)
{	
	global $RecordStats;
	$result = Db::Query($sql);
	if($result !== true) return $result;
	//no SELECT
	if($rn = Db::LastAffectedRows())
	{
		if(preg_match("@^\s*DELETE\s+@is", $sql)) $RecordStats['total_deleted_local_record_count'] += $rn;
		elseif(preg_match("@[=\s]'invalid'[,\s]@is", $sql)) $RecordStats['total_invalidated_local_record_count'] += $rn;
		else $RecordStats['total_updated_local_record_count'] += $rn;
	}
	else
	{
		if($exit_on_no_affect_error) Logger::Quit("No affected row with sql:\n".$sql, 1);
		//else Logger::Warning_("No affected row with sql:\n".$sql);		
	}		
	return $rn;
}

const REMOTE_CONNECTION = "REMOTE_CONNECTION";
Db::AddConnectionString(Constants::DataBaseHost2, Constants::DataBaseUser2, Constants::DataBasePassword2, Constants::DataBase2, REMOTE_CONNECTION);

function query2($sql, $exit_on_no_affect_error=true)
{
	refresh_session();	
	global $RecordStats;
	$result = Db::Query($sql, REMOTE_CONNECTION);
	if($result !== true) return $result;
	//no SELECT		
	if($rn = Db::LastAffectedRows(REMOTE_CONNECTION))
	{
		if(preg_match("@[\s,]record_status\s*=\s*3[\s,]@is", $sql)) $RecordStats['total_deleted_remote_record_count'] += $rn;
		elseif(preg_match("@^\s*DELETE\s+@is", $sql)) Logger::Quit("No DELETE is allowed on the remote database!");
		elseif(preg_match("@^\s*REPLACE\s+@is", $sql))
		{
			if($rn == 1) $RecordStats['total_inserted_remote_record_count'] += 1;
			elseif($rn == 2)
			{
				$rn = 1;
				$RecordStats['total_changed_remote_record_count'] += 1;
			}
			elseif($rn == 0) $RecordStats['total_same_remote_record_count'] += 1;
			else Logger::Quit("RELACE affected more than 1 record: $rn"); 
		}
		else Logger::Quit("Such sql $sql cannot be counted."); 
	}
	else
	{
		if($exit_on_no_affect_error) Logger::Quit("No affected row with sql:\n".$sql, 1);
		//else Logger::Warning_("No affected row with sql:\n".$sql);			
	}
	return $rn;
}

$ValidationStats = array('old_publish_time'=>0,'price == 1'=>0,'!price'=>0,'!status'=>0,'is_sold'=>0,);
function validate_product(&$p)
{		
	$p['price'] = preg_replace("@[^\d]+@s", "", $p['price']);
	$p['postcode'] = strtoupper($p['postcode']);
	$p['postcode1'] = preg_replace("@(?<=.)\s.*@s", "", $p['postcode']);
	$p['bedroom_number'] = preg_replace("@[^\d]+@s", "", $p['bedroom_number']);
	$p['description'] = Regex::GetFirstValue("@((.*?\s+){0,50})@is", Html::PrepareField($p['description']));
	if($p['agent'] and preg_match("@_owner@is", $p['agent'])) $p['agent'] = "for_sale_by_owner";
	else $p['agent'] = "estate_agent";
	if(!$p['image_path']) $p['image_path'] = Constants::DefaultImageName;
	$p['image_path'] = addslashes($p['image_path']);
	
	global $ValidationStats;
	
	static $old_publish_time;
	if(!$old_publish_time) $old_publish_time = time() - 18 * 30 * 24 * 3600;		
	if(strtotime($p['publish_date']) <= $old_publish_time) 
	{
		$ValidationStats['old_publish_time']++;
		return false;
	}
	
	if($p['price'] == 1) 
	{
		$ValidationStats['price == 1']++;
		return false;
	}
	
	/*if(!$p['price']) 
	{
		$ValidationStats['!price']++;
		return false;
	}*/
	
	if(!$p['status']) 
	{
		$ValidationStats['!status']++;
		return false;
	}
	
	if(array_key_exists('is_sold', $p)) 
	{//sale
		$ValidationStats['is_sold']++;
		return false;
	}
	
	return true;
}

function show_progress($result)
{
	static $rows = 0;
	$rows += $result;
	Logger::Write2("Affected rows: $rows");
}
	
switch(Mode::This())
{
	case Mode::EXPORT_TABLE:
	
		$products_table = TableRoutines::GetProductsTableForCrawler(Shell::GetCommandOpt("c"));
		process_products_table($products_table);
			
		break;
	case Mode::REEXPORT_ENTIRE_TABLE:
	
		$crawler_id = Shell::GetCommandOpt("s");
		$products_table = TableRoutines::GetProductsTableForCrawler();
		query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid LIKE '".$crawler_id."_%' AND record_status<>3");//it is needed if the remote db lost synchronization
		while($rc = query("UPDATE $products_table SET _state='parsed' WHERE _state='replicated' LIMIT 10000", false)) show_progress($rc);
		$RecordStats = array('total_updated_local_record_count'=>0, 'total_invalidated_local_record_count'=>0, 'total_deleted_local_record_count'=>0, 'total_inserted_remote_record_count'=>0, 'total_changed_remote_record_count'=>0, 'total_same_remote_record_count'=>0, 'total_deleted_remote_record_count'=>0);
		process_products_table($products_table);		
			
		break;
	case Mode::REEXPORT_ENTIRE_DB:
	
		Logger::Warning_("Attention: it may take long time! Are you sure re-exporting of the entire db?");
		if(!Shell::GetYesOrNoFromConsole()) 
		{
			Logger::Write2("Mode ".Mode::Name()." was declined! Exiting...");
			exit();
		}
		
		Logger::Write2("Cleaning up the remote database.");
		query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE record_status<>3");//it is needed if the remote db lost synchronization
		foreach(TableRoutines::GetProductsTables() as $products_table) 
		{
			Logger::Write2("Updating $products_table");
			//limit is used to:
			//a) avoid blocking the table for a long time
			//b) save RAM
			while($rc = query("UPDATE $products_table SET _state='parsed' WHERE _state IN ('replicated', 'invalid') LIMIT 10000", false)) show_progress($rc);
		}
		
		Logger::Write2("The records have been marked for re-exporting. Now you have to run exproter.php with no key to perform parsig or let it be done by cron.");
			
		break;
	case Mode::MAIN:
		
		foreach(TableRoutines::GetProductsTables() as $products_table) process_products_table($products_table);
			
		break;		
	default: 
		
		Logger::Quit("Mode ".Mode::Name()." is not defined.");
}
	
function process_products_table($products_table)
{		
	Logger::Write2("Processing table $products_table");
	
	$updated_local_record_count = $invalidated_local_record_count = $deleted_local_record_count = $updated_remote_record_count = $deleted_remote_record_count = 0;	
	
	$crawler_id = TableRoutines::GetCrawlerIdForProductsTable($products_table);	
	if($crawler_id)
	{
		$state = Db::GetSingleValue("SELECT state FROM crawlers WHERE id='$crawler_id'");		
		if($state == 'debug')
		{
			//Logger::Write("Table $products_table is ignored because crawler $crawler_id is in debug state.");	
			Logger::Warning_("Table $products_table has crawler in debug stage. Deleting its all remote records and 'deleted' local records.");
			while($rn = query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid LIKE '".addslashes($crawler_id)."+%' AND record_status<>3 LIMIT 10000", false)) $deleted_remote_record_count += $rn; //limit is used to:
			//a) avoid blocking the table for a long time
			//b) save RAM
			while($rn = query("DELETE FROM $products_table WHERE _state='deleted' LIMIT 10000", false)) $deleted_local_record_count += $rn;//limit is used to:
			//a) avoid blocking the table for a long time
			//b) save RAM
			return;
		}
	}
	
	if(!$crawler_id or $state == 'disabled')
	{
		Logger::Warning_("Table $products_table has no crawler or it is disabled. Deleting its remote records and dropping its local table.");
		while($rn = query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid LIKE '".addslashes($crawler_id)."+%' AND record_status<>3 LIMIT 10000", false)) $deleted_remote_record_count += $rn; //limit is used to:
		//a) avoid blocking the table for a long time
		//b) save RAM
		query("DROP TABLE $products_table", false);
		//image folder will be deleted by cleaner.php
		return;
	}
	
//validate already replicated records
///////////////////////////////////////////////
	/*if(!array_key_exists('is_sold', $p)) 
	{//auction
		while($rs = Db::GetArray("SELECT id, auction_date FROM $products_table WHERE _state='replicated' AND NOW()>auction_date"))
			if(preg_match("@auction@is", $p['status']) and $ad = strtotime($p['auction_date']) and $ad < time())
			{
				$deleted_remote_record_count += query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid='".addslashes($crawler_id."+".$p['id'])."'", false);
			}
	}*/
///////////////////////////////////////////////

	//limit is used to:
	//a) avoid blocking the table for a long time
	//b) save RAM	
	while($rs = Db::GetArray("SELECT id, parsed_data FROM $products_table WHERE _state='deleted' LIMIT 1000"))
	{
		foreach($rs as $r)
		{	
			$deleted_remote_record_count += query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid='".addslashes($crawler_id."+".$r['id'])."'", false);
			
			$p = json_decode($r['parsed_data'], true);
			if($p['image_path'])
			{//it is done to help Cleaner
				$image_file = Constants::ImageDirectory."/".$p['image_path'];
				if(file_exists($image_file))
				{
					if(unlink($image_file)) Logger::Write("Deleted: $image_file");
					else Logger::Warning_("Could not delete $image_file");
				}
			}
			$deleted_local_record_count += query("DELETE FROM $products_table WHERE id='".addslashes($p['id'])."'");
		}
	}

	//limit is used to:
	//a) avoid blocking the table for a long time
	//b) save RAM	
	while($rs = Db::GetArray("SELECT id, raw_data, parsed_data FROM $products_table WHERE _state='parsed' LIMIT 1000"))
	//while($rs = Db::GetArray("SELECT * FROM $products_table WHERE _state='parsed' AND change_date<=ADDDATE(NOW(), INTERVAL -7 DAY) LIMIT 1000"))
	{
		foreach($rs as $r)
		{
			$rd = json_decode($r['raw_data'], true);
			$p = json_decode($r['parsed_data'], true);
			if(!array_key_exists('agent', $p)) $p['agent'] = $rd['agent'];
			if(!array_key_exists('image_path', $p)) $p['image_path'] = $rd['image_path'];
			if(!array_key_exists('publish_date', $p)) if(array_key_exists('publish_date', $p)) $p['publish_date'] = $rd['publish_date']; else $p['publish_date'] = null;
			if(array_key_exists('is_sold', $rd))/*sale*/ if(!array_key_exists('is_sold', $p)) $p['is_sold'] = $rd['is_sold'];			
			
			//the following is needed for legacy data:
			$p['postcode'] = preg_replace("@^\s*\!+@is", "", $p['postcode']);
			$p['county'] = preg_replace("@^\s*\!+@is", "", $p['county']);
			$p['town'] = preg_replace("@^\s*\!+@is", "", $p['town']);
			$p['street'] = preg_replace("@^\s*\!+@is", "", $p['street']);
						
			if(array_key_exists('is_sold', $p)) 
			{//sale		 
			}
			else
			{//auction
				if($p['status']) $p['status'] = $p['status'].",auction";
				else $p['status'] = "auction";
			}
			
			if(!validate_product($p))
			{
				$deleted_remote_record_count += query2("UPDATE kh_deals SET record_status=3, update_timestamp=UNIX_TIMESTAMP(NOW()), synchronized_time=NOW() WHERE cid='".addslashes($crawler_id."+".$r['id'])."'", false);
				$invalidated_local_record_count += query("UPDATE $products_table SET _state='invalid' WHERE id='".addslashes($r['id'])."'");
				continue;
			}
							
			$sql1 = "kh_deals SET image_url='".addslashes($p['image_path'])."', thumbnail_url='".addslashes($p['image_path'])."', source_url='".addslashes($p['url'])."', price='".addslashes($p['price'])."', street='".addslashes($p['street'])."', county='".addslashes($p['county'])."', town='".addslashes($p['town'])."', postal_code='".addslashes($p['postcode'])."', postal_code_short='".addslashes($p['postcode1'])."', property_type='".$p['type']."', bedrooms='".addslashes($p['bedroom_number'])."', listing_timestamp=UNIX_TIMESTAMP('".$p['publish_date']."'), synchronized_time=NOW(), features='".addslashes($p['features'])."', tenure='".addslashes($p['tenure'])."', deal_source='".addslashes($p['agent'])."', property_types='".$p['status']."'";			
			if(array_key_exists('is_sold', $p)) 
			{//sale		 
				$sql1 .= ", description='".addslashes($p['description'])."', is_sold='".addslashes($p['is_sold'])."'";
			}
			else
			{//auction
				$sql1 .= ", description='".addslashes($p['agent'].", ".$p['auction_date'].", ".$p['auction_time'].", ".$p['auction_location']." ".$p['description'])."'";
			}
			
			$updated_remote_record_count += query2("REPLACE $sql1, record_status=2, update_timestamp=UNIX_TIMESTAMP(NOW()), cid='".addslashes($crawler_id."+".$r['id'])."'");
			$updated_local_record_count += query("UPDATE $products_table SET _state='replicated' WHERE id='".addslashes($r['id'])."'");
		}
	}
		
	Logger::Write2("\nLocal records updated: $updated_local_record_count\nLocal records invalidated: $invalidated_local_record_count\nLocal records deleted: $deleted_local_record_count\nRemote records updated: $updated_remote_record_count\nRemote records deleted: $deleted_remote_record_count");
}

function print_report()
{
	global $RecordStats;
	Logger::Write2("TOTAL: ".Misc::GetArrayAsString($RecordStats));
		
	global $ValidationStats;	
	Logger::Write2("Invalidated records: ".Misc::GetArrayAsString($ValidationStats));	
}

print_report();
close_session();
Logger::Write2("COMPLETED");

?>