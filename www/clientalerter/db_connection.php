<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


include_once("constants.php");

$link = mysql_connect(Constants::DataBaseHost, Constants::DataBaseUser, Constants::DataBasePassword) or die('Could not connect! \n'.mysql_error());
mysql_select_db(Constants::DataBase) or die("Could not select database '".Constants::DataBase."'");

?>