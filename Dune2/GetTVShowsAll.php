<?php

	include 'StartUW.php';
	
	$sql = "SELECT TVShows.ID AS SID, TVShows.Title AS STitle, TVShows.*, TVEps.*, TVEpPath, LastPlayed, LastPosition FROM TVShows, TVEps, TVEpPaths LEFT JOIN TVWatched ON TVEpPaths.ID=TVWatched.TVEpPathID ";
	$sql .= "WHERE TVShows.ID=TVEps.TVShowID AND TVEps.TVEpPathID=TVEpPaths.ID AND TVEps.TVEpPathID > 0 ";
	$filter = $_GET["tvshow"];
	if($filter != 'all')
	{
		$sql .= "AND TVShows.ID=$filter ";
	}

	$sql .= "Order by TVShows.ID, TVEps.Season, TVEps.Episode";

	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tvshows/>');

	$id = -1;
	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 20000))
	{
		if($id != $row["SID"]) 
		{
			$id = $row["SID"];
			$path = $row["TVShowPath"];
			$title = $row["STitle"];
			$caption = str_replace(".", " ", $title);
			$caption = str_replace("&", "&amp;", $caption);
			$path = str_replace("\\", "/", $path);
			$dir = str_replace("/TVShows/", "/_dune/01/99/", $path);
			$dir = str_replace("&", "&amp;", $dir);
			$rd = $row["Premiered"];
			$genres = $row["Genre"];
			
			$tvshow = $xml->addChild('tvshow');
			$tvshow->addChild('id', "$id");
			$tvshow->addChild('caption', "$caption");
			$tvshow->addChild('poster_url', "smb:$dir/icon.aai");
			$tvshow->addChild('background_url', "smb:$dir/background.aai");
			$tvshow->addChild('release_date', "$rd");
			$tvshow->addChild('genres', " $genres ");
			$episodes = $tvshow->addChild('episodes');
		}
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
		
		$tvshow = $episodes->addChild('episode');
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