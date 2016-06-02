<?

//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

class Html
{
	static public function PrepareField($str, $save_format=False)
	{
		$str = preg_replace('/<\!\-\-.*?\-\->/is', '', $str);
		$str = preg_replace('#<script.*?>.*?</script>#is', '', $str);
		$str = html_entity_decode($str);
		$str = preg_replace('/[\n\r\t'.chr(160).chr(194).']/is', ' ', $str);
		if($save_format)
		{
			$str = preg_replace('/<(br|\/tr)\s?[^>]*>/is', '\r\n', $str);
		}
		$str = preg_replace('/<.*?>/is', ' ', $str);
		$str = preg_replace('/\s{2,}/is', ' ', $str);
		return trim($str);
	}
	
	static public function PrepareWebPage($str)
	{
		$str = preg_replace('/<\!\-\-.*?\-\->/is', '', $str);
		/*$str = preg_replace('#<script.*?>.*?</script>#is', '', $str);*/
		$str = preg_replace('/[\n\r\t'.chr(160).chr(194).']/is', ' ', $str);
		$str = preg_replace('/\s{2,}/is', ' ', $str);
		return trim($str);
	}

	static public function PrepareCsvField($str, $save_format=False)
	{
		return preg_replace('/,/is', ' ', self::PrepareField($str, $save_format));
	}

	static public function PrepareTsvLine()
	{
		$ss = array();
		foreach(func_get_args() as $s) $ss[] = preg_replace('/\t/is', ' ', $s);
		return join("\t", $ss)."\n";
	}

	static public function DecodeAndPrepareField($str, $save_format=False)
	{
		return self::PrepareField(self::HtmlDecode($str, $save_format));
	}

	static public function DecodeAndPrepareFields($strs, $save_format=False)
	{
		if(is_array($strs))
		{
			foreach($strs as $k=>$str)
				 $r[$k] = self::DecodeAndPrepareFields($str, $save_format);
		}
		else $r = self::DecodeAndPrepareField(self::HtmlDecode($strs, $save_format));
		return $r;
	}

	static public function PrepareSqlField($str, $save_format=False)
	{
		return addslashes(self::PrepareField($str, $save_format));
	}

	static public function HtmlDecode($htmls)
	{
		if(is_array($htmls))
		{
			foreach($htmls as $key=>$html)
				$htmls2[$key] = self::HtmlDecode($html);
		}
		else $htmls2 = self::HtmlEntityDecode($htmls);
		return $htmls2;
	}
	
	static public function GetAbsoluteUrls($parent_url, $links)
	{
		$links = (array)$links;
		
		self::ParseUrl($parent_url, $host_url, $path, $query, $anchor) or trigger_error("Could not parse url: $parent_url");
						
		$abs_links = array();
		foreach($links as $l)
		{
			$l = self::HtmlEntityDecode($l);
			$l = trim($l);		
			if(preg_match("@^/@i", $l)) $l = self::construct_url($host_url, $l);			
			elseif(preg_match("@^\.\./@i", $l)) $l = self::construct_url($host_url, preg_replace("@/[^/]*$@is", "/", $path)."/".$l); 
			elseif(preg_match("@^\./@i", $l)) $l = self::construct_url($host_url, preg_replace("@/[^/]*$@is", "/", $path)."/".$l); 
			elseif(preg_match('@^\?@is', $l)) $l = self::construct_url($host_url, $path, $l);
			elseif(preg_match("@^\w{3,5}:@i", $l)) $l = self::construct_url($l);
			elseif(!$l) $l = self::construct_url($parent_url);
			else $l = self::construct_url($host_url, preg_replace("@/[^/]*$@", "/", $path).$l);
			
			$abs_links[] = $l;
		}

		return $abs_links;
	}		
			
	static public function ParseUrl($url, &$host_url, &$path, &$query, &$anchor)						
	{
		if(!preg_match("@(\w{3,5}://[^/\?\#]+)([^\?\#]*)([^\#]*)(.*)$@is", $url, $res))	return false;
		$host_url = $res[1];
		$path = $res[2];
		$query = $res[3];
		$anchor = $res[4];
		return true;
	}	
		
	static private function construct_url($host_url, $path=null, $query=null)						
	{
		self::ParseUrl("$host_url$path$query", $host_url, $path, $query, $anchor) or trigger_error("Could not parse url: $host_url$path$query");
		
		while(preg_match("@/+\.\./+@", $path)) $path = preg_replace("@(/+[^/\.]+)?/+\.\./+@", "/", $path, 1);
		while(preg_match("@/+\./+@", $path)) $path = preg_replace("@/+\./+@", "/", $path, 1);
		while(preg_match("@(^|[^:])//+@", $path)) $path = preg_replace("@(^|[^:])//+@", "$1/", $path, 1);		
		
		return "$host_url$path$query";	
	}
	
	static public function HtmlEntityDecode($string)
	{	
		$string = html_entity_decode($string);
    	$string = preg_replace('/&#(\d+);/m', "chr(\\1)", $string); 
    	$string = preg_replace('/&#x([a-f0-9]+);/mi', "chr(0x\\1)", $string);  
		return $string;
	}
		
