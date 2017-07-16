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

$f = new alerts_filter();

$is = array();
//$is[] = "<a href='export.php?".$f->GetCurrentParametersAsQuery()."&data=reports' target='_blank'>Export</a>";
//if($f->IsFilterVisible()) $is[] = "<a href='".$f->GetPageUrlWithClearedFilter()."'>Clear search</a>";
if(!$f->IsFilterVisible()) $is[] = "<a href='#' onclick=\"document.getElementById('".$f->BlockId."').style.display='block'; this.style.display='none'; return false;\">Search</a>";

$m = new Menu2($is);
$m->Display();

print("<div align='left'>");
$f->Display();
print("</div>");

$t = new alerts_table();
$t->Display();

include_once("end.php");
?>