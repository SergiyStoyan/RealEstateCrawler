<?php

$ddb= @mysql_connect('87.117.228.105:3320','db640','Iez8aiv4bei3');
if ($ddb) {
	mysql_select_db('db640',$ddb);
} 


if (!mysql_connect('localhost','maker','e#,aB1-^WI~P')) {
	die('cannot connect to db server');
}
mysql_select_db('real_estate');
//mysql_set_charset('latin1');

$DB_NAME='real_estate';


?>