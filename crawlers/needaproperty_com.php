<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************

include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class needaproperty_com extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 1000);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
	}
			
	function GetInitialListItems()
	{
		return "http://www.needaproperty.com/property-search/type/for-sale";
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@property/for-sale/@is");
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
		$id = Downloader::Regex()->ExtractValueFromResponseUrl("@.+\-(.+?)$@i");
		//$image_url = Downloader::Xpath()->ExtractImageUrl("//div[@class='item active']");	
		$image_url = false;	
		$headline =	Downloader::Xpath()->GetJoinedInnerHtml("//div[@class='module-title']")." ".Downloader::Xpath()->GetJoinedInnerHtml("//p[@class='price']");		
		$description = Downloader::Xpath()->GetJoinedOuterHtml("//div[@id='details-overview']/div[@class='sub-module-content']/h3")." ".Downloader::Xpath()->GetJoinedOuterHtml("//div[@id='details-overview']/div[@class='sub-module-content']/p")." ".Downloader::Xpath()->GetJoinedOuterHtml("//div[@id='details-overview']/div[@class='sub-module-content']//div[@class='full']");
		$address = Downloader::Xpath()->GetJoinedInnerHtml("//div[@class='module-title']/small");
		$agent = Downloader::Xpath()->GetJoinedOuterHtml("//div[@class='module module-two agent-details auto-mobile-collapse']//address");
	}
}

Run();

?>