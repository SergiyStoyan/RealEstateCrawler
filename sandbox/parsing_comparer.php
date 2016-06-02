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
   	const COMPARE_TABLE = "s";
}

Mode::PrintUsage(array(
	Mode::MAIN=>" - compare tables of the crawlers having state 'enabled' or 'debug' in the target db",
	Mode::COMPARE_TABLE=>"=<crawler_id> - compare the table of <crawler_id>"
	)
);

const RECORDS_BUFFER_LIMIT_NUMBER = 10000;

set_time_limit(0);
ini_set('memory_limit', '-1');

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/constants.php");
Logger::Init(Constants::LogDirectory."/_parsing_comparer", 3);
Logger::$CopyToConsole = false;

include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/table_routines.php");
include_once("$ABSPATH/base/field_routines.php");
		
Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");
Logger::Write2("MODE: ".Mode::Name());

const ORIGIN_CONNECTION = "ORIGIN_CONNECTION";
Db::AddConnectionString(Constants::DataBaseHost, Constants::DataBaseUser, Constants::DataBasePassword, "real_estate", ORIGIN_CONNECTION);

//Db::Query("DELETE FROM _parsing_comparison");
Db::Query("DROP TABLE IF EXISTS _parsing_comparison");
Db::Query("CREATE TABLE IF NOT EXISTS `_parsing_comparison` (
  `id` varchar(500) NOT NULL,
  `fields` set('price','status','features','tenure','tenancy','type','bedroom_number','town','street','county','postcode','is_sold','auctioneer_name','auction_date','auction_time','auction_location') NOT NULL,
  `differences` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");
	
switch(Mode::This())
{
	case Mode::COMPARE_TABLE:
	
		$crawler_id = Mode::OptionValue();
		$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
		process_products_table($products_table);
						
		break;
	case Mode::MAIN:
		
		foreach(TableRoutines::GetProductsTables() as $products_table) process_products_table($products_table);
		
		break;		
	default: 
		
		Logger::Quit("Mode '".Mode::Name()."' is not defined.");
}
	
function process_products_table($products_table)
{				
	Logger::Write2("Processing table $products_table");
	
	if(!Db::GetSingleValue("SHOW TABLES LIKE '$products_table'", ORIGIN_CONNECTION))
	{
		Logger::Warning_("Table $products_table does not exist in the origin database.");
		return;
	}
	
	static $no_such_product_id = 0;
	static $different_contents = 0;
	static $equal_parsing_results = 0;
	static $different_parsing_results = 0;
	static $not_parsed_products = 0;
		
	$table_type = TableRoutines::GetProductsTablePrefix($products_table);
	FieldRoutines::SetProductsTableType($table_type);
	
	$crawler_id = TableRoutines::GetCrawlerIdForProductsTable($products_table);
	
	$temp_products_table = "temp_$products_table";
	Db::Query("CREATE TEMPORARY TABLE $temp_products_table AS (SELECT id FROM $products_table WHERE _state<>'new')", ORIGIN_CONNECTION);
	while($ps = Db::GetArray("SELECT * FROM (SELECT id FROM $temp_products_table LIMIT ".RECORDS_BUFFER_LIMIT_NUMBER.") a INNER JOIN $products_table b ON a.id=b.id", ORIGIN_CONNECTION))
	{
		foreach($ps as $p)
		{
			//the following is needed for legacy data:
			$p['postcode'] = preg_replace("@^\s*\!+@is", "", $p['postcode']);
			$p['county'] = preg_replace("@^\s*\!+@is", "", $p['county']);
			$p['town'] = preg_replace("@^\s*\!+@is", "", $p['town']);
			$p['street'] = preg_replace("@^\s*\!+@is", "", $p['street']);
			
			$p2 = Db::GetRowArray("SELECT * FROM $products_table WHERE id='".$p['id']."'");
			
			if(!$p2)
			{
				$no_such_product_id++;
				continue;
			}
			
			if($p2['_state'] == 'new')
			{
				$not_parsed_products++;
				continue;
			}
			
			if($p['headline'] != $p2['headline'] or $p['description'] != $p2['description'])
			{/*Logger::Warning($p['id']);
				if($p['headline'] != $p2['headline'])
				{
					 Logger::Warning("\n".$p['headline']."\n\n".$p2['headline']);
					 for($i=0; $i<strlen($p['headline']); $i++) if($p['headline'][$i] != $p2['headline'][$i]) Logger::Write2($i."---".$p['headline'][$i]." != ".$p2['headline'][$i]);
				}
				if($p['description'] != $p2['description']) 
				{Logger::Warning("\n".$p['description']."\n\n".$p2['description']);
					 for($i=0; $i<strlen($p['description']); $i++) if($p['description'][$i] != $p2['description'][$i]) Logger::Write2($i."---".$p['description'][$i]." != ".$p2['description'][$i]);
			Logger::Quit(8);
					 }*/
				$different_contents++;
				continue;
			}
			
			$differences = array();
			static $fields1 = array('price','status','features','tenure','type','bedroom_number');
			foreach($fields1 as $f)	if($p[$f] != $p2[$f]) $differences[$f] = $p[$f]."<>".$p2[$f];
			
			static $fields2 = array('town','street','county','postcode');
			foreach($fields2 as $f)	if($p[$f] != $p2[$f]) $differences[$f] = $p[$f]."<>".$p2[$f];
			
			if($table_type == ProductsTableType::SALE)
			{
				static $fields3 = array('is_sold');
				foreach($fields3 as $f)	if($p[$f] != $p2[$f]) $differences[$f] = $p[$f]."<>".$p2[$f];	
			}
			elseif($table_type == ProductsTableType::AUCTION)
			{				
				static $fields4 = array('tenancy','auctioneer_name','auction_date','auction_time','auction_location');
				foreach($fields4 as $f)	if($p[$f] != $p2[$f]) $differences[$f] = $p[$f]."<>".$p2[$f];
			}
			
			if(empty($differences))
			{
				$equal_parsing_results++;
				continue;				
			}
			
			$different_parsing_results++;
			$ds = "";
			foreach($differences as $field=>$v) $ds .= "$field: $v, ";
			Db::Query("INSERT _parsing_comparison SET id='$crawler_id+".$p['id']."', fields='".join(",", array_keys($differences))."', differences='".addslashes($ds)."'");	
		}
		
		static $total_product_count = 0;
		$total_product_count += Db::SmartQuery("DELETE FROM $temp_products_table LIMIT ".RECORDS_BUFFER_LIMIT_NUMBER, ORIGIN_CONNECTION);
		Logger::Write2("Compared: $total_product_count");
	}
	Db::Query("DROP TABLE $temp_products_table", ORIGIN_CONNECTION);
	Logger::Write2("\nno_such_product_id: $no_such_product_id\ndifferent_contents: $different_contents\nnot_parsed_products: $not_parsed_products\nequal_parsing_results: $equal_parsing_results\ndifferent_parsing_results: $different_parsing_results");
}

Logger::Write2("COMPLETED");

?>