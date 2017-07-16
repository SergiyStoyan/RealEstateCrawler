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

class alerts_filter extends Filter
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
			if(!empty($_REQUEST["client_id"]) and $id == $_REQUEST["client_id"]) $select = "SELECTED";
			$clients_html .= "<OPTION value='$id' $select>".$n."</OPTION>";
		}
		$parameters["Client"] = "<SELECT id='client_id' name='client_id' onchange='e = getElementById(\"filter_id\"); if(e) e.value = \"\"; submit();'>$clients_html</SELECT>";
		
		if(!empty($_REQUEST["client_id"]))
		{
			$filters = array();
			
			$result = self::query("SELECT id, filter FROM alert_filters WHERE client_id='".$_REQUEST["client_id"]."'");
			while($r = mysql_fetch_assoc($result)) $filters[$r['id']] = $r['filter'];
			
			$filters_html = "<OPTION value='' SELECTED>--</OPTION>";
			foreach($filters as $id=>$f)
			{
				$select = "";
				if(!empty($_REQUEST["filter_id"]) and $id == $_REQUEST["filter_id"]) $select = "SELECTED";
				$f = json_decode($f, true);
				$s = $f['town'].",".$f['postcode'].",".$f['status'].",".$f['features'].",".$f['price_min']."-".$f['price_max'];
				if(strlen($s) > 100) $s = substr($s, 0, 100)."...";
				$filters_html .= "<OPTION value='$id' $select>".$s."</OPTION>";
			}
			$parameters["Filter"] = "<SELECT id='filter_id' name='filter_id' onchange='submit();'>$filters_html</SELECT>";			
		}
				
		if(!empty($_REQUEST["client_id"])) $show_filter = true;
		elseif(!empty($_REQUEST["filter_id"])) $show_filter = true;
		else $show_filter = false;
		
		parent::__construct($parameters, $show_filter);
	}
}

?>