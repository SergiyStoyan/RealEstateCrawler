<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************
error_reporting(E_ALL);
include_once("crawler6/crawler6_3.php");
include_once("product6_sale.php");

class ListItem extends RequestItem
{
	public $Region;
}

abstract class Crawler6_3_sale extends Crawler6_3
{	
	final protected function GetProductFromProductPage()
	{		
		$url = Downloader::Response()->Url();//it is gotten here as it can be changed by ParseProductPage()

		//$crawl_parameters = $this->Item()->GetValueFromAncestorItems('Region');
		$crawl_parameters = $this->Engine()->Item()->Region;
		
		$rc = $this->ParseProductPage(
			$id,
			$image_url,
			$headline,
			$description,
			$address, 
			$agent
		);
		if($rc === false) return false;
		
		return new ProductSale($crawl_parameters, $url, $id, $image_url, $headline, $description, $address, $agent);
	}
		
	abstract protected function ParseProductPage(
		&$id,
		&$image_url,
		&$headline,
		&$description,
		&$address, 
		&$agent
	);	
}

?>