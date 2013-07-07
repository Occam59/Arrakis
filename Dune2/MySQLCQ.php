<?php
	$database_name = $_GET["database_name"];
	$database_user = $_GET["database_user"];
	$database_password = $_GET["database_password"];
	

// Connect to database server
    $hd = mysql_connect("localhost", $database_user, $database_password)
          or die ("Unable to connect");
 
    // Select database
    mysql_select_db ($database_name, $hd)
          or die ("Unable to select database");
   
    // Execute query
    $res = mysql_query($sql, $hd)
          or die ("Unable to run query $sql");
 
?>