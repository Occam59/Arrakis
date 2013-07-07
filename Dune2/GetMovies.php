<?php

	$sql = "SELECT Movies.*, LastPlayed, LastPosition FROM Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID ";

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
					$sql = "SELECT Movies.*, LastPosition FROM MoviesGenres, Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID  WHERE MoviesGenres.MovieID=Movies.ID ";
					$sql .= "AND MoviesGenres.Genre=\"$genre\" ";
					$sql .= "ORDER BY SortTitle";
					break;
				case "00/05":
					$index = substr($filter, 6);
					$sql .= "WHERE FIND_IN_SET(Left(SortTitle,1),\"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z\")=$index ";
					$sql .= "ORDER BY SortTitle";
					break;
				case "00/06":
					$index = substr($filter, 6);
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
				$sql = "SELECT Movies.*, LastPosition FROM MoviesActors, Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID WHERE Movies.ID=MoviesActors.MovieID AND MoviesActors.ActorName=\"$filter\"";
			}
			else 
			{
				$sql = "SELECT Movies.*, LastPosition FROM MoviesDirectors, Movies LEFT JOIN MoviesWatched ON MoviesWatched.MovieID=Movies.ID WHERE Movies.ID=MoviesDirectors.MovieID AND MoviesDirectors.Director=\"$filter\"";
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