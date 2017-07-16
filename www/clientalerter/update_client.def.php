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

include_once("base/update.php");
class update_client extends Update
{
	function get_form_parameters()
	{
		if(empty($_REQUEST['id'])) $values = $_REQUEST;
		else
		{
			$sql = "SELECT * FROM alert_clients WHERE id=".$_REQUEST['id'];
			$result = self::query($sql);
			$values = mysql_fetch_assoc($result);
		}
		$parameters["Name"] = "<input type='text' name='name' value='".(isset($values["name"]) ? $values["name"] : "")."'>";
		$parameters["Type"] = self::BuildSelectParameter('type', (isset($values["type"]) ? $values["type"] : ""), array('gold', 'platinum'));
		$parameters["Emails"] = "<input type='text' name='emails' value='".(isset($values["emails"]) ? $values["emails"] : "")."'>";
		$parameters[] = "<input type='hidden' name='id' value='".(isset($values["id"]) ? $values["id"] : "")."'>";
		return $parameters;
	}

	function save()
	{
		if(!empty($_REQUEST['id'])) $sql = "UPDATE alert_clients SET type='".addslashes($_REQUEST['type'])."', name='".addslashes($_REQUEST['name'])."', emails='".addslashes($_REQUEST['emails'])."' WHERE id=".$_REQUEST['id'];
		else $sql = "INSERT INTO alert_clients SET type='".addslashes($_REQUEST['type'])."', name='".addslashes($_REQUEST['name'])."', emails='".addslashes($_REQUEST['emails'])."', create_time=NOW()";
		return self::query($sql);
	}

	function validate()
	{
	    if(!$_REQUEST["name"]) $errors[] = "Name field cannot be empty";
	    if(!$_REQUEST["emails"]) $errors[] = "Emails field cannot be empty";

	    if(!empty($errors))
	    {
	    	$this->error($errors);
	    	return false;
	    }
	    return true;
	}
}
?>