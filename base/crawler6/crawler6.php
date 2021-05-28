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

//include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/misc.php");
include_once("$ABSPATH/base/crawler6/product.php");
include_once("$ABSPATH/base/crawler6/request.php");
include_once("$ABSPATH/base/crawler6/item.php");
include_once("$ABSPATH/base/crawler6/engine6.php");

class RequestItem extends Item
{	
	public $Seed;
}

abstract class Crawler6 extends Crawler
{
	//helper function		
	final protected function get_base_configuration()
	{			
		$configuration['DROP_BROKEN_SESSION_OLDER_THAN_SECS'] = 604800;//(7 * 24 * 60 * 60)
		$configuration['MAX_PRODUCT_ERROR_GOING_TOGETHER_COUNT'] = 5;
		$configuration['MAX_DUPLICATED_PRODUCT_ID_COUNT'] = 5;
		$configuration['MAX_NOLIST_ERROR_GOING_TOGETHER_COUNT'] = 10;
		$configuration['MAX_NOPRODUCT_ERROR_GOING_TOGETHER_COUNT'] = 3;
		$configuration['MAX_NETWORK_ERROR_GOING_TOGETHER_COUNT'] = 5;
		$configuration['MAX_IMAGE_FILE_LENGTH_IN_BYTES'] = 300000;
		$configuration['MAX_IMAGE_ERROR_GOING_TOGETHER_COUNT'] = 5;
		$configuration['TIME_INTERVAL_BETWEEN_HTTP_REQUESTS_IN_MSS'] = 3000;
		//$configuration['HTTP_REQUEST_TIMEOUT_IN_SECS'] = 60;
		return $configuration;
	}
	
	//helper function		
	final protected function get_items_and_add2queue($parent_item, $extractor, $item_class, $request_class, $queue_name, Counter $item_error_going_together_counter)
	{		
		$urls_or_requests_or_items = $this->$extractor();
		if($urls_or_requests_or_items and !is_array($urls_or_requests_or_items)) $urls_or_requests_or_items = array($urls_or_requests_or_items);
		if(!$urls_or_requests_or_items or !count($urls_or_requests_or_items)) 
		{
			Logger::Warning_("No Item found for $queue_name queue.");
			$item_error_going_together_counter->Increment();
			return;
		}
		else $item_error_going_together_counter->Reset();
		$items = $this->convert_objects2items($parent_item, $urls_or_requests_or_items, $item_class, $request_class);
		$this->Engine()->AddItems2Queue($queue_name, $items);
	}
	
	//helper function
	//objects can be either url's or Item's' or Request's or Request seeds - but only same type in the array
	final protected function convert_objects2items($parent_item, Array $objects, $item_class, $request_class)
	{	
		$o = $objects[0];
		if(is_string($o))
		{
			if(preg_match("@^\s*https?\://@", $o))
			{//urls
				$items = array();
				foreach($objects as $url)
				{
					$request = new $request_class($url);
					$i = new $item_class($parent_item);
					$i->Seed = $request->Seed();
					$items[$i->Seed] = $i;				
				}				
			}
			else
			{//seeds
				$items = array();
				foreach($objects as $seed)
				{	
					$i = new $item_class($parent_item);
					$i->Seed = $seed;
					$items[$i->Seed] = $i;		
				}				
			}
			$items = array_values($items);
		}
		elseif($o instanceof Item)
		{
			$items = $objects;
		}
		elseif($o instanceof $request_class) 
		{
			$items = array();
			foreach($objects as $request)
			{
				$i = new $item_class($parent_item);
				$i->Seed = $request->Seed();
				$items[$request->Seed()] = $i;				
			}
			if(Mode::This() == Mode::RAW_DEBUG or Mode::This() == Mode::DEBUG) Logger::Write("Extracted seeds: ".Misc::GetArrayAsString(array_keys($items)));
			return array_values($items);
		}
		else
		{
			$this->ExitOnError("Returned object could not be converted into Item: '$o', class: ".get_class($o));
		}
		if(Mode::This() == Mode::RAW_DEBUG or Mode::This() == Mode::DEBUG)
		{
			$ss = array();
			foreach($items as $i)
			{
				$s = array();
				$s['seed'] = $i->Seed; 
				$r = $request_class::Restore($i->Seed);
				$s['url'] = $r->Url();
				if($r->PostParameters()) $s['POST'] = $r->PostParameters();
				$ss[] = $s;
			}
			Logger::Write("Extracted unique urls: ".Misc::GetArrayAsString($ss));
		}
		return $items;
	}
	
	abstract public function TestPage($url);
}

function Run()
{
	try
	{		
		$crawler = GetCrawlerClass();
		$e = new Engine6($crawler);
		$e->Run();
	}
	catch(Exception $e)
	{
		Logger::Error($e);				
	}
}


?>