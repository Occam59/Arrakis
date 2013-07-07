<?php

	$sql = "SELECT Movies.ID FROM Movies ";

	$filter = $_GET["movie"];
	$filter_type = substr($filter, 0, 2);
	$filter = substr($filter, 2);
	switch ($filter_type) 
	{
		case "s.":
			switch(substr($filter, 0,5))
			{
				case "00/00":
					$sql .= "ORDER BY SortTitle";
					break;
				case "00/01":
					$sql .= "ORDER BY DateAdd Desc";
					break;
				case "00/02":
					$sql .= "ORDER BY Substr(ReleaseDate, -4) desc,  Substr(ReleaseDate, -7,  2) desc, abs(ReleaseDate) desc";
					break;
				case "00/03":
					$sql .= "ORDER BY Rating Desc";
					break;
				case "00/04":
					$genre = substr($filter, 6);
					$sql = "SELECT MoviesGenres.MovieID AS ID FROM MoviesGenres WHERE MoviesGenres.Genre=\"$genre\" ";
					$sql .= "ORDER BY SortTitle";
					break;
				case "00/05":
					$index = substr($filter, 6);
					$sql .= "WHERE FIND_IN_SET(Left(SortTitle,1),\"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z\")=$index ";
					$sql .= "ORDER BY SortTitle";
					break;
				case "00/06":
					$index = substr($filter, 6);
					$sql = "SELECT Movies.ID FROM Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID ";
					$sql .= "WHERE ISNULL(LastPosition) OR LastPosition!=0 ";
					switch($index)
					{
						case 'Title':
							$sql .= "ORDER BY SortTitle";
							break;
						case 'ReleaseDate':
							$sql .= "ORDER BY Substr(ReleaseDate, -4) desc,  Substr(ReleaseDate, -7,  2) desc, abs(ReleaseDate) desc";
							break;
						case 'DateAdded':
							$sql .= "ORDER BY DateAdd Desc";
							break;
						default:
							break;
					}
					break;
				default:
				break;
			}
			break;
		case "p.":
			$type = $_GET["type"];
			if($type == "actor")
			{
				$sql = "SELECT MoviesActors.MovieID AS ID FROM MoviesActors WHERE MoviesActors.ActorName=\"$filter\"";
			}
			else 
			{
				$sql = "SELECT MoviesDirectors.MovieID AS ID FROM MoviesDirectors WHERE MoviesDirectors.Director=\"$filter\"";
			}
			break;
		default:
	}
	
	include 'MySQLCQ.php';
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movieids/>');

	$i = 0;
	while(($row = mysql_fetch_assoc($res)) && ($i++ < 10000))
	{
		$id = $row["ID"];
		$xml->addChild('id', "$id");
	}
	
	Header('Content-type: text/xml');
	print($xml->asXML());

?>