<?
include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class thehouseshop_com extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 100);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
		//Downloader::$UserAgent = "Mozilla/5.0";
		//Downloader::$AdditionalHttpHeaders = array("Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3");
	}

	function GetInitialListItems()
	{
		return "https://www.thehouseshop.com/property-for-sale";
	}
	
	function GetListItemsFromListPage()
	{
		return Downloader::Xpath()->ExtractUrls('//a[@class="btn btn-ths-gray btn-ths-size-sm btn-next"]');
	}		
		
	function GetProductItemsFromListPage()
	{
		return Downloader::Xpath()->ExtractUrls('//a[@class="btn btn-ths-blue btn-ths-size-sm btn-details"]');
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
		//$image_url = Downloader::Xpath()->ExtractUrls("//*[@id='galleria']");
		$image_url = false;
		$headline =	Downloader::Xpath()->GetJoinedInnerHtml('//div[@class="headline"]');				
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="property-details"]');		
		$address = 	Downloader::Xpath()->GetJoinedInnerHtml('//*[@class="address"]');
		$agent = false;
	}
}

Run();

?>