<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once("base/back.php");
include_once("menu1.def.php");
include_once("filters.def.php");

include_once("base/update.php");
class update_filter extends Update
{
	function get_form_parameters()
	{		
		if(!empty($_REQUEST['id']))
		{
			/*if(!$updatable)
			{
				$_SESSION['error'] = "You cannot update this filter as it has been taken to search.";
				return;
			}*/
			
			$sql = "SELECT * FROM alert_filters WHERE id=".$_REQUEST['id'];
			$result = self::query($sql);
			$values = mysql_fetch_assoc($result);
			$f = json_decode($values['filter'], true);
			$values = array_merge($values, $f);
		}
		elseif(!empty($_REQUEST['origin_id']))
		{
			$sql = "SELECT * FROM alert_filters WHERE id=".$_REQUEST['origin_id'];
			$result = self::query($sql);
			$values = mysql_fetch_assoc($result);
			$f = json_decode($values['filter'], true);
			$values = array_merge($values, $f);
			unset($values['id']);
		}
		else $values = $_REQUEST;
				
		if(!empty($_REQUEST['id']) and !filters_table::CanBeUpdated($_REQUEST['id'])) $disabled = "disabled";
		else $disabled = "";
		$parameters["State"] = self::BuildSelectParameter('state', (isset($values["state"]) ? $values["state"] : ""), array('active', 'archived'));
		$parameters["Town"] = "<input type='text' $disabled name='town' value='".(isset($values["town"]) ? $values["town"] : "")."'>";
		$parameters["Postcode"] = "<input type='text' $disabled name='postcode' value='".(isset($values["postcode"]) ? $values["postcode"] : "")."'>";
		
		$parameters["Type"] = "";
		foreach(self::get_values_of_field("status") as $v)
		{
			if(preg_match("@^!@is", $v)) continue;
			if(isset($values["status"]) and stristr($values["status"], $v) !== false or isset($values["status_$v"])) $checked = "checked";
			else $checked = "";
			$parameters["Type"] .= "<input type='checkbox' $disabled name='status_$v' value='$v' $checked> $v<br>";
		}
		
		$parameters["Features"] = "<br>";
		foreach(self::get_values_of_field("features") as $v)
		{
			if(preg_match("@^!@is", $v)) continue;
			if(isset($values["features"]) and stristr($values["features"], $v) !== false or isset($values["features_$v"])) $checked = "checked";
			else $checked = "";
			$parameters["Features"] .= "<input type='checkbox' $disabled name='features_$v' value='$v' $checked> $v<br>";
		}
		
		$parameters["Min Price"] = "<input type='text' $disabled name='price_min' value='".(isset($values["price_min"]) ? $values["price_min"] : "")."'>";
		$parameters["Max Price"] = "<input type='text' $disabled name='price_max' value='".(isset($values["price_max"]) ? $values["price_max"] : "")."'>";
		$parameters["Comment"] = "<input type='text' name='comment' value='".(isset($values["comment"]) ? $values["comment"] : "")."'>";
		$parameters[] = "<input type='hidden' name='id' value='".(isset($values["id"]) ? $values["id"] : "")."'>";
		$parameters[] = "<input type='hidden' name='client_id' value='".(isset($values["client_id"]) ? $values["client_id"] : "")."'>";
		return $parameters;
	}
	
	private static function get_values_of_field( $field)
	{
	    if($field == 'status') return array('repossession','investment_hmo','investment_portfolio','investment_prelet','investment_general','light_renovation','development','plot','unmortgageable','short_lease','planning_permssion_potential','planning_permission_granted','motivated_seller');
	    if($field == 'features') return array('basement','gifted_deposit','no_chain','no_stamp_duty','attic','vacant_possession','outbuildings','end_of_terrace','under_offer','garage','corner_plot');
	}

	function save()
	{
		if(empty($_REQUEST['id']) or filters_table::CanBeUpdated($_REQUEST['id']))
		{
			$f['town'] = $_REQUEST['town'];
			$f['postcode'] = $_REQUEST['postcode'];
			$f['price_min'] = $_REQUEST['price_min'];
			$f['price_max'] = $_REQUEST['price_max'];
			
			foreach(self::get_values_of_field("status") as $v) if(isset($_REQUEST["status_$v"])) $f['status'][] = $v;
			if(isset($f['status'])) $f['status'] = join(",", $f['status']); else $f['status'] = "";
			
			foreach(self::get_values_of_field("features") as $v) if(isset($_REQUEST["features_$v"])) $f['features'][] = $v;
			if(isset($f['features'])) $f['features'] = join(",", $f['features']); else $f['features'] = "";			
		}
		
		if(!empty($_REQUEST['id'])) 
		{
			if(empty($f)) $sql = "UPDATE alert_filters SET state='".addslashes($_REQUEST['state'])."', comment='".addslashes($_REQUEST['comment'])."' WHERE id=".$_REQUEST['id']; 
			else $sql = "UPDATE alert_filters SET state='".addslashes($_REQUEST['state'])."', comment='".addslashes($_REQUEST['comment'])."', filter='".addslashes(json_encode($f))."' WHERE id=".$_REQUEST['id'];
		}
		else $sql = "INSERT INTO alert_filters SET client_id='".addslashes($_REQUEST['client_id'])."', state='".addslashes($_REQUEST['state'])."', comment='".addslashes($_REQUEST['comment'])."', filter='".addslashes(json_encode($f))."', create_time=NOW()";
		return self::query($sql);
	}

	function validate()
	{
		if(filters_table::CanBeUpdated($_REQUEST['id']))
		{
		    if(!$_REQUEST["town"] and !$_REQUEST["postcode"]) $errors[] = "Either Town or Postcode must be set";
		    if(!$_REQUEST["state"]) $errors[] = "State field cannot be empty";			
		}

	    if(!empty($errors))
	    {
	    	$this->error($errors);
	    	return false;
	    }
	    return true;
	}
}
?>