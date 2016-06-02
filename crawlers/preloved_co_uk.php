<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************

include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class preloved_co_uk extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 1000);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
	}
		
	function GetInitialListItems()
	{ 
		return 'http://www.preloved.co.uk/search?keyword=&orderBy=mostRecent&location=&lat=&lon=&distance=&sectionId=3381&minimumPrice=&maximumPrice=&advertType=forsale&advertiserType=&promotionType=';
	}
	
	function GetListItemsFromListPage()
	{
		return Downloader::Xpath()->ExtractUrls('//*[@id="pagination-next-page"]');
	}	
			
	function GetProductItemsFromListPage()
	{
		return Downloader::Xpath()->ExtractUrls('//div[@itemid="#product"]');
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
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@/show/(\d+)@is");
		//$image_url = Downloader::Xpath()->ExtractImageUrl('//li[@data-parent="classified-media-slides"]');	
		$image_url = false;
		$headline =	Downloader::Xpath()->GetJoinedOuterHtml('//div[@class="classified__content"]//header');		
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//article[@class="classified__content__section"]');
		$address = Downloader::Xpath()->GetJoinedInnerHtml('//span[@data-test-element="advert-location"]');
		$agent = false;
	}
}

Run();

?>
?>