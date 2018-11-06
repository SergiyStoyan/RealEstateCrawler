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

include_once("$ABSPATH/base/crawler6/crawler6.php");

abstract class Crawler6_2 extends Crawler6
{				
	public function TestPage($url)
	{
		$request = new ListRequest($url);
		if(!$this->Download($request)) return;
				
		$products = $this->GetProductsFromListPage();
		if($products === null)
		{
			Logger::Error_("The product parser returned nothing.");	
			return;			
		}
		if(!is_array($products)) $products = array($products);
		Logger::Write("GetProductsFromListPage returned products:".count($products));
		$i = 0;
		foreach($products as $product)
		{
			$i++;
			if(get_class($product) != Product::ClassName()) 
			{
				Logger::Error("GetProductsFromListPage returned an object that is not ".Product::ClassName());
				continue;
			}		
			$fs2vs = $product->GetDbFields2ValuesForTest();
			Logger::Write("Parsed results for product $i:\n".Misc::GetArrayAsString($fs2vs));
		}
				
		Logger::Write("GetListItemsFromListPage:\n".Misc::GetArrayAsString($this->GetListItemsFromListPage()));
	}
				
	final public function Init()
	{		
		parent::__construct($engine);
			
		if(!class_exists('ListRequest'))
		{
			class_alias('Request', 'ListRequest') or Logger::Quit("Could not alias Request class");
			Logger::Write("ListRequest class is Request class");
		}
		if(!class_exists('ListNextRequest'))
		{
			class_alias('Request', 'ListNextRequest') or Logger::Quit("Could not alias Request class");
			Logger::Write("ListNextRequest class is Request class");
		}	
				
		$this->Initialize();
		$this->initialize_counters();
	}
	
	private function initialize_counters()
	{				
		$this->nolist_error_going_together_counter = new Counter("NOLIST_ERROR_GOING_TOGETHER_COUNT", $this->Get('MAX_NOLIST_ERROR_GOING_TOGETHER_COUNT'));
		$this->noproduct_error_going_together_counter = new Counter("NOPRODUCT_ERROR_GOING_TOGETHER_COUNT", $this->Get('MAX_NOPRODUCT_ERROR_GOING_TOGETHER_COUNT'));
	}
	protected $nolist_error_going_together_counter;
	protected $noproduct_error_going_together_counter;
		
	protected function Initialize_COMPLETE_SITE_CRAWLING()
	{			
		$configuration = $this->get_base_configuration();
		$configuration['DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND'] = false;
		$configuration['QUEUE_NAMES2SCHEMA'] = array(
			"LIST_NEXT"=>array('PROCESSOR'=>'LIST_Processor', 'ITEM_CLASS'=>'ListNextItem', 'MIN_ITEM_NUMBER'=>1, 'MAX_ITEM_ERROR_GOING_TOGETHER_COUNT'=>1, 'RESTORE_ERROR_ITEMS_AS_NEW'=>true, 'DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED'=>false), 
			"LIST"=>array('PROCESSOR'=>'LIST_Processor', 'ITEM_CLASS'=>'ListItem', 'MIN_ITEM_NUMBER'=>1, 'MAX_ITEM_ERROR_GOING_TOGETHER_COUNT'=>1, 'RESTORE_ERROR_ITEMS_AS_NEW'=>true, 'DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED'=>false)
		);
		
		$this->Set(null, $configuration);
	}
	
	protected function Initialize_ONLY_NEW_PRODUCTS_CRAWLING()
	{				
		$configuration = $this->get_base_configuration();	
		$configuration['DROP_ITEM_BRANCH_WHEN_OLD_PRODUCT_FOUND'] = array('TRUNK_QUEUE_NAME'=>"LIST", 'DELETE_PRODUCTS_OLDER_THAN_DAYS'=>152);
		$configuration['QUEUE_NAMES2SCHEMA'] = array(
			"LIST_NEXT"=>array('PROCESSOR'=>'LIST_Processor', 'ITEM_CLASS'=>'ListNextItem', 'MIN_ITEM_NUMBER'=>1, 'MAX_ITEM_ERROR_GOING_TOGETHER_COUNT'=>1, 'RESTORE_ERROR_ITEMS_AS_NEW'=>true, 'DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED'=>array('MAX_ITEM_NUMBER'=>9, 'TRUNK_QUEUE_NAME'=>'LIST')), 
			"LIST"=>array('PROCESSOR'=>'LIST_Processor', 'ITEM_CLASS'=>'ListItem', 'MIN_ITEM_NUMBER'=>1, 'MAX_ITEM_ERROR_GOING_TOGETHER_COUNT'=>1, 'RESTORE_ERROR_ITEMS_AS_NEW'=>true, 'DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED'=>false)
		);		
		
		$this->Set(null, $configuration);
	}
	
