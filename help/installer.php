<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

error_reporting(E_ALL);

include_once("common/shell_utilities.php");
Shell::ExitIfTheScriptRunsAlready();

include_once("common/logger.php");
Logger::$WriteToLog = false;
include_once("constants.php");
include_once("common/db.php");
include_once("common/misc.php");
include_once("base/crawler6/table_routines.php");

Logger::Write2("Process owner: ".Shell::GetProcessOwner());
Logger::Write2("STATRED");

Logger::Write2("Creating database connection.");
$c = new mysqli(Constants::DataBaseHost, Constants::DataBaseUser, Constants::DataBasePassword) or Logger::Quit("Connection failed: ".$c->connect_error);
Logger::Write2("Creating database ".Constants::DataBase);
$c->query("CREATE DATABASE ".Constants::DataBase) or Logger::Quit("Error while creating database: ".$c->error);
$c->close();

Logger::Write2("Creating table crawlers");
TableRoutines::CreateCrawlersTable();

Logger::Write2("COMPLETED");
?>