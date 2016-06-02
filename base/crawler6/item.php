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
			
abstract class Item
{	
	//it allows to access properties of ancestors by direct call
	public function __get($key)
	{
		for($parent_item = $this->parent_item; $parent_item != null; $parent_item = $parent_item->ParentItem()) if(property_exists($parent_item, $key)) return $parent_item->$key;
		Logger::Quit("Item ".get_class($this)." id='".$this->Id()."' has neither property '$key' nor ancestor having it.");
	}
	
	final public function __construct($parent_item)
	{		
		$this->parent_item = $parent_item;
	}
		
	final public function ParentItem()
	{
		return $this->parent_item;		
	}
	private $parent_item;
	
	final public function Id()
	{
		return $this->id;
	}	
	private $id;			
		
	final public function QueueName()
	{
		return $this->queue_name;		
	}
	private $queue_name;
	
	final public function GetValuesAsString()
	{
		$values = array();		
		foreach(self::GetPublicProperties($this) as $key)
		{
			$value = $this->$key;
			if(!is_string($value))
			{
				if($value) Logger::Quit("Item value must be a string: $key=$value");
				else $value = "";
			}
			(strpos($value, "|") === false) or Logger::Quit("Item value cannot contain '|': $value");
			$values[] = trim($value);
		}
		return join("|", $values);
	}
	
	final public static function Restore($item_class, $item_values_string, $id, $parent_item, $queue_name)
	{		
		$item = new $item_class($parent_item);
		$item->id = $id;
		$item->queue_name = $queue_name;
		
		$values = preg_split("@\|@is", $item_values_string);
		$i = 0;
		foreach(self::GetPublicProperties($item_class) as $key) $item->$key = $values[$i++];
				
		return $item;
	}
		
	final public static function GetPublicProperties($item_class)
	{
		$reflect = new ReflectionClass($item_class);
		$ps = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
		$properties = array();
		foreach($ps as $p) $properties[] = $p->getName();
		return $properties;
	}		
	
	///////////////////////   HELPER methods  /////////////////////////
			
	/*final public function GetValueFromAncestorItems($key)
	{
		for($parent_item = $this->ParentItem(); $parent_item != null; $parent_item = $parent_item->ParentItem()) if(property_exists($parent_item, $key)) return $parent_item->$key;
		Logger::Quit("Item ".get_class($this)." id='".$this->Id()."' has no ancestor having property '$key'");
	}*/
}

?>