	public function Begin()
	{		
		$this->get_items_and_add2queue(null, 'GetInitialListItems', "ListItem", "ListRequest", "LIST", $this->nolist_error_going_together_counter);
	}
	
	public function End()
	{			
		foreach($this->Get('QUEUE_NAMES2SCHEMA') as $queue_name=>$schema)
		{
			$min = $schema['MIN_ITEM_NUMBER'];
			$in = $this->Engine()->GetTotalItemCountInQueue($queue_name);
			if($in < $min) $this->Engine()->ExitOnError("crawl_list_pages found too few product urls: $in < $min");
		}
					
		$last_list_state = $this->GetLastItemStateInQueue("LIST");
		if(!$last_list_state) $this->ExitOnError("No LIST item was found at the end.");
		if($last_list_state == 'error') $this->ExitOnError("The last LIST item was completed with error; most likely that means the site was not crawled completely.");
		
		$last_list_state = $this->GetLastItemStateInQueue("LIST_NEXT");
		if(!$last_list_state) $this->ExitOnError("No LIST_NEXT item was found at the end.");
		if($last_list_state == 'error') $this->ExitOnError("The last LIST_NEXT item was completed with error; most likely that means the site was not crawled completely.");
	}	
		
	public function LIST_Processor(Item $item)
	{			
		//it is assumed that Item always contains 'Seed'
		$request = ListRequest::Restore($item->Seed);
		return $this->_LIST_Processor($item, $request);
	}	
		
	public function LIST_NEXT_Processor(Item $item)
	{			
		$request = ListNextRequest::Restore($item->Seed);
		return $this->_LIST_Processor($item, $request);
	}
	
	public function _LIST_Processor(Item $item, $request)
	{			
		if(!Downloader::Get($request)) return false;
		if(!Downloader::Response()->Page()) return true;					
		
		$products = $this->GetProductsFromListPage();
		if($products === null) 
		{
			Logger::Error_("The product parser returned nothing.");			
			$this->noproduct_error_going_together_counter->Increment();
			return false;
		}
		if(!is_array($products)) $products = array($products);
		if(!count($products)) 
		{
			Logger::Warning_("No product was returned by GetProductsFromListPage.");
			$this->noproduct_error_going_together_counter->Increment();
		}
		else 
		{
			foreach($products as $p) $this->SaveProduct($p);
			$this->noproduct_error_going_together_counter->Reset();
		}	
		
		$this->get_items_and_add2queue($item, 'GetListItemsFromListPage', "ListNextRequest", "LIST_NEXT", $this->nolist_error_going_together_counter);
		
		return true;
	}
	
	//**************************************************************************************************************
	//functions to be overridden
	//**************************************************************************************************************
	protected function Initialize()
	{				
	}
			
	abstract protected function GetInitialListItems();
			
	protected function GetListItemsFromListPage()
	{		
		$urls = array();
		$ns = Downloader::Xpath()->GetXpath()->query("//a");
		foreach($ns as $n) 
		{			
			if($title = $n->attributes->getNamedItem("title") and preg_match("@^\s*Next\s*Page\s*$@is", $title->nodeValue)
				or preg_match("@^(\s|&nbsp;)*Next(\s|&nbsp;)*(Page(\s|&nbsp;)*)?(((&gt;)+|(»)+)(\s|&nbsp;)*)?$@is", $n->textContent)
				or preg_match("@^(\s|&nbsp;)*((&gt;|>)+|(»)+)(\s|&nbsp;)*$@is", $n->textContent)
			) $urls[] = $n->attributes->getNamedItem("href")->nodeValue;
		}
		return Downloader::Response()->GetAbsoluteUrls($urls);
	}
	
	abstract protected function GetProductsFromListPage();
}

?>