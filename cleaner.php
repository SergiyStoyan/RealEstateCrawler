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
Logger::Init(Constants::LogDirectory."/_cleaner", 40);

include_once("common/db.php");
include_once("common/misc.php");

include_once("base/crawler6/table_routines.php");

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");

Logger::Write2("Cleaning products tables.");
foreach(TableRoutines::GetProductsTables() as $products_table) process_products_table($products_table);
function process_products_table($products_table)
{
	$crawler_id = TableRoutines::GetCrawlerIdForProductsTable($products_table);
	//$last_run_time = Db::GetSingleValue("SELECT UNIX_TIMESTAMP(_last_product_time) FROM crawlers WHERE id='$crawler_id'");
	if(!Db::GetSingleValue("SELECT id FROM crawlers WHERE id='$crawler_id'"))
	{
		Logger::Warning_("Crawler $crawler_id for product table $products_table does not exist. Deleting table $products_table");
		Db::SmartQuery("DROP TABLE $products_table");
		return;		
	}
	$deleted_count = 0;
	while($rn = Db::SmartQuery("DELETE FROM $products_table WHERE _state='deleted' LIMIT 10000")) $deleted_count += $rn;
	Logger::Write("From table $products_table deleted records:$deleted_count");
	//Logger::Write2("Optimizing table $products_table.");
	//Db::Query("OPTIMIZE TABLE `$products_table`");    
}

////////////////////////////////////////////////////////////
//Queues cleanup
////////////////////////////////////////////////////////////
include_once("base/crawler6/session.php");
Session::Cleanup();

////////////////////////////////////////////////////////////
//Image files cleanup
////////////////////////////////////////////////////////////
$dh = opendir(Constants::ImageDirectory) or Logger::Quit("Could not open image dir: ".Constants::ImageDirectory);
while($d = readdir($dh)) 
{
 	if($d == "." or $d == "..") continue;
	$crawler_id = $d;
	$crawler_image_dir = Constants::ImageDirectory."/$crawler_id";
 	if(!is_dir($crawler_image_dir)) continue;
	if(!TableRoutines::GetProductsTableForCrawler($crawler_id))
	{
		Logger::Warning_("Product table for crawler $crawler_id does not exist. Deleting dir '$crawler_image_dir'");
		Misc::ClearDirectory($crawler_image_dir);
		continue;
	}	
}
closedir($dh);

$old_file_threshold_time = time() - (60 * 24 * 60 * 60);
foreach(TableRoutines::GetProductsTables() as $products_table) touch_image_files_for_products_table($old_file_threshold_time, $products_table);
function touch_image_files_for_products_table($old_file_threshold_time, $products_table)
{
	Logger::Write2("Touching image files of old but not-deleted products for $products_table.");
	$last_id = null;
	while($rs = Db::GetArray("SELECT id, raw_data FROM $products_table WHERE _state<>'deleted' AND crawl_time<=FROM_UNIXTIME($old_file_threshold_time) AND id>'".$last_id."' ORDER BY id LIMIT 1000"))
	{
		foreach($rs as $r)
		{			
			$rd = json_decode($r['raw_data'], true);
			if(!isset($rd['image_path'])) continue;
			$file = Constants::ImageDirectory."/".$rd['image_path'];
			touch($file) or Logger::Error("Could not change time of the file $file");			
		}
		$last_id = $r['id'];
	}	
}
Logger::Write2("Deleting image files older than ".date("Y-m-d H:i:s", $old_file_threshold_time));
$file = Constants::ImageDirectory."/".Constants::DefaultImageName;
touch($file) or Logger::Error("Could not change time of the file $file");
Misc::ClearDirectory(Constants::ImageDirectory, $old_file_threshold_time);

////////////////////////////////////////////////////////////
//Cached files cleanup
////////////////////////////////////////////////////////////
$old_file_threshold_time = time() - (10 * 24 * 60 * 60);
Logger::Write2("Deleting cached files older than ".date("Y-m-d H:i:s", $old_file_threshold_time));
Misc::ClearDirectory(Constants::CacheDirectory, $old_file_threshold_time);

////////////////////////////////////////////////////////////
//Log files cleanup
////////////////////////////////////////////////////////////
//Despite of Logger cleaning, it will clean up also logs from scripts that do not run anymore.
$old_file_threshold_time = time() - (30 * 24 * 60 * 60);
Logger::Write2("Deleting log files older than ".date("Y-m-d H:i:s", $old_file_threshold_time));
Misc::ClearDirectory(Constants::LogDirectory, $old_file_threshold_time);

////////////////////////////////////////////////////////////
//Client alerts cleanup
////////////////////////////////////////////////////////////
include_once("parser/alert_seeker.php");
AlertSeeker::Cleanup();

Logger::Write2("COMPLETED");

?>