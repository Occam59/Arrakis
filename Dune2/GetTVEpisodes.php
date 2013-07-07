<?php

	$tvshow = $_GET["tvshow"];

	$sql ="SELECT TVEps.*, TVEpPath, LastPlayed, LastPosition FROM TVEps, TVEpPaths LEFT JOIN TVWatched ON TVEpPaths.ID=TVWatched.TVEpPathID "; 
	$sql .= "WHERE TVEps.TVEpPathID=TVEpPaths.ID AND TVShowID=$tvshow AND TVEps.TVEpPathID > 0 Order by TVEps.Season, TVEps.Episode";

	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><episodes/>');

	while($row = mysql_fetch_assoc($res))
	{

		// Assigning variables from cell values
		$episode = $row["Episode"];
		$season = $row["Season"];
		$path = $row["TVEpPath"];
		$title = $row["Title"];
		$caption = str_replace(".", " ", $title);
		$caption = str_replace("&", "&amp;", $caption);
		$path = str_replace("\\", "/", $path);
		$path = str_replace("&", "&amp;", $path);
		$dir = str_replace("/TVShows/", "/_dune/01/99/", $path);
		$dir = substr($dir, 0, strrpos($dir, "/"));
		$dir = substr($dir, 0, strrpos($dir, "/"));
		$dir .= sprintf("/%02d/%02d", $season, $episode);
		$watched = isset($row["LastPosition"]) && $row["LastPosition"] == 0 ? 1 : 0;
		$lastplayed = $row["LastPlayed"];
		
		$tvshow = $xml->addChild('episode');
		$tvshow->addChild('id', $row["ID"]);
		$tvshow->addChild('episode', "$episode");
		$tvshow->addChild('season', "$season");
		$tvshow->addChild('caption', "$caption");
		$tvshow->addChild('poster_url', "smb:$dir/icon.aai");
		$tvshow->addChild('watched', "$watched");
		$tvshow->addChild('media_url', "smb:$path");
		$tvshow->addChild('aired', $row["Aired"]);
		$tvshow->addChild('lastplayed', "$lastplayed");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>