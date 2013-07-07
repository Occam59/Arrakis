<?php

	include 'StartUW.php';

	$sql = "SELECT Movies.*, LastPlayed, LastPosition FROM Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID ";
	
	$filter = $_GET["movie"];
	if($filter != 'all')
	{
		$sql .= "WHERE Movies.ID=$filter";
	}

	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movies/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 10000))
	{

		// Assigning variables from cell values
		$id = $row["ID"];
		$path = $row["MoviePath"];
		$title = $row["SortTitle"];
		$caption = str_replace(".", " ", $title);
		$caption = str_replace("&", "&amp;", $caption);
		$path = str_replace("\\", "/", $path);
		$dir = str_replace("/Movies/", "/_dune/00/99/", $path);
		$dir = substr($dir, 0, strrpos($dir, "/"));
		$dir = str_replace("&", "&amp;", $dir);
		$trailer = $row["Trailer"];
		$rating = $row["Rating"];
		$rd = $row["ReleaseDate"];
		$y = substr($rd, -4);
		$m = substr($rd, -7, 2);
		$d = substr($rd, 0, strlen($rd)-8);
		$rd = sprintf("%04d-%02d-%02d", $y, $m, $d);
		$watched = isset($row["LastPosition"]) && $row["LastPosition"] == 0 ? 1 : 0;
		$genres = $row["Genre"];
		$lastplayed = $row["LastPlayed"];
		
		$movie = $xml->addChild('movie');
		$movie->addChild('id', "$id");
		$movie->addChild('caption', "$caption");
		$movie->addChild('poster_url', "smb:$dir/icon.aai");
		$movie->addChild('background_url', "smb:$dir/background.aai");
		$movie->addChild('media_url', "smb:$path");
		$movie->addChild('release_date', "$rd");
		$movie->addChild('rating', "$rating");
		$movie->addChild('IMDB', $row["Imdb"]);
		$movie->addChild('watched', "$watched");
		$movie->addChild('date_added', date('Y-m-d', $row[DateAdd]));
		if($trailer != "") {
			$movie->addChild('trailer_url', "$trailer");
		}
		$movie->addChild('genres', " $genres ");
		$movie->addChild('lastplayed', "$lastplayed");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>