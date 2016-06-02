<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../..";

include_once("$ABSPATH/base/crawler6/regex.php");
include_once("$ABSPATH/base/crawler6/xpath.php");

final class Response
{			
	final function __construct(Request $request, $page, $response_url, $response_code, $cached)
	{
		$this->request = $request;
		$this->code = $response_code;						
		if($response_code != 200)
		{
			$class = $this->request;
			if(!$this->code or !$class::$IgnoredErrorHttpCodes or !in_array($this->code, $class::$IgnoredErrorHttpCodes)) $this->is_error = true;			
		}
		$this->cached = $cached;
		if(!$page) return;
		$this->page = Html::PrepareWebPage($page);
		$this->url = $response_url;
		$this->regex = new Regex($this->page, $this->url);
		$this->xpath = new Xpath($this->page, $this->url);
	}
	
	final public function Page()
	{
		return $this->page;
	}
	private $page;
	
	final public function Url()
	{
		return $this->url;
	}
	private $url;
	
	final public function Code()
	{
		return $this->code;
	}
	private $code;
	
	final public function Cached()
	{
		return $this->cached;
	}
	private $cached;
	
	final public function Request()
	{
		return $this->request;
	}
	private $request;
		
	final public function Regex()
	{
		return $this->regex;
	}
	private $regex; 
	
	final public function Xpath()
	{
		return $this->xpath;
	}
	private $xpath;
	
	//used to ignore certain error http codes	
	final public function IsError()
	{
		return $this->is_error;
	}
	private $is_error = false;
	
	final public function GetAbsoluteUrl($url)
	{
		return Html::GetAbsoluteUrl($this->url, $url);
	}
	
	final public function GetAbsoluteUrls($urls)
	{
		return Html::GetAbsoluteUrls($this->url, $urls);
	}
}
?>