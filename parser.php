<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
set_time_limit(0);
ini_set('memory_limit', '-1');

include_once("common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

class Mode extends ModeTemplate
{
   	const MAIN = null;
   	const TEST_PRODUCT = "t";
   	const REPARSE_PRODUCT = "r";
   	const REPARSE_SITE_EMPTY_ADDRESS = "b";
   	const REPARSE_SITE = "s";
   	const REPARSE_ENABLED_SITES = "g";
	const REPARSE_ENTIRE_DB = "u";
}

Mode::PrintUsage(array(
	Mode::MAIN=>" - main mode meaning parsing all not-parsed products",
	Mode::TEST_PRODUCT=>"=<crawler_id>+<product_id> - parse the data for <product_id> in table of <crawler_id> and output results to console",
	Mode::REPARSE_PRODUCT=>"=<crawler_id>+<product_id> - re-parse the data for <product_id> in table of <crawler_id>",
	Mode::REPARSE_SITE_EMPTY_ADDRESS=>"=<crawler_id> - re-parse all products of <crawler_id> having no address detected",
	Mode::REPARSE_SITE=>"=<crawler_id> - re-parse all products of <crawler_id>",
	Mode::REPARSE_ENABLED_SITES=>" - re-parse all products of all enabled crawlers",
	Mode::REPARSE_ENTIRE_DB=>" - re-parse all products in the database"
	)
);

include_once("common/logger.php");
include_once("constants.php");
Logger::Init(Constants::LogDirectory."/_parser", 3);
//Logger::$CopyToConsole = 1;

include_once("common/db.php");
include_once("common/html_utilities.php");
include_once("base/crawler6/table_routines.php");
include_once("parser/address_parser.php");
include_once("parser/value_parser.php");
include_once("parser/common_phrase_detector.php");
include_once("parser/alert_seeker.php");

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");
Logger::Write2("MODE: ".Mode::Name());

//pcntl_setpriority(-1);
//proc_nice(-5) or Logger::Warning_("Could not renice the process.");

function show_progress($result)
{
	static $rows = 0;
	$rows += $result;
	Logger::Write2("Affected rows: $rows");
}

switch(Mode::This())
{
	case Mode::REPARSE_SITE_EMPTY_ADDRESS:
	
		$crawler_id = Mode::OptionValue();
		$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
		if(!$products_table) Logger::Quit("No table for crawler '$crawler_id' exists.");
		Logger::Write2("Updating $products_table");		
		while($result = Db::SmartQuery("UPDATE $products_table SET _state='new' WHERE _state IN ('parsed', 'replicated', 'invalid') AND postcode='' LIMIT 10000")) show_progress($result);		
		Logger::Write2("#############################\nThe records have been marked for reparsing. You can brake the process now and wait until they are processed by cron.");
		process_products_table($products_table);
		
		break;	
	case Mode::MAIN:
	
		//foreach(TableRoutines::GetProductsTables() as $products_table) process_products_table($products_table);	
		$crawlers = Db::GetFirstColumnArray("SELECT id FROM crawlers WHERE state IN ('enabled', 'debug') ORDER BY state, _last_start_time");
		foreach($crawlers as $crawler_id)
		{
			$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
			if(!$products_table)
			{
				Logger::Write("No table for crawler '$crawler_id' exists.");
				continue;
			}
			process_products_table($products_table);
		}
	
		break;		
	case Mode::REPARSE_ENABLED_SITES:
		
		$crawlers = Db::GetFirstColumnArray("SELECT id FROM crawlers WHERE state='enabled' ORDER BY _last_start_time");
		foreach($crawlers as $crawler_id)
		{
			$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
			if(!$products_table)
			{
				Logger::Write("No table for crawler '$crawler_id' exists.");
				continue;
			}
			Logger::Write2("Updating $products_table");
			while($result = Db::SmartQuery("UPDATE $products_table SET _state='new' WHERE _state IN ('parsed', 'replicated', 'invalid') LIMIT 10000")) show_progress($result);
		}
		Logger::Write2("The records have been marked for re-parsing. Now you have to run parser.php with no key to perform parsig or let it be done by cron.");
	
		break;		
	case Mode::REPARSE_ENTIRE_DB:
		
		foreach(TableRoutines::GetProductsTables() as $products_table)
		{
			Logger::Write2("Updating $products_table");
			while($result = Db::SmartQuery("UPDATE $products_table SET _state='new' WHERE _state IN ('parsed', 'replicated', 'invalid') LIMIT 10000")) show_progress($result);
		}
		Logger::Write2("The records have been marked for re-parsing. Now you have to run parser.php with no key to perform parsig or let it be done by cron.");
		
		break;		
	case Mode::REPARSE_SITE:
	
		$crawler_id = Mode::OptionValue();
		$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
		if(!$products_table) Logger::Quit("No table for crawler '$crawler_id' exists.");
			Logger::Write2("Updating $products_table");
		while($result = Db::SmartQuery("UPDATE $products_table SET _state='new' WHERE _state IN ('parsed', 'replicated', 'invalid') LIMIT 10000")) show_progress($result);
		Logger::Write2("#############################\nThe table have been marked for reparsing. You can brake the process now and wait until they are processed by cron.");		
		process_products_table($products_table);
	
		break;
	case Mode::TEST_PRODUCT:
	case Mode::REPARSE_PRODUCT:
	
		Logger::$CopyToConsole = true;
		
		AddressParser::$WriteDetails = true;
		TableRoutines::GetPartsOfGlobalProductId(Mode::OptionValue(), $crawler_id, $product_id);
		$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
		$p = Db::GetRowArray("SELECT * FROM $products_table WHERE id='".addslashes($product_id)."'") or Logger::Quit("Product ID '$product_id' does not exist in '$products_table'");
		$rd = json_decode($p['raw_data'], true);
		
		Logger::Write("Parsing product: ".$p['id']." in $products_table");
		Logger::Write("Url: ".$p['url']);
		Logger::Write("Address=\n".$rd['address']);
		Logger::Write("Headline=\n".$rd['headline']);
		//Logger::Write("Description=\n".$rd['description']);	
					
		parse_product($p['id'], $rd, $pd, $products_table);
		Logger::Write("postcode=".$pd['postcode'].", county=".$pd['county'].", town=".$pd['town'].", village=".$pd['village'].", street=".$pd['street'].", thoroughfare=".$pd['thoroughfare']); 
		
		if(array_key_exists('is_sold', $rd))/*sale*/ Logger::Write("bedroom_number=".$pd['bedroom_number'].", type=".$pd['type'].", price=".$pd['price'].", status=".$pd['status'].", features=".$pd['features'].", is_sold=".$pd['is_sold'].", tenure=".$pd['tenure']);
		else/*auction*/ Logger::Write("bedroom_number=".$pd['bedroom_number'].", type=".$pd['type'].", price=".$pd['price'].", status=".$pd['status'].", features=".$pd['features'].", tenure=".$pd['tenure']);

		if(Mode::This() == Mode::REPARSE_PRODUCT)
		{					
			save_product($p['id'], $pd, $products_table);
			Logger::Write2("The parsed data was saved in the db.");
			seek_alerts($pd, $p['id'], $p['url'], $crawler_id);
		}
		
		break;
	default:	
		
		Logger::Quit("No such mode defined: ".Mode::Name());
}

AlertSeeker::Complete();
Logger::Write2("COMPLETED");

function process_products_table($products_table)
{			
	Logger::Write2("Processing $products_table");
	
	$crawler_id = TableRoutines::GetCrawlerIdForProductsTable($products_table);
		
	//limit is used to:
	//a) avoid blocking the table for a long time
	//b) save RAM	
	while($ps = Db::GetArray("SELECT id, url, raw_data, UNIX_TIMESTAMP(change_time) AS change_time FROM $products_table WHERE _state='new' LIMIT 1000"))
	{
		foreach($ps as $p)
		{
			$rd = json_decode($p['raw_data'], true);
			$pd = array();
			parse_product($p['id'], $rd, $pd, $products_table);
			save_product($p['id'], $pd, $products_table);
			//detect_common_phrases($rd, $crawler_id);
			seek_alerts($pd, $p['id'], $p['url'], $crawler_id);		
		}
	}
}

function save_product($id, &$pd, $products_table)
{	
	foreach($pd as $key => $value) $pd[$key] = utf8_encode($value);	
	$pdj = json_encode($pd) or Logger::Quit("json_encode error: ".json_last_error());
	$sql = "UPDATE $products_table SET _state='parsed', parsed_data='".addslashes($pdj)."', parse_time=NOW() WHERE id='".addslashes($id)."'";
	if(!Db::SmartQuery($sql)) Logger::Quit("The product was not updated with sql:\n".$sql);	
}

function parse_product($id, &$rd, &$pd, $products_table)
{	
	//Logger::Write2("Parsing product: ".$p['id']." in $products_table");
			
	try
	{		
		$ap = new AddressParser($rd, $pd);
		$ap->ParseAddress();
						
		ValueParser::Parse($rd, $pd);
	}
	catch(Exception $e)
	{
		Logger::Write2("Exception in $products_table+$id follows.");
		Logger::Quit($e);
	}	
}

/*function detect_common_phrases(&$p, $crawler_id)
{			
	try
	{		
		CommonPhraseDetector::Detect($p, $crawler_id);
	}
	catch(Exception $e)
	{
		Logger::Write2("Exception in ".TableRoutines::GetGlobalProductId($crawler_id, $p['id'])." follows.");
		Logger::Quit($e);
	}	
}*/

function seek_alerts(&$pd, $id, $url, $crawler_id)
{			
	try
	{								
		AlertSeeker::Seek($pd, $id, $url, $crawler_id);
	}
	catch(Exception $e)
	{
		Logger::Write2("Exception in ".TableRoutines::GetGlobalProductId($crawler_id, $id)." follows.");
		Logger::Quit($e);
	}	
}

?>