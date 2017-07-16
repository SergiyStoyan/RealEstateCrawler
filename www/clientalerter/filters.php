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

$f = new filters_filter();

$is = array();
if(!empty($_REQUEST["client_id"])) $is[] = "<a href='update_filter.php?action=new&client_id=".$_REQUEST["client_id"]."'>Add filter</a>";
if(!$f->IsFilterVisible()) $is[] = "<a href='#' onclick=\"document.getElementById('".$f->BlockId."').style.display='block'; this.style.display='none'; return false;\">Set client";

$m = new Menu2($is);
$m->Display();

print("<div align='left'>");
$f->Display();
print("</div>");

$t = new filters_table();
$t->Display();

include_once("end.php");
?>