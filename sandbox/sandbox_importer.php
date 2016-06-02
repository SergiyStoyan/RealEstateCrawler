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

$ABSPATH = dirname(__FILE__)."/../";

include_once("$ABSPATH/common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

class Mode extends ModeTemplate
{
   	const MAIN = null;
   	const IMPORT_SITE = "s";
   	const IMPORT_ALL_SITES = "i";
   	const DROP_ALL_PRODUCTS_TABLES = "d";
   	const DROP_ALL_SITES = "c";
}

Mode::PrintUsage(array(
	Mode::MAIN=>" - import tables of the crawlers having state 'enabled' or 'debug' in the sandbox. Existing data is not overwritten",
	Mode::IMPORT_SITE=>"=<crawler_id> - import crawler <crawler_id> and its products table. Existing data is not overwritten",
	Mode::IMPORT_ALL_SITES=>" - import all the crawlers having state 'enabled' or 'debug' in the origin db and their products tables. Existing data is not overwritten",
	Mode::DROP_ALL_PRODUCTS_TABLES=>" - drop all the products tables in the sandbox",
	Mode::DROP_ALL_SITES=>" - drop 'crawlers' and all the products tables in the sandbox",
	)
);

const RECORDS_BUFFER_LIMIT_NUMBER = 10000;

set_time_limit(0);
ini_set('memory_limit', '-1');

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
Logger::Init(Constants::LogDirectory."/_sandbox_importer", 3);
Logger::$CopyToConsole = false;

include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/table_routines.php");
include_once("$ABSPATH/base/field_routines.php");
		
Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");
Logger::Write2("MODE: ".Mode::Name());

const ORIGIN_CONNECTION = "ORIGIN_CONNECTION";
Db::AddConnectionString(Constants::DataBaseHost, Constants::DataBaseUser, Constants::DataBasePassword, "real_estate", ORIGIN_CONNECTION);

switch(Mode::This())
{
	case Mode::DROP_ALL_SITES:
	case Mode::DROP_ALL_PRODUCTS_TABLES:
		
		foreach(TableRoutines::GetProductsTables() as $products_table)
		{
			Logger::Write2("Dropping $products_table");
			Db::Query("DROP TABLE IF EXISTS $products_table");		
		}
		
		if(Mode::This() == Mode::DROP_ALL_SITES)
		{
			Logger::Write2("Dropping table 'crawlers'");
			Db::Query("DROP TABLE IF EXISTS crawlers");			
		}
						
		break;
	case Mode::MAIN:
		
		$crawlers = Db::GetFirstColumnArray("SELECT id FROM crawlers WHERE state IN ('enabled', 'debug')");
		foreach($crawlers as $crawler_id)
		{
			$products_table = GetProductsTableForCrawler_in_origin_db($crawler_id);
			if(!$products_table)
			{
				Logger::Warning_("No table for crawler '$crawler_id' exists in the origin db.");
				continue;
			}
			process_products_table($products_table);
		}
			
		break;
	case Mode::IMPORT_SITE:
	
		$crawler_id = Mode::OptionValue();
		import_site($crawler_id);
						
		break;	
	case Mode::IMPORT_ALL_SITES:
		
		$crawlers = Db::GetArray("SELECT * FROM crawlers WHERE state IN ('enabled', 'debug')", ORIGIN_CONNECTION);
		foreach($crawlers as $crawler) import_site($c['id']);
			
		break;		
	default: 
		
		Logger::Quit("Mode '".Mode::Name()."' is not defined.");
}

function import_site($crawler_id)
{	
	if(!Db::Query("SELECT id FROM crawlers WHERE id='$crawler_id'"))
	{
		$c = Db::GetRowArray("SELECT * FROM crawlers WHERE id='$crawler_id'", ORIGIN_CONNECTION);
		Db::Query("INSERT crawlers SET id='$crawler_id', state='".$c['state']."', site='".$c['site']."', command='stop', run_time_span='".$c['run_time_span']."', crawl_product_timeout='".$c['crawl_product_timeout']."', admin_emails='".$c['admin_emails']."', comments='".$c['comments']."', _last_start_time='".$c['_last_start_time']."'");
	}
	$products_table = GetProductsTableForCrawler_in_origin_db($crawler_id);
	if(!$products_table)
	{
		Logger::Warning("No table for crawler '$crawler_id' exists in the origin db.");
		return;
	}
	process_products_table($products_table);
}

function GetProductsTableForCrawler_in_origin_db($crawler_id)
{
	$r = new ReflectionClass('ProductsTableType');
	foreach($r->getConstants() as $p) if($table = Db::GetSingleValue("SHOW TABLES LIKE '$p$crawler_id'", ORIGIN_CONNECTION)) return $table;
}
	
function process_products_table($products_table)
{				
	Logger::Write2("Processing table $products_table");
	Db::Query("CREATE TABLE IF NOT EXISTS $products_table LIKE ".Db::DataBaseName(ORIGIN_CONNECTION).".$products_table");	
	
	/*if(Db::GetSingleValue("SELECT id FROM $products_table LIMIT 1"))
	{
		Logger::Write2("$products_table is not empty, so passed out.");
		return;
	}*/		
		
	$table_type = TableRoutines::GetProductsTablePrefix($products_table);
	FieldRoutines::SetProductsTableType($table_type);
	
	$temp_products_table = "temp_$products_table";
	Db::Query("CREATE TEMPORARY TABLE $temp_products_table AS (SELECT id FROM $products_table)", ORIGIN_CONNECTION);
	while($ps = Db::GetArray("SELECT * FROM (SELECT id FROM $temp_products_table LIMIT ".RECORDS_BUFFER_LIMIT_NUMBER.") a INNER JOIN $products_table b ON a.id=b.id", ORIGIN_CONNECTION))
	{
		foreach($ps as $p)
		{
			FieldRoutines::EmptyParsedValues($p);
			$p['_state'] = 'new';
			$fvs = array();
			$sql1 = "";
			foreach($p as $f=>$v) $fvs[] = "$f='".addslashes($v)."'";
			$sql1 .= join(",\n", $fvs)."\n";
			//Db::Query("REPLACE $products_table SET $sql1");	
			Db::Query("INSERT IGNORE $products_table SET $sql1");		
		}
		
		static $total_product_count = 0;
		$total_product_count += Db::SmartQuery("DELETE FROM $temp_products_table LIMIT ".RECORDS_BUFFER_LIMIT_NUMBER, ORIGIN_CONNECTION);
		Logger::Write2("Processed: $total_product_count");
	}
	Db::Query("DROP TABLE $temp_products_table", ORIGIN_CONNECTION);
}

Logger::Write2("COMPLETED");

?>