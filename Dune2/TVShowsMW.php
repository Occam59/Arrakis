<?php

	$tvshow = $_GET["tvshow"];
	$watched = $_GET["watched"];
	
	$sql = "SELECT TVEps.TVEpPathID, TVEpPath, LastPosition FROM TVEps, TVEpPaths LEFT JOIN TVWatched ON TVEpPaths.ID=TVWatched.TVEpPathID ";
	$sql .= "WHERE TVEps.TVEpPathID=TVEpPaths.ID AND TVEps.TVEpPathID > 0 AND TVEps.TVSHowID=$tvshow ";

	if(array_key_exists("season", $_GET)) 
	{
		$season = $_GET["season"];
		$sql .= "AND TVEps.Season=$season ";
	}
	
	if(array_key_exists("episode", $_GET)) 
	{
		$episode = $_GET["episode"];
		$sql .= "AND TVEps.Episode=$episode ";
	}
	
	include 'MySQLCQ.php';
	while($row = mysql_fetch_assoc($res))
	{
		$ep = str_replace("\\", "\\\\", $row["TVEpPath"]);
		$ep = str_replace("'", "\'", $ep);
		$sql = "";
		if(isset($row['LastPosition']))
		{
			if($watched === 'true')
			{
				$sql = "UPDATE TVWatched Set FileName = '".$ep."', LastPosition= 0 WHERE TVEpPathID=".$row['TVEpPathID'];
			}
			else
			{
				$sql = "DELETE FROM TVWatched WHERE TVEpPathID=".$row['TVEpPathID'];
			}
		}
		else
		{
			if($watched === 'true')
			{
				$sql = "INSERT INTO TVWatched (FileName, PlayCount, LastPlayed, LastPosition, TVEpPathID) VALUES('".$ep."', 1, 1, 0, ".$row['TVEpPathID'].")"; 
			}
		}
		
		if($sql != "")
		{
			$res2 = mysql_query($sql, $hd) or die ("Unable to execute query $sql");
		}
	}
	
	echo 'Success';

?>