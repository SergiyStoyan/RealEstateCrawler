<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

if(is_readable(dirname(__FILE__)."/constants_admin.php")) include_once("constants_admin.php");
else include_once("constants_maker.php");

//these constants require multi-user permission 
//chmod 751

class Constants extends Constants_
{
	const DataBaseHost = "127.0.0.1";	
	const DataBase = "real_estate2";
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 8;
	const ImageDirectory = "/home/crawler/images2/";
	const ImageBaseUrl = "http://92.205.23.180/images2";
	const LogDirectory = "/home/crawler/logs2/";
	const LogUrl = "http://92.205.23.180/logs2";
	const CacheDirectory = "/home/crawler/cache2/";
	const DefaultImageName = "property-deal-image-coming-soon.jpg";	
    //const DataBaseUser = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
	//const DataBasePassword = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
}

?>