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

include_once("alerts_filter.def.php");

class alerts_table extends Table
{
	function __construct()
	{
		$columns = array("Client ID"=>'client_id', "Filter ID"=>'filter_id', "Product ID"=>"product_id", "State"=>'_state', "Found"=>'found_time', "Sent Time"=>'sent_time', "Town"=>"product_town", "Postcode"=>"product_postcode", "Matched Types"=>"matched_types", "Price"=>"product_price", "_Site"=>"product_url");
		
		$wheres = array();
		if(!empty($_REQUEST["client_id"])) $wheres[] = "client_id='".$_REQUEST["client_id"]."'";
		if(!empty($_REQUEST["filter_id"])) $wheres[] = "filter_id='".$_REQUEST["filter_id"]."'";
		if(empty($wheres)) $where = "";
		else $where = "WHERE ".join(" AND ", $wheres);
		$sql = "SELECT * FROM alert_notifications $where ORDER BY found_time DESC"; 
        parent::__construct($columns, $sql, 0, "list");
	}

	function build_row(&$db_row)
	{
		$db_row['found_time'] = substr($db_row["found_time"], 0, 10);
		$db_row['sent_time'] = substr($db_row["sent_time"], 0, 10);
		$db_row["product_url"] = "<a href='".$db_row["product_url"]."' title='site' target='_blank'>site</a>";
		$db_row["client_id"] = "<a href='clients.php?client_id=".$db_row["client_id"]."' title='client'>".$db_row["client_id"]."</a>";
		$db_row["filter_id"] = "<a href='filters.php?filter_id=".$db_row["filter_id"]."' title='filter'>".$db_row["filter_id"]."</a>";
		$db_row["product_id"] = "<a href='/~maker/webtester?product_id=".urlencode($db_row["product_id"])."' title='product' target='_blank'>".$db_row["product_id"]."</a>";
		
		$f = json_decode($db_row['matched_types'], true);
		$db_row["matched_types"] = "";
		$db_row["matched_types"] .= isset($f['status']) ? join("<br>", $f['status']) : "";
		$db_row["matched_types"] .= isset($f['features']) ? "<br>".join("<br>", $f['features']) : "";
		
		parent::build_row($db_row);
	}
}
?>