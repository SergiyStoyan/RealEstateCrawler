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

include_once("base/shell_utilities.php");

Shell::ExitIfTheScriptRunsAlready();

set_time_limit(0);
ini_set('memory_limit', '-1');

include_once("common/logger.php");
include_once("constants.php");
include_once("common/db.php");

Logger::Write2("STATRED");

mysql_query("SET AUTOCOMMIT=0");
//$a = Db::Query("SHOW VARIABLES LIKE 'query_cache_size'");
//while($r = mysql_fetch_array($a)) Logger::Write2($r);
//exit();
 //SET max_heap_table_size = 1024*1024;
//Db::Query("SET GLOBAL query_cache_size=100000000");

//SELECT `county`,`traditional_county`,`town`,`village`,`throughfare` FROM `addresses` WHERE village<>'' AND INSTR("Verney Junction, £995,000'", village)>0
	
Logger::Write2("1");
import_prt("PAF-PRT-UK-CSV_1.csv");
Logger::Write2("1.1");
mysql_query("COMMIT");

Logger::Write2("2");
import_prt("PAF-PRT-UK-CSV_2.csv");
Logger::Write2("2.1");
mysql_query("COMMIT");

Logger::Write2("3");
import_cnt("PCL-CNT-UK-CSV_1.csv");
Logger::Write2("3.1");
mysql_query("COMMIT");

Logger::Write2("4");
import_cnt("PCL-CNT-UK-CSV_2.csv");
Logger::Write2("4.1");
mysql_query("COMMIT");

Logger::Write2("5");
import_grd("PCL-GRD-UK-CSV_1.csv");
Logger::Write2("5.1");
mysql_query("COMMIT");

Logger::Write2("6");
import_grd("PCL-GRD-UK-CSV_2.csv");
Logger::Write2("6.1");
mysql_query("COMMIT");

//Db::Query("SET GLOBAL query_cache_size=0");

/*
//define varchar length
SELECT MAX( LENGTH( county ) ) 
FROM (
SELECT * 
FROM addresses
GROUP BY county
)a
*/

Logger::Write2("COMPLETED");

function import_prt($file)
{
	$rs = file($file);
	foreach($rs as $r) 
	{
		$fs = preg_split("@,@", $r);
		Db::Query("REPLACE addresses SET postcode='".trim3($fs[5])."', throughfare='".trim2($fs[0])."', street='".trim2($fs[1])."', double_dependent_locality='".trim2($fs[2])."', village='".trim2($fs[3])."', town='".trim2($fs[4])."'");
	}	
}

function import_cnt($file)
{
	$rs = file($file);
	foreach($rs as $r) 
	{
		$fs = preg_split("@,@", $r);
		$sql1 =  "addresses SET traditional_county='".trim2($fs[1])."', county='".trim2($fs[2])."'";
		$sql2 = "postcode='".trim3($fs[0])."'";
		Db::Query("UPDATE $sql1 WHERE $sql2") or Db::Query("INSERT $sql1, $sql2");
	}	
}

function import_grd($file)
{
	$rs = file($file);
	foreach($rs as $r) 
	{
		$fs = preg_split("@,@", $r);
		$sql1 = "addresses SET lon=".trim2($fs[7]).", lat=".trim2($fs[8]);
		$sql2 = "postcode='".trim3($fs[0])."'";
		Db::Query("UPDATE $sql1 WHERE $sql2") or Db::Query("INSERT $sql1, $sql2");
	}	
}

function trim2($s)
{
	return addslashes(strtolower(trim($s, " \r\n\"")));
	//return addslashes(preg_replace("@^[\"\s]+|[\"\s]+$@", "", $s));
}

function trim3($s)
{
	return preg_replace("@\s{2,}@", " ", trim2($s));
}


?>