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

include_once("$ABSPATH/common/logger.php");
include_once("$ABSPATH/common/html_utilities.php");

//libxml_use_internal_errors(false);
Logger::$IgnoreMessagePatterns["@DOMDocument::loadHTML@is"] = "";

class Xpath
{
	function __construct($page, $response_url)
	{
		$this->page = $page;
		$this->response_url = $response_url;		
	}
	private $page;
	private $response_url;
	
	public function GetXpath()
	{
		if(!$this->xpath) $this->xpath = new DOMXPath($this->GetDocument());			
		return $this->xpath;
	}
	private $xpath;
	
	public function GetDocument()
	{
		if(!$this->document)
		{
			$this->document = new DomDocument();
			@$this->document->loadhtml($this->page);
		}				
		return $this->document;
	}
	private $document;
		
	/*error: child's' text is joined with no space
	public function GetValues($xpath)
	{
		$ns = $this->GetXpath()->query($xpath);
		foreach($ns as $n) $vs[] = $n->nodeValue;
		return $vs;
	}*/	
	
	public function GetValues($xpath)
	{
		$ns = $this->GetXpath()->query($xpath);
		foreach($ns as $n) $vs[] = Html::PrepareField($this->GetDocument()->saveHTML($n));
		return $vs;
	}	
	
	/*error: child's' text is joined with no space
	public function GetJoinedValue($xpath, $separator="\n\n")
	{
		$ns = $this->GetXpath()->query($xpath);
		foreach($ns as $n) $s .= $n->nodeValue.$separator;
		return $s;
	}*/	
	
	public function GetJoinedValue($xpath, $separator="\n\n")
	{
		$s = $this->GetJoinedOuterHtml($xpath, $separator);
		return Html::PrepareField($s);
	}		
	
	public function GetJoinedOuterHtml($xpath, $separator="\n\n")
	{
		$ns = $this->GetXpath()->query($xpath);
		$s = "";
		foreach($ns as $n) $s .= $this->GetDocument()->saveHTML($n).$separator;
		return $s;
	}
	
	public function GetJoinedInnerHtml($xpath, $separator="\n\n")
	{
		$s = $this->GetJoinedOuterHtml($xpath, $separator);
		$s = preg_replace("@^\s*<.*?>|<[^>]*?>\s*$@is", "", $s);
		return $s;
	}
	
	public function GetAttributeValue($xpath, $attribute)
	{
		$ns = $this->GetXpath()->query($xpath);
		if(!$ns->item(0)) return null;
		return $ns->item(0)->attributes->getNamedItem($attribute)->nodeValue;
	}			
	
	//return any hrefs found within xpath
	public function ExtractUrls($xpath)
	{
		$xpath_nodes = $this->GetXpath()->query($xpath);
		$urls = array();
		$this->_ExtractUrls($xpath_nodes, $urls);
		return Html::GetAbsoluteUrls($this->response_url, $urls);
	}
	
	private function _ExtractUrls($xpath_nodes, &$urls)
	{
		if(!$xpath_nodes) return;		
		foreach($xpath_nodes as $n) 
		{
			if(strtolower($n->nodeName) == "a") $urls[] = $n->attributes->getNamedItem("href")->nodeValue;
			$this->_ExtractUrls($n->childNodes, $urls);
		}
	}			
	
	//return the first src found within xpath
	public function ExtractImageUrl($xpath)
	{
		$xpath_nodes = $this->GetXpath()->query($xpath);
		return Html::GetAbsoluteUrl($this->response_url, $this->_ExtractImageUrl($xpath_nodes));
	}
	
	private function _ExtractImageUrl($xpath_nodes)
	{		
		if(!$xpath_nodes) return;
		foreach($xpath_nodes as $n) 
		{
			if(strtolower($n->nodeName) == "img") 
			{
				$src_i = $n->attributes->getNamedItem("src");
				if($src_i) return $src_i->nodeValue;
			}
			$image_url = $this->_ExtractImageUrl($n->childNodes);
			if($image_url) return $image_url;
		}
	}
	
	public function ExplodeDocument($xpath) 
	{
		$xpaths = array();
		foreach($this->GetXpath()->query($xpath) as $xn) $xpaths[] = new Xpath($this->GetDocument()->saveHTML($xn), $this->response_url);
		return $xpaths;
	}		
}

/*$xp = new Xpath("<div id='z'>wwww<div id='x'>qqqq</div>eee<div id='x'>rrr</div>ttt</div>", "");
print_r($xp->GetJoinedOuterHtml("//div[@id='z']"));*/

?>