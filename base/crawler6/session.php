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
include_once("$ABSPATH/common/db.php");
include_once("$ABSPATH/base/crawler6/table_routines.php");
include_once("$ABSPATH/base/crawler6/queue.php");
include_once("$ABSPATH/base/crawler6/crawler.php");
			
final class Session
{
	function __construct(Crawler $crawler)
	{
		$this->crawler = $crawler or Logger::Quit("Engine is not specified.");
				
		$queue_names2schema = $this->crawler->Get('QUEUE_NAMES2SCHEMA');
		count($queue_names2schema) or Logger::Quit("No queue is defined. They should be set in QUEUE_NAMES2SCHEMA");
		$this->table = TableRoutines::CreateSessionTableForCrawler($this->crawler->Id(), array_keys($queue_names2schema));	
		
		$this->start_time = Db::GetSingleValue("SELECT UNIX_TIMESTAMP(add_time) FROM $this->table ORDER BY id LIMIT 1"); 	
		if($this->start_time and $this->start_time < time() - $this->crawler->Get('DROP_BROKEN_SESSION_OLDER_THAN_SECS'))
		{
			Db::SmartQuery("DELETE FROM $this->table");
			Logger::Write("The previous broken session is dropped as older than ".Misc::GetDurationAsString($this->crawler->Get('DROP_BROKEN_SESSION_OLDER_THAN_SECS')));
			$this->start_time = null;
		}
		
		foreach($queue_names2schema as $queue_name=>$schema) $this->queue_names2queue[$queue_name] = new Queue($this->table, $queue_name, $schema);						
		
		if($this->start_time)
		{				
			$is_new_item = false;
			foreach($this->queue_names2queue as $q)
			{
				$new_item_count = $q->NewCount() and $is_new_item = true;
				Logger::Write("Broken session queue '".$q->Name."': new item count: $new_item_count; total item count: ".$q->TotalCount());
			}
			if($is_new_item)
			{			
				$this->is_new = false;
				Logger::Write("The session is restored since ".date("Y-m-d H:i:s", $this->start_time));
			}
			else
			{
				Logger::Error_("Despite the previous session was broken, it contains no new item and so cannot be continued. Usually it should not happen so and most likely means a problem in the target site or the crawler. Possible reasons are:
- download timeout happened and the pages were trimmed (the warning is logged),
- the pages had unacceptable content type which is filtered out by the framework (the warning is logged),
- \$IgnoredErrorHttpCodes is set by the crawler making the framework ignore download problems, 
- the target site was being redeveloped,
- the site's http server was overloaded while being not set properly, 
- curl has a bug.

A new session will be started.");
				Db::Query("DELETE FROM $this->table");
				$this->start_time = null;
			}
		}
		if(!$this->start_time)
		{
			$this->is_new = true;
			$this->start_time = Db::GetSingleValue("SELECT UNIX_TIMESTAMP()");
			Logger::Write("The session is new. Started: ".date("Y-m-d H:i:s", $this->start_time));
		}		
	}		
	private $crawler;
	private $table;
	private $queue_names2queue = array();
	
	public function StartTime()
	{
		return $this->start_time;
	}
	private $start_time;
	
	public function IsNew()
	{
		return $this->is_new;
	}
	private $is_new;
		
	public function IsError()
	{
		$error = false;
		foreach($this->queue_names2queue as $q)
		{
			$ec = $q->ErrorCount();
			if(!$ec) continue;
			$error = true;
			Logger::Write("Queue '".$q->Name."': error item count: $ec");
		}
		return $error;
	}
	
	//can be invoked only on successfull completion 
	public function Destroy()
	{
		Db::Query("DROP TABLE IF EXISTS $this->table");
		Logger::Write("Session table has been dropped.");
	}
	
	public function DropCurrentBranchDownToTrunkQueue($trunk_queue_name)
	{
		if($trunk_queue_name == null) 
		{
			$c = Db::SmartQuery("UPDATE $this->table SET state='dropped' WHERE state='new'");
			Logger::Write("Dropped all new items: $c");
			return;
		}
		
		foreach($this->queue_names2queue as $qn=>$q) 
		{
			if($qn == $trunk_queue_name) break;
			$c = Db::SmartQuery("UPDATE $this->table SET state='dropped' WHERE queue_name='".$q->Name."' AND state='new'");
			Logger::Write("Dropped new items of queue '".$q->Name."': $c from current branch down to trunk queue '$trunk_queue_name'");
		}
	}
	
	static public function Cleanup()
	{
		Logger::Write2("Cleaning session tables.");
		foreach(TableRoutines::GetSessionTables() as $table)
		{	
			$crawler_id = TableRoutines::GetCrawlerIdForSessionTable($table);
			$r = Db::GetRowArray("SELECT state, _last_session_state FROM crawlers WHERE id='$crawler_id'");
			if(empty($r)) Logger::Write("Dropping table $table because its crawler does not exist anymore.");
			elseif($r['state'] == 'disabled') Logger::Write("Dropping table $table because its crawler is disabled.");
			elseif($r['_last_session_state'] == 'started') continue;
			else
			{
				$last_time = Db::GetSingleValue("SELECT UNIX_TIMESTAMP(add_time) FROM $table ORDER BY id DESC LIMIT 1");
				if($last_time and $last_time >= time() - 3600 * 24 * 30) continue;
				else Logger::Write("Dropping table $table as containing only old data.");
			}			
			Db::Query("DROP TABLE $table");
		}
	}
		
	//**************************************************************************************************************
	//bot cycle and queue management
	//**************************************************************************************************************	
	private function get_item_from_current_branch($item_id)
	{		
		if(!$item_id) return null;
		
		for($ancestor_item = $this->current_item; $ancestor_item; $ancestor_item = $ancestor_item->ParentItem()) if($ancestor_item->Id() == $item_id) return $ancestor_item;
			
		$i = Db::GetRowArray("SELECT item_values, id, parent_id, queue_name FROM $this->table WHERE id=$item_id");
		return Item::Restore($this->queue_names2queue[$i['queue_name']]->ItemClass, $i['item_values'], $i['id'], $this->get_item_from_current_branch($i['parent_id']), $i['queue_name']);
	}		
	
	public function Move2NextItem()
	{
		if($i = Db::GetRowArray("SELECT item_values, id, parent_id, queue_name FROM $this->table WHERE state='new' ORDER BY queue_name, id LIMIT 1"))
		{
			$current_queue = $this->queue_names2queue[$i['queue_name']];
			$parent_item = $this->get_item_from_current_branch($i['parent_id']);
			$item = Item::Restore($current_queue->ItemClass, $i['item_values'], $i['id'], $parent_item, $current_queue->Name);
			
			if($this->current_item and $this->current_item->Id() == $item->Id()) Logger::Quit("Item '".$item->Id()."' in queue '$queue->Name' (id: $queue->Id) was not completed.");
			$this->current_item = $item;
						
			if($dibwice = $this->crawler->Get('QUEUE_NAMES2SCHEMA/'.$current_queue->Name.'/DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED'))
			{
				static $queue_name2item_count = array();
				static $last_trunk_item;
				if(!isset($queue_name2item_count[$current_queue->Name]))
				{
					$last_trunk_item = $this->get_trunk_item($this->current_item, $dibwice['TRUNK_QUEUE_NAME']);
					$queue_name2item_count[$current_queue->Name] = $this->get_not_new_item_count_in_branch_in_queue($last_trunk_item->Id(), $current_queue->Name);
					Logger::Write("Queue '".$current_queue->Name."' has 'DROP_ITEM_BRANCH_WHEN_ITEM_COUNT_EXCEEDED' set. In this queue for current brunch of trunk queue '".$dibwice['TRUNK_QUEUE_NAME']."' old item count: ".$queue_name2item_count[$current_queue->Name]);
				}
				$current_trunk_item = $this->get_trunk_item($this->current_item, $dibwice['TRUNK_QUEUE_NAME']);
				if($last_trunk_item != $current_trunk_item)
				{
					$queue_name2item_count[$current_queue->Name] = 0;
					$last_trunk_item = $current_trunk_item;
				}
				$ic = $queue_name2item_count[$current_queue->Name] + 1;
				if($ic > $dibwice['MAX_ITEM_NUMBER'])
				{
					Logger::Write("Max product number ".$dibwice['MAX_ITEM_NUMBER']." for current branch of '".$current_queue->Name."' has been reached");
					$this->DropCurrentBranchDownToTrunkQueue($dibwice['TRUNK_QUEUE_NAME']);
					$queue_name2item_count[$current_queue->Name] = 0;
					if(Db::GetSingleValue("SELECT state FROM $this->table WHERE id=".$this->current_item->Id()) != 'new') return $this->Move2NextItem();
				}
				else $queue_name2item_count[$current_queue->Name] = $ic;
			}
						
			return $this->current_item;
		}
		$this->current_item = null;		
	}
	private $current_item;
	
	private function get_trunk_item($item, $trunk_queue_name)
	{
		for(; $item; $item = $item->ParentItem()) if($item->QueueName() == $trunk_queue_name) return $item;
	}
		
	private function get_not_new_item_count_in_branch_in_queue($trunk_item_id, $queue_name)
	{
		$c = 0;
		$rs = Db::GetArray("SELECT id, queue_name, state FROM $this->table WHERE parent_id=$trunk_item_id");
		foreach($rs as $r)
		{
			if($r['queue_name'] == $queue_name and $r['state'] != 'new') $c++;
			$c += $this->get_not_new_item_count_in_branch_in_queue($r['id'], $queue_name);
		}
		return $c;
	}
	
	//get currently picked up item
	final public function Item()
	{
		return $this->current_item;
	}	
	
	final public function GetQueue($queue_name)
	{
		return $this->queue_names2queue[$queue_name];
	}
}

?>