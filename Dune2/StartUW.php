<?php

	$database_name = $_GET["database_name"];
	$database_user = $_GET["database_user"];
	$database_password = $_GET["database_password"];
	$ipaddress = $_SERVER["REMOTE_ADDR"];

	$cmd="/usr/local/apache/bin/php -f ./UpdateWatched.php $database_name $database_user $database_password $ipaddress > /dev/null 2>/dev/null &";
	$st = exec($cmd);
?>