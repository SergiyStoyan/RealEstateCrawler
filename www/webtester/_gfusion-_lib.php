<?php

function getCrawlers() {
	$re=array();

	$q=mysql_query('select id,state,_last_session_state from crawlers order by id');
	while ($r=mysql_fetch_assoc($q)) {
		if (!mysql_fetch_array(mysql_query("show tables like 'products_$crawler'")))
			$r['prefix']='2';
		else	$r['prefix']='';
		$re[]=$r;
	}

	return $re;
}



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





?>