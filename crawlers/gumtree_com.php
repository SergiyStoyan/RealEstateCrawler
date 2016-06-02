<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************

include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class gumtree_com extends Crawler6_3_sale
{		
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 1000);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
	}
		
	function GetInitialListItems()
	{ 
		return 'http://www.gumtree.com/property-for-sale';
	}
	
	protected function GetListItemsFromListPage()
	{	
		return Downloader::Xpath()->ExtractUrls('//*[@class="pagination-next"]');
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Xpath()->ExtractUrls('//li//*[@class="listing-link"]');
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
		//$image_url =	Xpath_ExtractImageUrl_orFALSE($this->Xpath(),"//img[contains(@alt,'Picture 1')]");	
		$image_url = false;	
		$headline = Downloader::Xpath()->GetJoinedInnerHtml('//header[@class="clearfix space-mbs"]');
		$description = Downloader::Xpath()->GetJoinedInnerHtml("//*[@class='ad-description']");
		$address = Downloader::Xpath()->GetJoinedInnerHtml("//*[@class='ad-location truncate-line set-left']");	
		$agent = false;
	}
}

Run();

?>