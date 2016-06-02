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
class Constants extends Constants_
{
	const DataBaseHost = "localhost";	
	const DataBase = "real_estate";
	//const DataBaseHost2 = "87.117.228.105:3311";
	//const DataBaseHost2 = "87.117.228.105:3321";
	const DataBaseHost2 = "87.117.228.105:3320";
	//const DataBase2 = "pt_deals";	
	//const DataBase2 = "db764";	
	const DataBase2 = "db640";	
	const AdminEmail = "sergey.stoyan@gmail.com";
	const CrawlerProcessMaxNumber = 10;
	//const ImageDirectory = "c:\\temp\\images";
	const ImageDirectory = "/home/crawler/public_html/images/";
	const ImageDirectory2 = "/home/crawler/public_html/images2/";//inverted images
	const LogDirectory = "/home/crawler/public_html/logs/";
	const CacheDirectory = "/home/crawler/cache/";
	const PdfDirectory = "/home/crawler/pdfs/";
	const LogUrl = "http://87.117.228.105/~crawler/logs";	
	const ImageBaseUrl = "http://87.117.228.105/~crawler/images";  
	const DefaultImageName = "property-deal-image-coming-soon.jpg";
}

?>