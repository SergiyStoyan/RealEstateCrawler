<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************

//error_reporting(E_ALL);

ob_start();

include_once("constants.php");
session_start();
ini_set("session.gc_maxlifetime", Constants::SessionTimeout);

//set_include_path(get_include_path().PATH_SEPARATOR.".\\..".PATH_SEPARATOR."./view");
include_once("db_connection.php");
include_once("base/utilities.php");
include_once("menu1.def.php");
include_once("base/menu2.php");

/*include_once("base/login.php");

$user_permits = array(
"_UNDEFINED"=>array("login.php"),
'admin'=>array('clients.php', 'update_client.php', 'filters.php', 'update_filter.php', 'alerts.php', 'users.php', 'update_user.php', 'login.php', 'index.php'=>'login.php'),
'user'=>array('reports.php', 'login.php', 'index.php'=>'login.php')
);
Login::Authorize($user_permits);*/

//import_request_variables("gP");

function definitions_for($file)
{
	$p = strrpos($file, ".");
	$file = substr_replace($file, ".def", $p, 0);
	return $file;
}

?>
<html>
<head>
<title>Client alert admin</title>
<link rel="stylesheet" href="template.css" type="text/css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body>
<div align="center">

<div style="width:80%;" align="left">









