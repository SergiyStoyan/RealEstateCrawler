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

//process_products_table("test4");
foreach(TableRoutines::GetAuctionProductsTables() as $products_table) process_products_table($products_table);
function process_products_table($products_table)
{
	Db::Query("DELETE FROM $products_table WHERE _state='deleted'");
}

?>