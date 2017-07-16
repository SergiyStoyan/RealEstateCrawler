<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/../../";

include_once("$ABSPATH/common/db.php");
//include_once("$ABSPATH/shell_utilities.php");

//Crawler db specific routines
class TableRoutines
{	
	static public function GetPartsOfGlobalProductId($global_product_id, &$crawler_id, &$product_id)
	{
		$ps = preg_split("@\+@is", $global_product_id);
		$crawler_id = $ps[0];
		$product_id = $ps[1];
	}
	
	static public function GetGlobalProductId($crawler_id, $product_id)
	{
		return "$crawler_id+$product_id";
	}
	
	static public function CreateProductsTableForCrawler($crawler_id)
	{
		$table = self::GetProductsTableForCrawler($crawler_id);
		Db::Query("
CREATE TABLE IF NOT EXISTS `$table` (
`id` varchar(128) NOT NULL,
`crawl_parameters` varchar(128) DEFAULT NULL COMMENT 'if crawler runs with different parameters',
`_state` enum('new','parsed','deleted','debug','debug_parsed') NOT NULL DEFAULT 'debug',
`crawl_time` datetime NOT NULL,
`url` varchar(256) NOT NULL,
`raw_data` text NOT NULL,
`parsed_data` text NOT NULL,
`parse_time` datetime DEFAULT NULL,
`change_time` datetime DEFAULT NULL,
`publish_time` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 		
		");
		return $table;
	}
	
	static public function GetProductsTableForCrawler($crawler_id)
	{		
		return "products_".$crawler_id;
	}
		
	static public function GetProductsTables()
	{
		return Db::GetFirstColumnArray("SHOW TABLES LIKE 'products\_%'");
	}
		
	static public function GetCrawlerIdForProductsTable($products_table)
	{
		return preg_replace("@^products_@is", "", $products_table);
	}
		
	static public function GetSessionTables()
	{
		return Db::GetFirstColumnArray("SHOW TABLES LIKE 'session\_%'");
	}
		
	static public function GetSessionTableForCrawler($crawler_id)
	{
		//return Db::GetSingleValue("SHOW TABLES LIKE 'session_$crawler_id'");
		return "session_".$crawler_id;
	}
		
	static public function CreateSessionTableForCrawler($crawler_id, array $queue_names)
	{
		$table = "session_$crawler_id";
		Db::Query("
CREATE TABLE IF NOT EXISTS `$table` (
`id` int(32) UNSIGNED NOT NULL AUTO_INCREMENT,
`queue_name` enum('".join("','", $queue_names)."') NOT NULL,
`state` enum('new','completed','error','dropped') NOT NULL,
`item_values` varchar(500) NOT NULL,
`parent_id` int UNSIGNED NOT NULL,
`add_time` datetime NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY (`queue_name`,`item_values`),
KEY (`queue_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		");		
		return $table;
	}
		
	static public function GetCrawlerIdForSessionTable($queues_table)
	{
		//$crawler_id = substr($table, strpos($queues_table, "_") + 1);
		//return Db::GetSingleValue("SELECT * FROM crawlers WHERE id='$crawler_id'");
		return preg_replace("@^session_@is", "", $queues_table);
	}
	
	static public function CreateCrawlersTable()
	{		
		return Db::Query("
CREATE TABLE IF NOT EXISTS `crawlers` (
  `id` varchar(32) NOT NULL,
  `state` enum('enabled','disabled','debug') NOT NULL DEFAULT 'debug',
  `site` varchar(64) NOT NULL,
  `command` enum('','stop','restart') NOT NULL DEFAULT '' COMMENT 'used while debugging/updating crawler',
  `run_time_span` int(11) NOT NULL DEFAULT '86400' COMMENT 'in seconds',
  `crawl_product_timeout` int(11) NOT NULL DEFAULT '600' COMMENT 'if no product was crawled for the last specified number of seconds, an error is arisen',
  `yield_product_timeout` int(11) NOT NULL DEFAULT '259200' COMMENT 'if no new product was added for the last specified number of seconds, an error is arisen',
  `admin_emails` varchar(300) NOT NULL COMMENT 'emails going by  '','' or new line',
  `comment` varchar(1000) NOT NULL,
  `restart_delay_if_broken` int(11) NOT NULL DEFAULT '1800' COMMENT 'in seconds',
  `_last_session_state` enum('','started','_completed','completed','_error','error','broken','killed','debug_completed') DEFAULT '',
  `_next_start_time` datetime NOT NULL,
  `_last_start_time` datetime NOT NULL,
  `_last_end_time` datetime NOT NULL,
  `_last_process_id` int(11) NOT NULL,
  `_last_log` varchar(500) NOT NULL,
  `_archive` text NOT NULL,
  `_last_product_time` datetime NOT NULL COMMENT 'used to monitor crawler activity by manager',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1; 		
		");
	}
}

?>