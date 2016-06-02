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
include_once("$ABSPATH/base/crawler6/item.php");
include_once("$ABSPATH/common/misc.php");
			
final class Queue
{	
	function __construct($table, $name, $schema)
	{		
		$this->Name = $name or Logger::Quit("Queue name is empty.");
		$this->Processor = $schema['PROCESSOR'] or Logger::Quit("Queue processor is empty.");
		$this->ItemClass = $schema['ITEM_CLASS'] or Logger::Quit("Queue item class is empty.");
		$this->table = $table or Logger::Quit("Session table is empty.");
		
		if($schema['RESTORE_ERROR_ITEMS_AS_NEW'])
		{
			if($c = Db::SmartQuery("UPDATE $this->table SET state='new' WHERE queue_name='".$this->Name."' AND state='error'")) Logger::Write("Error items in '$name' were converted to new: $c");
		}
		
		$this->item_error_going_together_counter = new Counter("ITEM_ERROR_GOING_TOGETHER_COUNT", $schema['MAX_ITEM_ERROR_GOING_TOGETHER_COUNT']);
	}
	private $table;
	public $ItemClass;
	public $Name;
	public $Processor;
	
	//NB! each call to Next must have a respective call to Complete!
	public function CompleteItem($item, $success)
	{
		//if(!$this->item) 
		if($success)
		{
			$state = "completed";
			$this->item_error_going_together_counter->Reset();
		}
		else
		{
			$state = "error";
			Logger::Warning_("Item '".$item->GetValuesAsString()."' in '".$item->QueueName()."' was completed with error.");
			$this->item_error_going_together_counter->Increment();
		}
		Db::Query("UPDATE $this->table SET state='$state' WHERE id=".$item->Id()) or Logger::Quit("Item '".$item->Id()."' in queue '$this->Name' was already completed.");
	}
	private $item_error_going_together_counter = 0;
	
	public function Add(Item $item)
	{
		if($pi = $item->ParentItem()) $parent_id = $pi->Id();
		else $parent_id = 0;
		Db::Query("INSERT IGNORE $this->table SET parent_id=$parent_id, queue_name='".$this->Name."', item_values='".addslashes($item->GetValuesAsString())."', state='new', add_time=NOW()");
		//Db::Query("REPLACE $this->table SET parent_id=IF(state='dropped', $parent_id, parent_id), queue_id=$this->Id, item_values='".addslashes($item->GetValuesAsString())."', state=IF(state='dropped', 'new', state), add_time=IF(state='dropped', NOW(), add_time)");
		//Db::SmartQuery("INSERT IGNORE $this->table SET parent_id=$parent_id, queue_id=$this->Id, item_values='".addslashes($item->GetValuesAsString())."', state='new', add_time=NOW()") or Db::Query("UPDATE $this->table SET parent_id=$parent_id, queue_id=$this->Id, state='new', add_time=NOW() WHERE queue_id=$this->Id AND item_values='".addslashes($item->GetValuesAsString())."' AND state='dropped'");
		return Db::LastAffectedRows();
	}	
	
	public function NewCount()
	{
		return Db::GetSingleValue("SELECT COUNT(item_values) FROM $this->table WHERE queue_name='".$this->Name."' AND state='new'");
	}	
	
	public function NotNewCount()
	{
		return Db::GetSingleValue("SELECT COUNT(item_values) FROM $this->table WHERE queue_name='".$this->Name."' AND state<>'new'");
	}	
	
	public function ErrorCount()
	{
		return Db::GetSingleValue("SELECT COUNT(item_values) FROM $this->table WHERE queue_name='".$this->Name."' AND state='error'");
	}	
	
	public function TotalCount()
	{
		return Db::GetSingleValue("SELECT COUNT(item_values) FROM $this->table WHERE queue_name='".$this->Name."'");
	}
	
	public function GetLastItemState()
	{
		return Db::GetSingleValue("SELECT state FROM $this->table WHERE queue_name='".$this->Name."' ORDER BY id DESC LIMIT 1");
	}
}

?>