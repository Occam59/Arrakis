<?php

	$movie = $_GET["movie"];
	$watched = $_GET["watched"];
	
	$sql ="SELECT Movies.ID, MoviePath, LastPosition FROM Movies LEFT JOIN MoviesWatched ON Movies.ID=MoviesWatched.MovieID WHERE Movies.ID = $movie"; 

	include 'MySQLCQ.php';
	$row = mysql_fetch_assoc($res);
	$mp = str_replace("\\", "\\\\", $row["MoviePath"]);
	$mp = str_replace("'", "\'", $mp);

	$sql = "";
	if(isset($row['LastPosition']))
	{
		if($watched === 'true')
		{
			$sql = "UPDATE MoviesWatched Set FileName = '".$mp."', LastPosition= 0 WHERE MovieID=$movie";
		}
		else
		{
			$sql = "DELETE FROM MoviesWatched WHERE MovieID=$movie";
		}
	}
	else
	{
		if($watched === 'true')
		{
			$sql = "INSERT INTO MoviesWatched (FileName, PlayCount, LastPlayed, LastPosition, MovieID) VALUES('".$mp."', 1, 1, 0, $movie)"; 
		}
	}
	
	if($sql != "")
	{
		$res = mysql_query($sql, $hd) or die ("Unable to execute query $sql");
//		echo $sql."\n";
	}
	
	echo 'Success';

?>