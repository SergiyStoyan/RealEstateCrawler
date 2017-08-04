<?php

function extractRegexValue($str,$rx) {
	if ( !preg_match($rx,$str,$m) ) {
//		print_r("NOT MATCHED /".$str."/".$rx."/"); echo "\n";
		return FALSE;
	}
//	print_r($m); echo "\n";
	return $m[1];
}

function columnEnum($c) {
	global $DB_NAME;

	echo("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME =  'products_' AND COLUMN_NAME =  '".$c."' AND TABLE_SCHEMA='".$DB_NAME."'");

	$q=mysql_query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME =  'products_' AND COLUMN_NAME =  '".$c."' AND TABLE_SCHEMA='".$DB_NAME."'");
	$r=mysql_fetch_assoc($q);
	$_enum=array_map(	function ($s) { return trim($s,"'"); },
				explode(	',',
						extractRegexValue($r['COLUMN_TYPE'], "@^enum\((.*)\)$@is") ) );

	return $_enum;
}


?>