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
		return Regex::GetFirstValue("@properties/(\d+)@is", $this->url);
	}	
	
	public static function Restore($seed)
	{
		return new ProductRequest("https://www.rightmove.co.uk/properties/$seed");
	}		
}

class rightmove_co_uk extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 100);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
		//$this->Set('HTTP_REQUEST_TIMEOUT_IN_SECS', 120);
		Downloader::SetRequestTimeoutInSecs(120);	
	}
	
	function GetInitialListItems()
	{	
		Downloader::Get2("https://www.rightmove.co.uk/major-cities.html") or $this->Engine()->ExitOnError("Cannot get initial page.");
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
		return Downloader::Regex()->ExtractUrls("@properties/\d{2,}($|\#)@i");
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
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@properties/(\d+)@is");
		//$image_url = Downloader::Xpath()->ExtractImageUrl("//img[@class='js-gallery-main']");
		$image_url = false;
		
		$headline =	Downloader::Xpath()->GetAttributeValue('//meta[@property="og:description"]', 'content');
			
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//article[./div/h2[contains(text(), "description")]]');
			
		//$address = Downloader::Xpath()->GetJoinedInnerHtml('//div[@itemprop="address"]');
		$address = Downloader::Xpath()->GetJoinedInnerHtml('//head/title');
		
		$agent = Downloader::Xpath()->GetJoinedInnerHtml('//div[./p[contains(text(), "MARKETED BY")]]/descendant::a[1]');
	}
}

Run();

?>