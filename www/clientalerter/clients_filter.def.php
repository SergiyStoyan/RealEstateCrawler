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

class clients_filter extends Filter
{
	function __construct()
	{ 
		if(!empty($_REQUEST['client_id']))
		{
			$value = $_REQUEST['client_id'];
			$parameters["ID"] = "<input id='name_pattern' name='client_id' type='text' onchange='submit();' value='$value'>";
		}
		else
		{
			if(!empty($_REQUEST['name_pattern'])) $value = $_REQUEST['name_pattern'];
			else $value = "";
			$parameters["Name pattern"] = "<input id='name_pattern' name='name_pattern' type='text' onchange='submit();' value='$value'>";
		}
		
		if(!empty($_REQUEST["client_id"])) $show_filter = true;
		elseif(!empty($_REQUEST["name_pattern"])) $show_filter = true;
		else $show_filter = false;
		
		parent::__construct($parameters, $show_filter);
	}
}

?>