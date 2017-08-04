<?php
include ('lib/db.php');
include ('lib/db_lib.php');






include ('/home/crawler/app/base/crawler6/table_routines.php');

$table_names_arr= TableRoutines::GetProductsTables();

	$q=mysql_query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME =  '".$table_names_arr[0]."' AND COLUMN_NAME =  '_state' AND TABLE_SCHEMA='".$DB_NAME."'");
	$r=mysql_fetch_assoc($q);
	$_enum=array_map(	function ($s) { return trim($s,"'"); },
				explode(	',',
						extractRegexValue($r['COLUMN_TYPE'], "@^enum\((.*)\)$@is") ) );




//$_states= array ('new','parsed','deleted','debug','debug_parsed');

//$_states=columnEnum('_state');

echo json_encode($_enum);

?>