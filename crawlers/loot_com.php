<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************

include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class loot_com extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 1000);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
	}
		
	function GetInitialListItems()
	{ 
		return array(
			'http://loot.com/property/flats-and-apartments-for-sale', 
			'http://loot.com/property/houses-for-sale'
		);
	}
	
	protected function GetListItemsFromListPage()
	{	
		$urls = array();
		$ns = Downloader::Xpath()->GetXpath()->query("//a[@class='next']");		
		foreach($ns as $n) 
		{		
			if(preg_match("@^(\s|&nbsp;)*Next@is", $n->textContent)) $urls[] = $n->attributes->getNamedItem("href")->nodeValue;
		}
		return Downloader::Response()->GetAbsoluteUrls($urls);
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@/\(f\)/search$@is");
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
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@-(\d+)ad/@is");
		//$image_url = preg_replace("@\?.*@is", "", $this->Xpath()->ExtractImageUrl("//div[@id='slideshow']"));	
		$image_url = false;	
		$headline =	Downloader::Xpath()->GetJoinedOuterHtml("//div[@id='ad-main-info']/div[@class='float-break']");
		$description = Downloader::Xpath()->GetJoinedOuterHtml("//div[@class='ad-details-description-block']");
		preg_match("@Location\s*:\s*</b>(.*?)<@is", $headline, $m) and $address = $m[1];
		preg_match("@Address\s*:(.*?)<@is", $description, $m) and $address .= " ".$m[1];
		$agent = false;
		//$publish_date =	Downloader::Xpath()->GetJoinedInnerHtml("//div[@class='ad-date']");
	}
}

Run();

?>