<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************
include_once("base/menu.php");
class menu1 extends Menu
{
	function __construct()
	{
		$menu = array("Clients"=>"clients.php", "Filters"=>"filters.php", "Alerts"=>"alerts.php");

		parent::__construct($menu);
	}
}
?>