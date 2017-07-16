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

include_once("clients_filter.def.php");

class clients_table extends Table
{
	function __construct()
	{
		$columns = array("#"=>'id', "Created"=>'create_time', "Client"=>'name', "Type"=>'type', "Emails"=>'emails', "_edit"=>"", "_filters"=>"", "_alerts"=>"");
		//$columns = array('id'=>"#", 'create_time'=>"Created", 'name'=>"Client", 'type'=>"Type", 'emails'=>"Emails", "_edit"=>"", "_filters", "_alerts");
				
		if(!empty($_REQUEST["client_id"])) $where = "WHERE id='".$_REQUEST['client_id']."'";
		elseif(!empty($_REQUEST['name_pattern'])) $where = "WHERE name LIKE '%".$_REQUEST['name_pattern']."%'";
		else $where = "";
		$sql = "SELECT * FROM alert_clients $where ORDER BY id DESC";
        parent::__construct($columns, $sql, 0, "list");
        parent::add_delete_column("ATTENTION!\\nSelected client and all the related information will be deleted. Proceed?");
	}

	function can_be_updated($id)
	{
		$result = self::query("SELECT * FROM alert_filters WHERE client_id='$id' AND last_start_time IS NULL");
		$values = mysql_fetch_assoc($result);
		if(empty($values)) return;
		
		$result = self::query("SELECT * FROM alert_notifications WHERE client_id='$id'");
		$values = mysql_fetch_assoc($result);
		if(!empty($values)) return;
	}
	
	function on_delete()
	{	
		if(!$this->can_be_updated($_REQUEST['id']))
		{
			$_SESSION['error'] = "You cannot delete this client as his/her filter has been taken to search.";			
			return;
		}
		self::query("DELETE FROM alert_filters WHERE client_id=".$_REQUEST['id']);
		self::query("DELETE FROM alert_clients WHERE id=".$_REQUEST['id']);
	}

	function build_row(&$db_row)
	{
		$key_id = $db_row["id"];		
		
		$date = substr($db_row["create_time"], 0, 10);
		$db_row['create_time'] = $date;
		$db_row["_edit"] = "<a href='update_client.php?action=update&id=$key_id' title='edit'>edit</a>";
		$db_row["_filters"] = "<a href='filters.php?client_id=$key_id' title='filters'>filters</a>";
		$db_row["_alerts"] = "<a href='alerts.php?client_id=$key_id' title='alerts'>alerts</a>";
		
		parent::build_row($db_row);
		
		$updatable = $this->can_be_updated($key_id);
		if(!$updatable) $db_row["_delete"] = "";
	}
}
?>