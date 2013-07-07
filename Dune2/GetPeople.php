<?php

	$people = $_GET["people"];
	$type = $_GET["type"];
	$id=substr($people,2);
	switch(substr($people,0,2))
	{
		case 'm.':
				if($type == "actor")
				{
					$sql = "SELECT ActorName As Name, Role, thumb FROM MoviesActors LEFT JOIN Actors On ActorName=Name WHERE MovieID=$id ORDER BY MoviesActors.ID";
				}
				else
				{
					$sql = "SELECT Director As Name, thumb FROM MoviesDirectors LEFT JOIN Actors On Director=Name WHERE MovieID=$id ORDER BY MoviesDirectors.ID";
				}
				break;
			case 't.';
				if($type == "actor")
				{
					$sql = "SELECT ActorName As Name, Role, thumb FROM TVShowActors LEFT JOIN Actors On ActorName=Name WHERE TVShowID=$id ORDER BY TVShowActors.ID";
				}
				else
				{
					$sql = "SELECT Director As Name, thumb FROM TVShowsDirectors LEFT JOIN Actors On Director=Name WHERE TVShowID=$id ORDER BY TVShowsDirectors.ID";
				}
			break;
	}
	// Connect to database server
	$hd = mysql_connect("localhost", "root", "admin")
	or die ("Unable to connect");
	
	// Select database
	mysql_select_db ("dune", $hd)
	or die ("Unable to select database");
	 
	// Execute query
	$res = mysql_query($sql, $hd)
	or die ("Unable to run query $sql");

	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><people/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 10000))
	{

	        // Assigning variables from cell values
        $name = $row["Name"];
        $thumb = $row["thumb"];
        $role = "";
        if(array_key_exists("Role", $row))
        {
        	$role = $row["Role"];
        }
				
		$movie = $xml->addChild('person');
		$movie->addChild('name', "$name");
		$movie->addChild('role', "$role");
		$movie->addChild('thumb_url', "$thumb");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>