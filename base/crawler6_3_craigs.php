<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

include_once("crawler5/crawler5_1_3.php");
include_once("crawler5/product.php");

class ListItem extends RequestItem
{
	public $Region;
}

abstract class Crawler6_3_craigs extends Crawler6_3
{	
	final protected function GetProductFromProductPage()
	{		
		$url = $this->Response()->Url();//it is gotten here as it can be changed by ParseProductPage()

		//$crawl_parameters = $this->Item()->GetValueFromAncestorItems('Region');
		$crawl_parameters = $this->Item()->Region;	
		$id = $url;
		
		$rc = $this->ParseProductPage($description, $contact);
		if($rc === false) return false;
				
		$rd["description"] = $description;
		$rd["contact"] = $contact;
		$raw_data = json_encode($rd);
		$product = new Product($crawl_parameters, $url, $id, $raw_data);
				
		return $product;
	}
		
	protected function ParseProductPage(&$description, &$contact)
	{
		throw new Exception("TBD");
	}
}

?>