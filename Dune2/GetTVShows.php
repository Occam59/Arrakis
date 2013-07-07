<?php

// http://192.168.1.86/_d2/GetMovies.php?movie=s.00/00&time=1371645358
// http://192.168.1.86/_d2/GetMovies.php?movie=s.00/00&time=1371645807
// http://192.168.1.86/_d2/GetMovies.php?movie=p.Zooey Deschanel&time=1371645807

	$sql = "SELECT * FROM TVShows ";

	$filter = $_GET["tvshow"];
	$filter_type = substr($filter, 0, 2);
	$filter = substr($filter, 2);

	switch ($filter_type) {
		case 's.':
			switch(substr($filter, 0,5))
			{
				case "01/00":
					$sql .= "ORDER BY Title";
					break;
				case "01/01":
					$sql = "SELECT * FROM TVShows, (SELECT TVShowID, MAX(Aired) AS ID1 FROM TVEps WHERE TVEpPathID > 0 GROUP BY TVShowID) A WHERE A.TVShowID = TVShows.ID ";
					$sql .= "ORDER BY A.ID1 Desc";
					break;
				case "01/02":
					$sql = "SELECT TVShows.ID, TVShows.Title, TVShowPath, Premiered FROM TVShows, TVEps LEFT JOIN TVWatched ON TVWatched.TVEpPathID = TVEps.TVEpPathID ";
					$sql .= "WHERE TVShows.ID = TVEps.TVSHowID AND TVEps.TVEpPathID > 0 AND (TVWatched.TVEpPathID IS NULL OR LastPosition > 0) Group by TVShows.Title, TVShowPath, ID, Premiered ";
					switch (substr($filter,6)) {
				    	case "Title":
							$sql .= "ORDER by TVShows.Title";
							break;
				    	case "LastPlayed":
							$sql = "SELECT TVShows.ID, TVShows.Title, TVShowPath, Premiered FROM TVShows, TVEps LEFT JOIN TVWatched ON TVWatched.TVEpPathID = TVEps.TVEpPathID ";
							$sql .= "WHERE TVShows.ID = TVEps.TVSHowID AND TVEps.TVEpPathID > 0 Group by TVShows.Title, TVShowPath, ID, Premiered ";
				    		$sql .= "ORDER BY MAX(LastPlayed) DESC";
							break;
						case "RecentlyAired":
							$sql .= "ORDER by MAX(Aired) DESC";
							break;
				   		case "OldestAired":
							$sql .= "ORDER by MIN(Aired) DESC";
							break;
				   		default:
				    }
					break;
				case "01/04":
					$genre = substr($filter, 6);
					$sql = "SELECT TVShows.* FROM TVShowsGenres, TVShows WHERE TVShowsGenres.TVShowID=TVShows.ID ";
					$sql .= "AND TVShowsGenres.Genre=\"$genre\" ";
					$sql .= "ORDER BY Title";
					break;
				case "01/05":
					$index = substr($filter, 6);
					$sql .= "WHERE FIND_IN_SET(Left(Title,1),\"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z\")=$index ";
					$sql .= "ORDER BY Title";
					break;
				break;
			}
			break;
				case "p.":
			$type = $_GET["type"];
			if($type == "actor")
			{
				$sql = "SELECT TVShows.* FROM TVShowActors, TVShows WHERE TVShows.ID=TVShowActors.TVShowID AND TVShowActors.ActorName=\"$filter\"";
			}
			else 
			{
				$sql = "SELECT TVShows.* FROM TVShowDirectors, WHERE TVShows.ID=TVShowDirectors.TVShowID AND TVShowDirectors.Director=\"$filter\"";
			}
			break;
		default:
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

	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tvshows/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 1000))
	{

		// Assigning variables from cell values
		$id = $row["ID"];
		$path = $row["TVShowPath"];
		$title = $row["Title"];
		$caption = str_replace(".", " ", $title);
		$caption = str_replace("&", "&amp;", $caption);
		$path = str_replace("\\", "/", $path);
		$dir = str_replace("/TVShows/", "/_dune/01/99/", $path);
		$dir = str_replace("&", "&amp;", $dir);
		$rd = $row["Premiered"];
		$genres = $row["Genres"];
		
		$movie = $xml->addChild('tvshow');
		$movie->addChild('id', "$id");
		$movie->addChild('caption', "$caption");
		$movie->addChild('poster_url', "smb:$dir/icon.aai");
		$movie->addChild('background_url', "smb:$dir/background.aai");
		$movie->addChild('release_date', "$rd");
		$movie->addChild('genres', " $genres ");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>