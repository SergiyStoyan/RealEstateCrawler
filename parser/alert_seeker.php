<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/..";

include_once("$ABSPATH/common/html_utilities.php");
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/common/misc.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");

class AlertSeeker
{
	public static $WriteDetails = false;
	
	public static function Init()
	{
		Logger::Write2("Initiating AlertSeeker");
			
		//self::cleanup();
				
		self::$filters = array();
		foreach(Db::GetArray("SELECT id, client_id, filter FROM alert_filters WHERE state='active'") as $r)
		{
			self::add_filter($r);
			Db::Query("UPDATE alert_filters SET last_start_time=NOW() WHERE id='".$r['id']."'");
		}
	}
	private static $filters;
	
	private static function add_filter($r)
	{
		$f = strtolower($r['filter']);
		$f = json_decode($f, true);
		
		$f['filter_id'] = $r['id'];
		$f['client_id'] = $r['client_id'];
		if(!empty($f['town'])) $f['town'] = trim($f['town']);
		else $f['town'] = false;
		if(!empty($f['postcode'])) $f['postcode1'] = trim(preg_replace("@\s.+@is", "", $f['postcode']));
		else $f['postcode1'] = false;
		if(!empty($f['status'])) $f['status'] = explode(",", trim($f['status']));
		else $f['status'] = false;
		if(!empty($f['features'])) $f['features'] = explode(",", trim($f['features']));
		else $f['features'] = false;
		if(!empty($f['price_min'])) $f['price_min'] = trim($f['price_min']);
		else $f['price_min'] = false;
		if(!empty($f['price_max'])) $f['price_max'] = trim($f['price_max']);
		else $f['price_max'] = false;
		
		self::$filters[$r['id']] = $f;
	}
		
	public static function DebugInit($filter_id)
	{		
		self::$filters = array();
		$r = Db::GetRowArray("SELECT id, client_id, filter FROM alert_filters WHERE state='active' and id=$filter_id");
		self::add_filter($r);
	}
		
	public static function Seek(&$pd, $id, $url, $crawler_id)
	{
		if(empty(self::$filters)) self::Init();
		
		$postcode1 = trim(preg_replace("@\s.+@is", "", $pd['postcode']));
		$status = $pd['status'];
		$features = $pd['features'];
		$price = $pd['price'];
				
		foreach(self::$filters as $filter_id=>$filter)
		{
			if(self::$WriteDetails) Logger::Write("Filter id: $filter_id");
			//if(self::$WriteDetails) Logger::Write("Filter:\n".Misc::GetArrayAsString($filter));
			
			if(self::$WriteDetails) Logger::Write($filter['town']."<=>".$pd['town']);
			if($filter['town'] and $filter['town'] != $pd['town']) continue;
			
			if(self::$WriteDetails) Logger::Write($filter['postcode1']."<=>".$postcode1);
			if($filter['postcode1'] and $filter['postcode1'] != $postcode1) continue;
				
			if(self::$WriteDetails) Logger::Write(join(",", $filter['status'])."<=>".$pd['status']);
			$matched_types = array();
			if($filter['status'])
			{
				$type_not_found = false;
				foreach($filter['status'] as $value) 
				{
					if(stripos($status, $value) === false) 
					{
						$type_not_found = true;
						break;
					}
					$matched_types['status'][] = $value;
				}
				if($type_not_found) continue;
			}
			
			if(self::$WriteDetails) Logger::Write(join(",", $filter['features'])."<=>".$pd['features']);			
			if($filter['features'])
			{
				$type_not_found = false;
				foreach($filter['features'] as $value)
				{
					if(stripos($features, $value) !== false) 
					{
						$matched_types['features'][] = $value;
						$type_not_found = false;
						break;
					}
					$type_not_found = true;					
				}
				if($type_not_found) continue;
			}
			
			if(self::$WriteDetails) Logger::Write($filter['price_min']."<=>".$pd['price']);
			if($filter['price_min'] and $filter['price_min'] > $price) continue;
			
			if(self::$WriteDetails) Logger::Write($filter['price_max']."<=>".$pd['price']);
			if($filter['price_max'] and $price > $filter['price_max']) continue;
			
			if(self::$WriteDetails) Logger::Write("Setting alert.");
			
			if(!isset($description)) $description = substr($pd['description'], 0, 150)."...";			
			$matched_types = json_encode($matched_types);			
			$global_product_id = TableRoutines::GetGlobalProductId($crawler_id, $id);
						
			$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
			$r = Db::GetRowArray("SELECT raw_data, change_time, crawl_time FROM $products_table WHERE id='".addslashes($id)."'");
			$change_time = empty($r['change_time']) ? $r['crawl_time'] : $r['change_time'];
			$rd = json_decode($r['raw_data'], true);
			$agent = isset($pd['agent']) ? $pd['agent'] : Html::PrepareField($rd['agent']);
			Db::Query("INSERT IGNORE alert_notifications SET client_id='".$filter['client_id']."', filter_id='".$filter['filter_id']."', product_id='".addslashes($global_product_id)."', product_town='".addslashes($pd['town'])."', product_status='$status', product_features='$features', product_postcode='".$pd['postcode']."', product_agent='".addslashes($agent)."', product_url='".addslashes($url)."', product_image_path='".$rd['image_path']."', product_description='".addslashes($description)."', product_price='$price', matched_types='$matched_types', _state='new', found_time=NOW(), product_change_time='$change_time'");
			
			if(!Db::LastAffectedRows()) Logger::Write("ALERT_SEEKER: $global_product_id is ignored for client_id '".$filter['client_id']."' as it was already caught fot this client.");			
		}
	}	
	
