<?php
include ('lib/db.php');

if (!$ddb)
	die('drupal DB access not available');

print_r($ddb);

?>