<?php

$id=$argv[1];

// Displays a multi-dimensional array as a HTML unordered lists.
function displayTree($array) {
     $newline = "<br>";
     foreach($array as $key => $value) {    //cycle through each item in the array as key => value pairs
         if (is_array($value) || is_object($value)) {        //if the VALUE is an array, then
            //call it out as such, surround with brackets, and recursively call displayTree.
             $value = "Array()" . $newline . "(<ul>" . displayTree($value) . "</ul>)" . $newline;
         }
        //if value isn't an array, it must be a string. output its' key and value.
        $output .= "[$key] => " . $value . $newline;
     }
     return $output;
}

class Logger {
	public static function Write2($a=NULL, $b=NULL) {
		return;
	}
}

function utf8conv($t) {
	return iconv('LATIN1','UTF-8//TRANSLIT',$t);
}


include ('lib/db.php');

include ('/home/crawler/app/parser/value_parser.php');

$crawler_type=FALSE;
$crawler_table_prefix='XXX';

function setCrawlerType() {
	global $crawler_type, $crawler_table_prefix, $crawler;
	if (!mysql_fetch_array(mysql_query("show tables like 'products_$crawler'"))) {
		$crawler_type='auction';
		$crawler_table_prefix='2';
	} else {
		$crawler_type='sale';
		$crawler_table_prefix='';
	}

}

if (!$id) {
	echo "Usage: checkHighlight.php <recordID>\n";
	exit;
}

$id_a=explode('+',$id);
$crawler=$id_a[0];
$id=$id_a[1];
setCrawlerType();

$wh="id ='".mysql_real_escape_string($id)."'";
$r=mysql_fetch_assoc(mysql_query("select * from products${crawler_table_prefix}_$crawler where ".$wh));
if ($r === FALSE) {
	echo "ID: $crawler+$id record NOT FOUND\n";
	exit;
}


$vp= new ValueParser($r);
$highlight=array(	'bedroom_number' => 'BedroomNumber',
			'type' => 'Type',
			'price' => 'Price',
			'status' => 'Status',
			'tenure' => 'Tenure',
			'is_sold' => 'IsSold',
			'features' => 'Features' );

$re=array();

foreach ($highlight as $hk => $hv) {
	$m=array();
	$f='Parse'.$hv;
	$vp->$f($m);
	$re[$hk]=$m;
}

print_r($re);

?>