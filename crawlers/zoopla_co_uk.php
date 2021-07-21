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
		return Regex::GetFirstValue("@for-sale/details/(.*?)(\&|\?|$)@is", $this->url);
	}	
	
	public static function Restore($seed)
	{
		return new ProductRequest("http://www.zoopla.co.uk/for-sale/details/$seed");
	}		
}

class zoopla_co_uk extends Crawler6_3_sale
{	
	function Initialize()
	{
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 1000);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
		Downloader::SetRequestTimeoutInSecs(120);	
	}
		
	function GetInitialListItems()
	{ 
		return array('https://www.zoopla.co.uk/for-sale/property/england/', 
			'https://www.zoopla.co.uk/for-sale/property/scotland/',
			'https://www.zoopla.co.uk/for-sale/property/wales/',
			'https://www.zoopla.co.uk/for-sale/property/northern-ireland/'
		);
	}
	
	function GetListItemsFromListPage()
	{
		//return Downloader::Regex()->ExtractUrls("@\?pn=\d+@is");
		return Downloader::Xpath()->ExtractUrls("//li[contains(@class, 'PaginationItemNext')]");
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@for-sale/details/\d+@is");
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
		$id = Downloader::Request()->Seed();
		//$image_url = Downloader::Xpath()->ExtractImageUrl("//img[@itemprop='photo']");	
		$image_url = false;	
		//$headline = Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="listing-summary-details"]');
		$headline = Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="price"]')."   <br>   ".Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="title-label"]');
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="page_features_section"]');		
		$address = Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="address-label"]');		
		$agent = Downloader::Xpath()->GetJoinedInnerHtml('//*[@data-testid="agent-details"]/div/p');
		if(preg_match("@(.*?)\<@is", $agent, $res))
			$agent = $res[1];			
		//$agent = Downloader::Xpath()->GetJoinedInnerHtml('//*[@class="dp-sidebar-wrapper__contact"]');
	}
}

Run();

?>