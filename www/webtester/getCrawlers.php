<?php
include ('lib/db.php');

$q=mysql_query('select id,state,_last_session_state from crawlers order by id');

$re=array();
while ($r=mysql_fetch_assoc($q))
	$re[]=$r;

echo json_encode($re);

?>