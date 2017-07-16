<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

ob_start();
include_once("head.php");
include_once("base/login.php");

?>

<table width=100%'>
<tr><td align='center' height='150px' valign='center'><h4>Reporting Tool</h4></td></tr>
<tr><td align='center'>

<?
$l = new Login("login");
$l->Display();

?>
</td></tr></table>

<?
include_once("end.php");
ob_flush();
?>
