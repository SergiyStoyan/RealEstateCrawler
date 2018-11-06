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
	const DataBase = "real_estate6_2";
	//const DataBaseHost2 = "87.117.228.105:3311";
	const DataBaseHost2 = "87.117.228.105:3321";
	//const DataBaseHost2 = "87.117.228.105:3320";
	//const DataBase2 = "pt_deals";	
	const DataBase2 = "db764";	
	//const DataBase2 = "db640";	
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 2;
	const ImageDirectory = "c:\\temp\\images6_2";
	const LogDirectory = "c:\\temp\\logs6_2/";
	const CacheDirectory = "c:\\temp\\cache6_2/";
	const LogUrl = "http://87.117.228.105/~crawler/logs6_2";
	const ImageBaseUrl = "http://87.117.228.105/~crawler/images6_2";
	const DefaultImageName = "property-deal-image-coming-soon.jpg";
    //const DataBaseUser = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
	//const DataBasePassword = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
}

class Constants extends Constants_
{
	const DataBaseHost = "localhost";	
	const DataBase = "real_estate6_2";
	//const DataBaseHost2 = "87.117.228.105:3311";
	const DataBaseHost2 = "87.117.228.105:3321";
	//const DataBaseHost2 = "87.117.228.105:3320";
	//const DataBase2 = "pt_deals";	
	const DataBase2 = "db764";	
	//const DataBase2 = "db640";	
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 2;
	const ImageDirectory = "/home/crawler/public_html/images6_2/";
	const ImageBaseUrl = "http://87.117.228.105/~crawler/images6_2";
	const LogDirectory = "/home/crawler/public_html/logs6_2/";
	const LogUrl = "http://87.117.228.105/~crawler/logs6_2";
	const CacheDirectory = "/home/crawler/cache6_2/";
	const DefaultImageName = "property-deal-image-coming-soon.jpg";
    //const DataBaseUser = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
	//const DataBasePassword = "";//must be defined in constants_admin.php and constants_maker.php ::Constants_
}

?>