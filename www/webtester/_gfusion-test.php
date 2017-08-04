<?php

/**
 *  outputCSV creates a line of CSV and outputs it to browser    
 */
function outputCSV($array) {
    $fp = fopen('php://output', 'w'); // this file actual writes to php output
    fputcsv($fp, $array);
    fclose($fp);
}
 
/**
 *  getCSV creates a line of CSV and returns it. 
 */
function getCSV($array) {
    ob_start(); // buffer the output ...
    outputCSV($array);
    return ob_get_clean(); // ... then return it as a string!
}



require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_FusiontablesService.php';
include ('lib/db.php');


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
$dSrv->query->sql("delete from 1rOJhFAS-WxuSVwCrt3VAMYBcUie_zdbleRyilHg");


$q=mysql_query("SELECT  'loot_com' AS crawler, town, features, COUNT(*) as c FROM products_loot_com GROUP BY town, features");
$csv="";
while ($r=mysql_fetch_assoc($q))
	$csv .= getCSV( array( $r['crawler'], $r['town'], $r['features'], $r['c']) );

//echo ($csv);
$uSrv= new Google_FusiontablesUploadService($client);
$iSR= $uSrv->import;

$iSR->import('1rOJhFAS-WxuSVwCrt3VAMYBcUie_zdbleRyilHg', $csv);



?>