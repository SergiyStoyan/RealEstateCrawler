<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

include_once("crawler6/product.php");

class ProductSale extends Product
{	
	function __construct($crawl_parameters, $url, $id, $image_url, $headline, $description, $address, $agent)
	{								
		if($image_url !== false and !preg_match("@\w@is", $image_url)) $this->errors[] = "image_url is empty";
		if($headline !== false and !preg_match("@\w@is", $headline)) $this->errors[] = "headline is empty";
		if($description !== false and !preg_match("@\w@is", $description)) $this->errors[] = "description is empty";
		if($address !== false and !preg_match("@\w@is", $address)) $this->errors[] = "address is empty";
		if($agent !== false and !preg_match("@\w@is", $agent)) $this->errors[] = "agent is empty";
		
		$rd["image_url"] = $image_url;
		$rd["headline"] = $headline;
		$rd["description"] = $description;
		$rd["address"] = $address;
		$rd["agent"] = $agent;
		
		parent::__construct($crawl_parameters, $url, $id, $rd);
	}
}

?>