<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************

include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class ProductRequest extends Request 
{
	static public $IgnoredErrorHttpCodes = array(404);
}

class ListNextRequest extends Request 
{
	static public $IgnoredErrorHttpCodes = array(404);
}

class ListRequest extends Request 
{
	static public $IgnoredErrorHttpCodes = array(404);
}

class look4aproperty_com extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_COMPLETE_SITE_CRAWLING();
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 5000);
	}
		
	function GetInitialListItems()
	{ 
		Downloader::Get2('http://www.look4aproperty.com/sale-property/') or $this->Engine()->ExitOnError("Cannot get initial page.");
		$areas = Downloader::Xpath()->ExtractUrls("//div[@id='side']/ul[1]");
		$us = array();
		foreach($areas as $a) 
		{
			Downloader::Get2($a) or $this->Engine()->ExitOnError("Cannot get an area page.");
			$us = array_merge($us, Downloader::Xpath()->ExtractUrls("//a[text()='Property For Sale']"));
		}
		return $us;
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@^/property/\d+@i");
	}
				
	function ParseProductPage(
		&$id,
		&$image_url,
		&$headline,
		&$description,
		&$address, 
		&$agent
	)
	{	
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@/(\d+)$@is");
		//$image_url = Downloader::Xpath()->ExtractImageUrl("//*[@id='mainImg']");	
		$image_url = false;	
		$headline =	Downloader::Xpath()->GetJoinedInnerHtml("//*[@id='main']/h1").' '.Downloader::Xpath()->GetJoinedInnerHtml("//*[@id='property']/h2/span[@class='price']/text()") ;
		$description = Downloader::Xpath()->GetJoinedInnerHtml("//*[@id='propertyDesc']");
		$address = Downloader::Xpath()->GetJoinedInnerHtml("//*[@id='main']/h1/span");
		$agent = Downloader::Xpath()->GetJoinedInnerHtml("//*[@id='agent' and ./div[@class='name']]//text()");
	}
}

Run();

?>