<?php

	$sql = "SELECT DISTINCT Genre from MoviesGenres ORDER BY Genre";

	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><genres/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 10000))
	{
		// Assigning variables from cell values
		$xml->addChild('genre', $row['Genre']);
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>