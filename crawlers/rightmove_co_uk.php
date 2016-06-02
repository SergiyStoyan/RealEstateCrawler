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
	protected function get_seed()
	{
		return Regex::GetFirstValue("@property-for-sale/property-(\d+)@is", $this->url);
	}	
	
	public static function Restore($seed)
	{
		return new ProductRequest("http://www.rightmove.co.uk/property-for-sale/property-$seed.html");
	}		
}

class rightmove_co_uk extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 100);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
	}
	
	function GetInitialListItems()
	{	
		Downloader::Get2("http://www.rightmove.co.uk/major-cities.html") or $this->Engine()->ExitOnError("Cannot get initial page.");
		$us = Downloader::Regex()->ExtractUrls("@property-for-sale/\w+\.html$@i");
		foreach($us as $u) $init_urls[] = "$u?sortType=6";
		//Logger::Write2($init_urls);
		return $init_urls;
	}
	
	function GetListItemsFromListPage()
	{
		$index = Downloader::Regex()->ExtractValue('@"next":"(\d+)"@i');
		$u = preg_replace('@&index=\d+@', '', Downloader::Response()->Url()).'&index='.$index;
		//Logger::Warning($u);
		return $u;
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@property-for-sale/property-\d{2,}\.html$@i");
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
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@property-(\d+)\.html@is");
		//$image_url = Downloader::Xpath()->ExtractImageUrl("//img[@class='js-gallery-main']");
		$image_url = false;
		$headline =	Downloader::Xpath()->GetJoinedInnerHtml('//div[@class="row one-col property-header"]');		
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="description"]');				
		$address = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="primaryContent"]//div[@class="left"]//address');				
		$agent = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="secondaryAgentDetails" or @id="requestdetails"]');
	}
}

Run();

?>