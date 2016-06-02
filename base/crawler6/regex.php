<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../../";

include_once("$ABSPATH/common/html_utilities.php");

class Regex
{
	function __construct($page, $response_url)
	{
		$this->page = $page;
		$this->response_url = $response_url;		
	}
	private $page;
	private $response_url;
	
	public function ExtractUrls($filter_regex=FALSE)
	{					
		$links = array();
        preg_match_all("@\shref=[\"']([^<>\"'\s]*)@is", $this->page, $res);
		$links = $res[1];
				
		if($filter_regex) $links = preg_grep($filter_regex, $links);
		
		//print_r($links);exit();
		$links = Html::GetAbsoluteUrls($this->response_url, $links);
		return $links;	
	}
	
	public function ExtractImageUrls($filter_regex=FALSE)
	{					
		$links = array();
        preg_match_all("@\ssrc=[\"']([^<>\"'\s]*)@is", $this->page, $res);
		$links = $res[1];
				
		if($filter_regex) $links = preg_grep($filter_regex, $links);
		
		$links = Html::GetAbsoluteUrls($this->response_url, $links);
		
		return $links;
	}
	
	public function ExtractImageUrl($filter_regex=FALSE)
	{		
		$is = self::ExtractImageUrls($filter_regex);
		return $is[0];	
	}
	
	public function ExtractValueFromResponseUrl($regex_chain)
	{
		return self::GetFirstValue($regex_chain, $this->response_url);
	}
	
	static public function ExtractUrlQueryValue($query_key, $url)
	{
		return self::GetFirstValue("@[\?\&]$query_key=(.*?)(\&|$)@is", $url);
	}
	
	public function ExtractValue($regex_chain)
	{
		return self::GetFirstValue($regex_chain, $this->page);
	}
	
	public function GetAbsoluteUrlFromValue($regex_chain)
	{
		return Html::GetAbsoluteUrl($this->response_url, self::ExtractValue($regex_chain));
	}
	
	public static function GetFirstValue($regex_chain, $str)
	{
		$regex_chain = (array)$regex_chain;
		
		foreach($regex_chain as $regex)
		{//Logger::Write("@@@$regex");
			if(!preg_match($regex, $str, $res))
				return NULL;
			$str = $res[1];			
		}
		
		return $str;
	}
}

?>