	static public function GetAbsoluteUrl($parent_url, $link)
	{
		$ls = Html::GetAbsoluteUrls($parent_url, $link);
		if(count($ls) == 1) $ls = trim($ls[0]);
		return $ls;
	}

	static public function GetUrlWithoutRequest($url)
	{
		 preg_match("@(.*?)(\?|\#|$)@is", $url, $res);
		 return $res[1];
	}
}

/*
//TEST CASES
$us = array(
	array('http://www.homesonview.co.uk/Scripts/FindProperty.asp?css=hov&ListType=1&Saletype=1&CompanyID=&Min=&Max=&beds=0&search_area=&Search=Search&firstname=&lastname=&email=&OrderBy=1&PropsPerPage=4', ".///thumb.ashx?cid=BURYASHB&img=lBAP0082905.jpg&w=80&h=60", "http://www.homesonview.co.uk/Scripts/thumb.ashx?cid=BURYASHB&img=lBAP0082905.jpg&w=80&h=60"),
	
	array('http://www.homesonview.co.uk/Scripts/FindProperty/', ".///thumb.ashx?cid=BURYASHB", "http://www.homesonview.co.uk/Scripts/FindProperty/thumb.ashx?cid=BURYASHB"),
	
	array('http://www.homesonview.co.uk/Scripts/FindProperty.asp?css=hov&ListType=1&Saletype=1&CompanyID=&Min=&Max=&beds=0&search_area=&Search=Search&firstname=&lastname=&email=&OrderBy=1&PropsPerPage=4', "../scripts/thumb.ashx?cid=BURYASHB&img=lBAP0082905.jpg&w=80&h=60", "http://www.homesonview.co.uk/Scripts/thumb.ashx?cid=BURYASHB&img=lBAP0082905.jpg&w=80&h=60"),
	
	array('http://www.homesonview.co.uk/Scripts/FindProperty.asp?css=hov', "?q=2", "http://www.homesonview.co.uk/Scripts/FindProperty.asp?q=2"),
	
	array('http://www.homesonview.co.uk/Scripts/FindProperty.asp?css=hov', "?w=qwe&y=7#ggg", "http://www.homesonview.co.uk/Scripts/FindProperty.asp?w=qwe&y=7"),
	
	array('http://www.homesonview.co.uk/Scripts/FindProperty.asp?css=hov', "qqqq/www?e=4", "http://www.homesonview.co.uk/Scripts/qqqq/www?e=4"),
	
	array('http://www.sequencehome.co.uk/buy/search-results?searchType=buy&geographyName=london&radius=5.0&includeSSTC=0', "?index=7&searchType=buy&geographyName=london&radius=5.0&includeSSTC=0", "http://www.sequencehome.co.uk/buy/search-results?index=7&searchType=buy&geographyName=london&radius=5.0&includeSSTC=0"),
	
	array('http://www.numberone4property.co.uk/sale_details_noframe.asp?House=ITL1135&View=1', "../../wwwdata/itlhome/agents/ITL/pictures/ITL11351.jpg", "http://www.numberone4property.co.uk/wwwdata/itlhome/agents/ITL/pictures/ITL11351.jpg"),

	array('http://www.your-move.co.uk/property-for-sale/detached-bungalow-for-sale-fox', "/property-for-sale/detached-bungalow-for-sale-fox&#39;s-halt-newark-road-torksey-lock-lincoln-ln1-2-sale-id-527790025", "http://www.your-move.co.uk/property-for-sale/detached-bungalow-for-sale-fox's-halt-newark-road-torksey-lock-lincoln-ln1-2-sale-id-527790025"),

	array('http://www.your-move.co.uk/', "http://property.com/detached?qwerty#aaa", "http://property.com/detached?qwerty"),

	array('http://www.countrywidepropertyauctions.co.uk/content/Auctions/Auction_Calendar/', "../../../index.php?action=moduleprocess&amp;_process=lot_search&amp;_path=content%2FAuctions%2FAuction_Calendar%2F&amp;_moduleclass=RPW_auction_calendar_obj&amp;_moduleclasspath=classes/modules/rpw/&amp;_moduleid=9ff391c85b6a059a9badf00c5019a2ab&amp;_section=mainContentRow3Column2&amp;auction=CHL120039", "http://www.countrywidepropertyauctions.co.uk/index.php?action=moduleprocess&_process=lot_search&_path=content%2FAuctions%2FAuction_Calendar%2F&_moduleclass=RPW_auction_calendar_obj&_moduleclasspath=classes/modules/rpw/&_moduleid=9ff391c85b6a059a9badf00c5019a2ab&_section=mainContentRow3Column2&auction=CHL120039"),
	
);

foreach($us as $u)
{
	if(strtolower(Html::GetAbsoluteUrl($u[0], $u[1])) != strtolower($u[2])) print("\n<br>ERROR in $u[1]\n<br>".Html::GetAbsoluteUrl($u[0], $u[1])."\n<br>!=\n<br>".$u[2]);
}
print("\n<br>END");
*/

?>