<?php


require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_FusiontablesService.php';
include ('lib/db.php');
include ('lib/db_lib.php');
include ('_gfusion-_lib.php');


const TABLE_ID= '1cQo3XMkmidvQSHsRJ-7VxbsPLI-AsawgUQPDGgM';




const CLIENT_ID = '594437102200.apps.googleusercontent.com';
const FT_SCOPE = 'https://www.googleapis.com/auth/fusiontables';
const SERVICE_ACCOUNT_NAME = '594437102200@developer.gserviceaccount.com';
const KEY_FILE = '0de0d369baea92b7596db90556c0d68b4a3a1fd7-privatekey.p12';
const APPLICATION_NAME = 'crawler-stats';
const DEVELOPER_KEY = 'AIzaSyB-xCJ02A98uFSXh9Yw_9KAdt1VHzDb48Y';

$client = new Google_Client();
$client->setApplicationName(APPLICATION_NAME);
$client->setClientId(CLIENT_ID);
$client->setDeveloperKey(DEVELOPER_KEY);

//add key
$key = file_get_contents(KEY_FILE);
$client->setAssertionCredentials(new Google_AssertionCredentials(
    SERVICE_ACCOUNT_NAME,
    array(FT_SCOPE),
    $key)
);

$dSrv= new Google_FusiontablesService($client);
$dSrv->query->sql("delete from ".TABLE_ID);


$re_csv="";
//$today=gmdate("Y-m-d");
//$today=gmdate("2013-01-16");

$statuses=array_unique(array_map(	function ($s) { return trim($s,"!"); },
				columnSet('status') ) );
//print_r($features);

//print_r(getCrawlers());
//exit;
foreach (getCrawlers() as $cr) {
//	echo($cr['id']); echo("\n");
	foreach ($statuses as $s) {
//		echo($s); echo("\n");

//		echo("SELECT REPLACE(town,'!','') as t, COUNT(*) as c FROM products".$cr['prefix']."_".$cr['id']." WHERE status like '%".$s."%' GROUP BY t \n");
		$q=mysql_query("SELECT REPLACE(town,'!','') as t, COUNT(*) as c FROM products".$cr['prefix']."_".$cr['id']." WHERE status like '%".$s."%' GROUP BY t");
		if (!$q) continue;
		while ($r=mysql_fetch_assoc($q)) {
//			echo(getCSV( array($cr['id'], $r['t'], $s, $r['c']) ));
			$re_csv .= getCSV( array($cr['id'], $r['t'], $s, $r['c']) );
		}
	}
	$q=mysql_query("SELECT REPLACE(town,'!','') as t, COUNT(*) as c FROM products".$cr['prefix']."_".$cr['id']." WHERE status='' GROUP BY t");
	if (!$q) continue;
	while ($r=mysql_fetch_assoc($q)) {
//		echo(getCSV( array($cr['id'], $r['t'], '', $r['c']) ));
		$re_csv .= getCSV( array($cr['id'], $r['t'], '', $r['c']) );
	}
}

//echo ($re_csv);
//exit;
$uSrv= new Google_FusiontablesUploadService($client);
$iSR= $uSrv->import;

$iSR->import(TABLE_ID, $re_csv);



?>