	public static function Complete()
	{
		if(empty(self::$filters)) return;// Logger::Quit("The class was not initiated.");
		
		foreach(self::$filters as $filter_id=>$filter) Db::Query("UPDATE alert_filters SET last_end_time=NOW() WHERE id='$filter_id'");
	}
	
	const DELETE_DATA_OLDER_THAN_DAYS = 90;
	
	static public function Cleanup()
	{
		Logger::Write2("Cleaning client alert tables.");
		$ns = Db::SmartQuery("DELETE FROM alert_notifications WHERE found_time<ADDDATE(NOW(), INTERVAL -".AlertSeeker::DELETE_DATA_OLDER_THAN_DAYS." DAY)");
		$fs = Db::SmartQuery("DELETE FROM alert_filters WHERE state='archived' AND (SELECT COUNT(n.filter_id) FROM alert_notifications n WHERE n.filter_id=alert_filters.id LIMIT 1)=0");
		$cs = Db::SmartQuery("DELETE FROM alert_clients WHERE create_time<ADDDATE(NOW(), INTERVAL -".AlertSeeker::DELETE_DATA_OLDER_THAN_DAYS." DAY) AND (SELECT COUNT(f.client_id) FROM alert_filters f WHERE f.client_id=alert_clients.id LIMIT 1)=0");
		Logger::Write("Deleted old:\nalert_notifications: $ns\nalert_filters: $fs\nalert_clients: $cs");		
	}
}

include_once("$ABSPATH/common/shell_utilities.php");

if(Shell::IsStartFile())
{
	error_reporting(E_ALL);
	set_time_limit(0);
	ini_set('memory_limit', '-1');

	Shell::ExitIfTheScriptRunsAlready();

	class Mode extends ModeTemplate
	{
   		const TEST_PRODUCT = "t[f]";
	}

	Mode::PrintUsage(array(
		Mode::TEST_PRODUCT=>"=<crawler_id>+<product_id> [-f=<filter_id>] - apply alert filter <filter_id> or all filters to <product_id> in table of <crawler_id>",
		)
	);

	include_once("$ABSPATH/constants.php");
	Logger::Init(Constants::LogDirectory."/_alert_seeker", 3);
		
	Logger::Write2("Process owner: ".Shell::GetProcessOwner());
	Logger::Write2("STATRED");
	Logger::Write2("MODE: ".Mode::Name());
	
	switch(Mode::This())
	{
		case Mode::TEST_PRODUCT:
		
			Logger::$CopyToConsole = true;
			TableRoutines::GetPartsOfGlobalProductId(Mode::OptionValue(), $crawler_id, $product_id);
			$products_table = TableRoutines::GetProductsTableForCrawler($crawler_id);
			$r = Db::GetRowArray("SELECT * FROM $products_table WHERE id='".addslashes($product_id)."'") or Logger::Quit("Product ID '$product_id' does not exist in '$products_table'");
			
			if($r["_state"] == 'new') Logger::Quit("The product is not parsed.");
			
			AlertSeeker::$WriteDetails = true;
			
			if($filter_id = Mode::OptionValue("f"))
			{
				Logger::Write("Applying filter $filter_id to product: ".$r['id']." in $products_table");
				AlertSeeker::DebugInit($filter_id);				
			}
			else Logger::Write("Applying all filters to product: ".$r['id']." in $products_table");
				
			$pd = json_decode($r['parsed_data'], true);
			//$rd = json_decode($r['raw_data'], true);
			AlertSeeker::Seek($pd, $r['id'], $r['url'], $crawler_id);
		
		break;
	default:	
		
		Logger::Quit("No such mode defined: ".Mode::Name());
	}			
}

/*
CREATE TABLE IF NOT EXISTS `alert_clients` (
  `id` int AUTO_INCREMENT,
  `create_time` datetime NOT NULL,
  `name` varchar(32) NOT NULL,
  `emails` varchar(512) NOT NULL,
  `type` enum('gold','platinum') NOT NULL, 
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
  
CREATE TABLE IF NOT EXISTS `alert_filters` (
  `id` int AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `filter` varchar(512) NOT NULL,
  `state` enum ('active','archived') NOT NULL,
  `create_time` datetime NOT NULL,
  `last_start_time` datetime,
  `last_end_time` datetime,
  `comment` varchar(512),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filter_key` (`client_id`, `filter`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    
CREATE TABLE IF NOT EXISTS `alert_notifications` (
  `filter_id` int NOT NULL,
  `client_id` int NOT NULL,
  `_state` enum ('new','sent','omitted','error') NOT NULL,
  `found_time` datetime NOT NULL,
  `sent_time` datetime,
  `product_id` varchar(256) NOT NULL,
  `product_change_time` datetime NOT NULL,
  `product_town` varchar(32) NOT NULL,
  `product_status` varchar(256) NOT NULL,
  `product_features` varchar(256) NOT NULL,
  `product_postcode` varchar(10) NOT NULL,  
  `product_agent` varchar(256) NOT NULL,
  `product_url` varchar(256) NOT NULL,
  `product_image_path` varchar(256) NOT NULL,
  `product_description` varchar(256) NOT NULL,
  `product_price` int NOT NULL,
  `matched_types` varchar(256) NOT NULL,
  PRIMARY KEY (`client_id`, `product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  	
*/


?>