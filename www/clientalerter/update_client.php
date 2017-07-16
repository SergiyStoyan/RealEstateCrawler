<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


include_once("head.php");

include_once(definitions_for(__FILE__));

$m = new menu1();
$m->Display();

$b = new Back();
$is = array($b->GetHtml(), "Edit client");
$m = new Menu2($is);
$m->Display();

$u = new update_client();
$u->Display();

include_once("end.php");
?>
