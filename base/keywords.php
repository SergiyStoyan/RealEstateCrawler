<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/..";

include_once("$ABSPATH/common/db.php");

class Keywords
{
	//returns an array with UK postcodes
	static public function GetPostcodes($shuffle=true)
	{
		$ps = array();
		$result = Db::Query("SELECT DISTINCT postcode FROM addresses");
		while($r = mysql_fetch_array($result)) $ps[] = $r['postcode'];		
		mysql_free_result($result);
		if($shuffle) shuffle($ps);
		return $ps;
	}
	
	//returns an array with first parts of UK postcodes
	static public function GetPostcode1s($shuffle=true)
	{
		$p1s = array();
		$result = Db::Query("SELECT DISTINCT SUBSTRING_INDEX(postcode, ' ', 1) AS p1 FROM addresses");
		while($r = mysql_fetch_array($result)) $p1s[] = $r['p1'];		
		mysql_free_result($result);
		if($shuffle) shuffle($p1s);
		return $p1s;
	}
	
	//returns an array with UK counties
	static public function GetCounties($shuffle=true)
	{
		$a = array();
		$result = Db::Query("SELECT DISTINCT county FROM addresses");
		while($r = mysql_fetch_array($result)) if($r['county']) $a[] = $r['county'];
		mysql_free_result($result);
		if($shuffle) shuffle($a);
		return $a;
	}
	
	static public function GetTownsBeyondCounties($shuffle=true)
	{		
		$a = array();
		$result = Db::Query("SELECT DISTINCT town FROM addresses WHERE county=''");
		while($r = mysql_fetch_array($result)) if($r['town']) $a[] = $r['town'];
		mysql_free_result($result);
		if($shuffle) shuffle($a);		
		return $a;
	}
	
	static public function GetPostcode1sBeyondCountiesAndTowns($shuffle=true)
	{		
		$a = array();
		$result = Db::Query("SELECT DISTINCT SUBSTRING_INDEX(postcode, ' ', 1) AS p1 FROM addresses WHERE county='' AND town=''");
		while($r = mysql_fetch_array($result)) if($r['town']) $a[] = $r['town'];
		mysql_free_result($result);
		if($shuffle) shuffle($a);		
		return $a;
	}
	
	static public function GetCountiesTownsPostcode1sCoveringUK($shuffle=true)
	{
		$a = array_merge(self::GetCounties(), self::GetTownsBeyondCounties(), self::GetPostcode1sBeyondCountiesAndTowns());
		if($shuffle) shuffle($a);
		return $a;
	}
	
	//SELECT * FROM  (SELECT county FROM addresses WHERE county<>'' GROUP BY county)b, (SELECT * FROM addresses WHERE county='' AND town<>'' GROUP BY town) a WHERE b.county=a.town	
}

/*function print_postcode1s()
{
	error_reporting(E_ALL ^ E_NOTICE);
	include_once("base/shell_utilities.php");
	include_once("base/logger.php");
	include_once("constants.php");
	Logger::$CopyToConsole = TRUE;
	include_once("base/db_utilities.php");

	$result = Db::Query("SELECT p1 FROM (SELECT SUBSTRING_INDEX(postcode, ' ', 1) AS p1 FROM addresses) ps GROUP BY p1") ps);
	while($r = mysql_fetch_array($result)) $p1s[] = $r['p1'];
	mysql_free_result($result);
 	Logger::Write2("'".join("',\n'", $p1s)."'");	
}
print_postcode1s();

function print_counties()
{
	error_reporting(E_ALL ^ E_NOTICE);
	include_once("base/shell_utilities.php");
	include_once("base/logger.php");
	include_once("constants.php");
	Logger::$CopyToConsole = TRUE;
	include_once("base/db_utilities.php");

	$result = Db::Query("SELECT county FROM addresses GROUP BY county");
	while($r = mysql_fetch_array($result)) $counties[] = $r['county'];
	mysql_free_result($result);
 	Logger::Write2("'".join("',\n'", $counties)."'");	
}
print_counties();
*/
	  
?>