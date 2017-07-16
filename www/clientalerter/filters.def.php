<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


include_once("menu1.def.php");
include_once("base/back.php");
include_once("base/table.php");

include_once("filters_filter.def.php");

class filters_table extends Table
{
	function __construct()
	{
		$columns = array("#"=>'id', "Created"=>'create_time', "Client ID"=>'client_id', "State"=>'state', "Comment"=>'comment', "Town"=>"", "Postcode"=>"", "Types"=>"", "Features"=>"", "Price Range"=>"", "_edit"=>"", "_duplicate"=>"", "_alerts"=>"");
		
		$wheres = array();
		if(!empty($_REQUEST["client_id"])) $wheres[] = "client_id='".$_REQUEST["client_id"]."'";
		if(!empty($_REQUEST["filter_id"])) $wheres[] = "id='".$_REQUEST["filter_id"]."'";
		if(!empty($wheres)) $where = "WHERE ".join(" AND ", $wheres);
		else $where = "";
		$sql = "SELECT * FROM alert_filters $where ORDER BY id DESC"; 
        parent::__construct($columns, $sql, 0, "list");
        parent::add_delete_column();
	}

	public static function CanBeUpdated($id)
	{
		$result = self::query("SELECT * FROM alert_filters WHERE id='$id' AND last_start_time IS NULL");
		$values = mysql_fetch_assoc($result);
		if(empty($values)) return false;
		
		$result = self::query("SELECT * FROM alert_notifications WHERE filter_id='$id' LIMIT 1");
		$values = mysql_fetch_assoc($result);
		if(!empty($values)) return false;
		
		return true;
	}	
	
	function on_delete()
	{
		if(!self::CanBeUpdated($_REQUEST['id']))
		{
			$_SESSION['error'] = "You cannot delete this filter as it has been taken to search.";
			return;
		}
		
		self::query("DELETE FROM alert_filters WHERE id=".$_REQUEST['id']);
	}

	function build_row(&$db_row)
	{
		$key_id = $db_row["id"];
		
		$updatable = true;
		if($db_row['last_start_time']) $updatable = false;
		else
		{
			$result = self::query("SELECT * FROM alert_notifications WHERE filter_id='$key_id' LIMIT 1");
			$values = mysql_fetch_assoc($result);
			if(!empty($values)) $updatable = false;			
		}
		
		$db_row['create_time'] = substr($db_row["create_time"], 0, 10);
		//$db_row["_edit"] = $updatable ? "<a href='update_filter.php?action=update&id=$key_id' title='edit'>edit</a>" : "";
		$db_row["_edit"] = "<a href='update_filter.php?action=update&id=$key_id' title='edit'>edit</a>";
		$db_row["_duplicate"] = "<a href='update_filter.php?action=update&origin_id=$key_id' title='duplicate'>copy</a>";
		$db_row["_alerts"] = "<a href='alerts.php?filter_id=$key_id' title='alerts'>alerts</a>";
		$db_row["client_id"] = "<a href='clients.php?client_id=".$db_row["client_id"]."' title='client'>".$db_row["client_id"]."</a>";
		
		$f = json_decode($db_row['filter'], true);
		$db_row["Town"] = isset($f['town']) ? $f['town'] : "";
		$db_row["Postcode"] = isset($f['postcode']) ? $f['postcode'] : "";
		$db_row["Types"] = join("<br>", explode(",", isset($f['status']) ? $f['status'] : ""));
		$db_row["Features"] = join("<br>", explode(",", isset($f['features']) ? $f['features'] : ""));
		$db_row["Price Range"] = (isset($f['price_min']) ? $f['price_min'] : "")." - ".(isset($f['price_max']) ? $f['price_max'] : "");
		
		parent::build_row($db_row);
		
		if(!$updatable) $db_row["_delete"] = "";
	}
}
?>