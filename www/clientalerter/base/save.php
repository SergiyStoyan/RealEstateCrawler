<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

include_once(dirname(__FILE__)."/../db_connection.php");

$sql = "UPDATE ".$_REQUEST['table']." SET ".$_REQUEST['field']."='".addslashes($_REQUEST['value'])."' WHERE id=".addslashes($_REQUEST['key_id']);
mysql_query($sql) or die("Query failed: $sql\n".mysql_error());
exit("OK");

print "Could not find an appropriate method.";


?>