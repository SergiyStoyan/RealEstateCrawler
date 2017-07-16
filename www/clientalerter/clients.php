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

$f = new clients_filter();

$is = array();
$is[] = "<a href='update_client.php?action=new'>New client</a>";
if(!$f->IsFilterVisible()) $is[] = "<a href='#' onclick=\"document.getElementById('".$f->BlockId."').style.display='block'; return false;\">Search";

$m = new Menu2($is);
$m->Display();

print("<div align='left'>");
$f->Display();
print("</div>");

$t = new clients_table();
$t->Display();

include_once("end.php");
?>
