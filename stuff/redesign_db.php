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
$ABSPATH = dirname(__FILE__)."/..";

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/table_routines.php");

process_products_table("products_");
foreach(TableRoutines::GetSaleProductsTables() as $products_table) process_products_table($products_table);
process_products_table("products2_");
foreach(TableRoutines::GetAuctionProductsTables() as $products_table) process_products_table($products_table);
function process_products_table($products_table)
{
	Logger::Write2("Processing $products_table");
	
	Db::Query("ALTER TABLE $products_table ADD `crawled_data` text AFTER `parse_time`, ADD `parsed_data` text AFTER `crawled_data`");
	
	//Db::Query("ALTER TABLE $products_table CHANGE agent agent varchar(256) NOT NULL AFTER `address`");
	
	//Db::Query("ALTER TABLE $products_table CHANGE `features` features SET('basement','gifted_deposit','no_chain','no_stamp_duty','attic','vacant_possession','outbuildings','end_of_terrace','short_lease','under_offer','garage',  'corner_plot',  'planning_permission','!basement','!gifted_deposit','!no_chain','!no_stamp_duty','!attic','!vacant_possession','!outbuildings','!end_of_terrace','!short_lease','!under_offer','!garage',  '!corner_plot',  '!planning_permission') NOT NULL AFTER `status`");
	
	//Db::Query("ALTER TABLE $products_table CHANGE `type` `type` ENUM('bungalow','land','house','flat','commercial','!bungalow','!land','!house','!flat','!commercial')  NOT NULL AFTER `features`");
	
	//Db::Query("ALTER TABLE $products_table CHANGE `tenure` tenure ENUM('freehold','leasehold','share_of_freehold','!freehold','!leasehold','!share_of_freehold') NOT NULL AFTER `features`");
	
	//Db::Query("ALTER TABLE $products_table ADD INDEX  `url_index` (  `url` )");
	
	//Db::Query("ALTER TABLE $products_table ADD `category` ENUM('sale','rental') NOT NULL DEFAULT 'sale' AFTER `status`");
		
	//Db::Query("ALTER TABLE $products_table ADD `features` SET('basement','gifted_deposit','no_chain','no_stamp_duty','attic','vacant_possession','outbuildings','end_of_terrace','short_lease','under_offer','garage','-') NOT NULL AFTER `is_sold`");
	
	//Db::Query("ALTER TABLE $products_table ADD `tenure` ENUM('freehold','leasehold','share_of_freehold','-') NOT NULL AFTER `features`");
		
}
Logger::Write2("DONE");
?>