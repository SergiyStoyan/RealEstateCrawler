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
set_time_limit(10 * 60 * 60);
//ini_set('memory_limit', '-1');

include_once("common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

include_once("common/logger.php");
include_once("constants.php");
Logger::Init(Constants::LogDirectory."/_watcher", 40);

include_once("common/db.php");
include_once("common/misc.php");

include_once("base/crawler6/table_routines.php");

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");

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
	$subject = "Crawler Watcher:";
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

$rs = Db::GetArray("SELECT id, yield_product_timeout, run_time_span, UNIX_TIMESTAMP(_last_product_time) AS _last_product_time, UNIX_TIMESTAMP(_last_end_time) AS _last_end_time FROM crawlers WHERE state<>'disabled'");
foreach($rs as $r)
{
	$crawler_id = $r['id'];
	$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
	Logger::Write2("Watching products table: $products_table");
	
	if($r['yield_product_timeout'] > 0 and !Db::GetSingleValue("SELECT id FROM $products_table WHERE publish_time >= NOW() - INTERVAL ".$r['yield_product_timeout']." SECOND LIMIT 1")) SendMessage("Crawler $crawler_id did not add a new product for the last ".Misc::GetDurationAsString($r['yield_product_timeout'])." The last product was added ".date("Y-m-d H:i:s", Db::GetSingleValue("SELECT UNIX_TIMESTAMP(publish_time) FROM $products_table ORDER BY publish_time DESC LIMIT 1")), $crawler_id);
	//$EXPECTED_YIELD_TIME_SPAN_IN_SECS = $r['run_time_span'] * $yield_session_count;
	//if(time() - $EXPECTED_YIELD_TIME_SPAN_IN_SECS >= $r['_last_product_time']) SendMessage("Crawler $crawler_id did not add a product for the last ".Misc::GetDurationAsString($EXPECTED_YIELD_TIME_SPAN_IN_SECS." The last product was added ".date("Y-m-d H:i:s", $r['_last_product_time']), $crawler_id));
}

Logger::Write2("COMPLETED");

?>