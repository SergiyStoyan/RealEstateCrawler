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

//these constants require low-level permission
class Constants1 extends Constants_
{
	const DataBaseHost = "localhost";	
	const DataBase = "real_estate6";
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 8;
	const ImageDirectory = "c:\\temp\\images6";
	const LogDirectory = "c:\\temp\\logs6/";
	const CacheDirectory = "c:\\temp\\cache6/";
	const LogUrl = "http://87.117.228.105/~crawler/logs6";
	const ImageBaseUrl = "http://87.117.228.105/~crawler/images6";
	const DefaultImageName = "property-deal-image-coming-soon.jpg";
    //const DataBaseUser = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
	//const DataBasePassword = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
}

class Constants extends Constants_
{
	const DataBaseHost = "127.0.0.1";	
	const DataBase = "real_estate";
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 8;
	const ImageDirectory = "/home/crawler/public_html/images/";
	const ImageBaseUrl = "http://87.117.228.105/~crawler/images";
	const LogDirectory = "/home/crawler/public_html/logs/";
	const LogUrl = "http://87.117.228.105/~crawler/logs";
	const CacheDirectory = "/home/crawler/cache/";
	const DefaultImageName = "property-deal-image-coming-soon.jpg";	
    //const DataBaseUser = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
	//const DataBasePassword = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
}

?>