<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("base/filter.php");

class filters_filter extends Filter
{
	function __construct()
	{
		$clients = array();
		
		if(!empty($_REQUEST["filter_id"]))
		{			
			$result = self::query("SELECT client_id FROM alert_filters WHERE id='".$_REQUEST["filter_id"]."'");
			$r = mysql_fetch_assoc($result);
			if(empty($_REQUEST["client_id"])) $_REQUEST["client_id"] = $r['client_id'];
			elseif($_REQUEST["client_id"] != $r['client_id']) $_REQUEST["filter_id"] = "";
		}		
		
		$result = self::query("SELECT id, name FROM alert_clients");
		while($r = mysql_fetch_assoc($result)) $clients[$r['id']] = $r['name'];
		
		$clients_html = "<OPTION value='' SELECTED>--</OPTION>";
		foreach($clients as $id=>$n)
		{
			$select = "";
			if(isset($_REQUEST["client_id"]) and $id == $_REQUEST["client_id"]) $select = "SELECTED";
			$clients_html .= "<OPTION value='$id' $select>".$n."</OPTION>";
		}
		$parameters["Client"] = "<SELECT id='client_id' name='client_id' onchange='submit();'>$clients_html</SELECT>";
		
		if(!empty($_REQUEST["filter_id"]))
		{
			$value = $_REQUEST['filter_id'];
			$parameters["ID"] = "<input id='filter_id' name='filter_id' type='text' onchange='submit();' value='$value'>";
		}
			
		if(!empty($_REQUEST["filter_id"])) $show_filter = true;
		if(!empty($_REQUEST["client_id"])) $show_filter = true;
		else $show_filter = false;
		
		parent::__construct($parameters, $show_filter);
	}
}

?>