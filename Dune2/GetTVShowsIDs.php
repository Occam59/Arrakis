<?php

	$sql = "SELECT ID FROM TVShows ";

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
					$sql = "SELECT ID FROM TVShows, (SELECT TVShowID, MAX(Aired) AS ID1 FROM TVEps WHERE TVEpPathID > 0 GROUP BY TVShowID) A WHERE A.TVShowID = TVShows.ID ";
					$sql .= "ORDER BY A.ID1 Desc";
					break;
				case "01/02":
					$sql = "SELECT ID, TVShows.Title, TVShowPath, Premiered FROM TVShows, TVEps LEFT JOIN TVWatched ON TVWatched.TVEpPathID = TVEps.TVEpPathID ";
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
					$sql = "SELECT TVShowsGenres.TVShowID AS ID FROM TVShowsGenres ";
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
				$sql = "SELECT TVShowActors.TVShowID AS ID FROM TVShowActors WHERE TVShowActors.ActorName=\"$filter\"";
			}
			else 
			{
				$sql = "SELECT TVShowDirectors.TVShowID AS ID FROM TVShowDirectors WHERE TVShowDirectors.Director=\"$filter\"";
			}
			break;
		default:
	}
	
	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tvshowsids/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 1000))
	{

		// Assigning variables from cell values
		$id = $row["ID"];
		$xml->addChild('id', "$id");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>