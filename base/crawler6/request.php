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

//can be inherited
class Request
{		
	final function __construct($url, $post_parameters=null, $is_binary=false)
	{
		$this->url = $url;
		$this->post_parameters = $post_parameters;
		$this->is_binary = $is_binary;
	}
	
	final public function Url()
	{
		return $this->url;
	}	
	protected $url;
	
	final public function IsBinary()
	{
		return $this->is_binary;
	}	
	protected $is_binary;
	
	//if PostParameters is unchangeable then it should be hardcoded
	final public function PostParameters()
	{
		return $this->post_parameters;
	}	
	protected $post_parameters;
		
	static public $IgnoredErrorHttpCodes = null;
	
	final public function Seed()
	{
		if(!$this->seed) 
		{
			$this->seed = $this->get_seed();
			if(!$this->seed) throw new Exception("Seed is empty for $this->url.");
		}
		return $this->seed;
	}	
	protected $seed;
	
	public static $AdditionalHttpHeaders = null;			
	public static $SendCookie = true;
			
	public final static function CreateRequests($urls, $post_parameters=null)
	{
		$urls = (array)$urls;
		$us = array();
		$request_class = get_called_class();
		foreach($urls as $u) $us[] = new $request_class($u, $post_parameters);
		return $us;
	}
	
	/*public final static function RestoreRequests($url_seeds)
	{
		$url_seeds = (array)$url_seeds;
		$ss = array();
		foreach($url_seeds as $s) $ss[] = self::RestoreRequest($s);
		return $ss;
	}*/
	
	/*public final static function AbsolutizeRequests($parent_url, &$requests)
	{
		$requests = (array)$requests;
		$us = array();
		foreach($requests as $r) $us[] = $r->Url();
		$us = Html::GetAbsoluteUrls($parent_url, $us);
		for($i = count($requests) - 1; $i >= 0; $i--)
		{
			$requests[$i]->url = $us[$i];
			$requests[$i]->seed = null;
		}
	}*/
	
	//to override. Try to keep the seed as small as possible. 
	protected function get_seed()
	{
		$seed = $this->Url();
		if($this->PostParameters()) $seed .= ";".http_build_query($this->PostParameters());
		return $seed;
	}	
	
	//to override
	public static function Restore($seed)
	{
		$ss = preg_split("@\;@is", $seed);
		if(isset($ss[1])) parse_str($ss[1], $pps);
		$pps = null;
		$request_class = get_called_class();
		return new $request_class($ss[0], $pps);
	}
}
?>