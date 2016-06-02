<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        17 December 2014
//********************************************************************************************
//$argv[] = "-d";
include_once(dirname(__FILE__)."/../base/crawler6_3_sale.php");

class ProductRequest extends Request
{
	protected function get_seed()
	{
		return Regex::GetFirstValue("@/vebra/property/(\d+)$@is", $this->url);
	}	
	
	public static function Restore($seed)
	{
		return new ProductRequest("http://www.vebra.com/vebra/property/$seed");
	}		
}

class vebra_com extends Crawler6_3_sale
{	
	function Initialize()
	{
		//$curler = new Curler(false, true, Constants::CacheDirectory);
		//$page = $curler->GetPage("http://www.vebra.com/vebra/property/search/results/0/0/0/Wiltshire/1/20000/3000000/0/0/0/2/0/1", true);
		//Logger::Write2($page);exit;
		$this->Initialize_ONLY_NEW_PRODUCTS_CRAWLING();
		$this->Set('QUEUE_NAMES2SCHEMA/PRODUCT/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED/MAX_ITEM_NUMBER', 10);
		$this->Set('TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS', 10000);
		//Downloader::$UserAgent = "Mozilla/5.0"; 
		Downloader::$UseCookie = false;
	}
	
	function GetInitialListItems()
	{
		Downloader::Get2("http://www.vebra.com/vebra/buying/directory/england") or $this->Engine()->ExitOnError("Cannot get initial page.");
		preg_match_all("@/vebra/buying/directory/(.+?)[\"\']@i", Downloader::Response()->Page(), $ms) or $this->Engine()->ExitOnError("Cannot get counties from the page.");
		//print_r($ms);
		$ms = &$ms[1];
		$init_urls = array();
		for($i = count($ms) - 1; $i >= 0; $i--) $init_urls[] = "http://www.vebra.com/vebra/property/search/results/0/0/0/".$ms[$i]."/1/20000/3000000/0/0/0/2/0/1";
		//print_r($init_urls);
		return $init_urls;
	}
	
	protected function GetListItemsFromListPage()
	{	
		$urls = array();
		$ns = Downloader::Xpath()->GetXpath()->query("//a");
		foreach($ns as $n) 
		{			
			if($title = $n->attributes->getNamedItem("title") and preg_match("@^\s*Next\s*Page\s*$@is", $title->nodeValue)) $urls[] = $n->attributes->getNamedItem("href")->nodeValue;
		}
		return Downloader::Response()->GetAbsoluteUrls($urls);
	}
	
	function GetProductItemsFromListPage()
	{
		return Downloader::Regex()->ExtractUrls("@/vebra/property/\d+@is");
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
		$headline = Downloader::Xpath()->GetJoinedInnerHtml('//*[@id="dtprop"]/h1');		
		$description = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="dtdesc" or @id="dtintrodesc"]');		
		$address = Downloader::Xpath()->GetJoinedInnerHtml('//*[@id="dtprop"]/h1/span[@class="address"]');		
		$agent = Downloader::Xpath()->GetJoinedInnerHtml('//div[@id="agdetails"]');
	}
}

Run();
?>