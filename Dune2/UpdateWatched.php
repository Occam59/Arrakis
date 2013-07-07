<?php
	$database_name = $argv[1];
	$database_user = $argv[2];
	$database_password = $argv[3];
	$ipaddress = $argv[4];
	$fip = str_replace('.', '_', $ipaddress);

	//Allow only one instance
	$fileHandler = fopen ('file_lock_'.$fip.'.lock', "a");
	if (!$fileHandler) {
	    die("unable to open file");
	}
	$hasLock = flock($fileHandler, LOCK_EX | LOCK_NB);
	if (!$hasLock) {
		die("Another instance is already running");
	}
	$t=time();
	$logs = "Monitor started at $t\n";
	fwrite($fileHandler, $logs, strlen($logs));
		
	$hd = mysql_connect("localhost", $database_user, $database_password)
          or die ("Unable to connect");
 
    // Select database
    mysql_select_db ($database_name, $hd)
          or die ("Unable to select database");
	
	$i=8640;
	
	$player_state = 'Unknown';
	while($player_state != 'standby' && $player_state != '' && $i-- > 0) {
		$player_state = check_status($hd, $ipaddress);
		echo "State: $player_state\n";
		if($player_state != 'standby' && $player_state != '' && $i > 0) {
			sleep(10);
		}
	}
	
	mysql_close($hd);

	$t=time();
	$logs = "Monitor stopped at $t\n";
	fwrite($fileHandler, $logs, strlen($logs));
	//release lock
	flock($fileHandler, LOCK_UN);
	fclose($fileHandler);
	
	
function check_status($hd, $ipaddress)
{
	$url = "http://$ipaddress/cgi-bin/do?cmd=status";
	$str = file_get_contents($url);
	
	$doc = new DOMDocument;
	
	// We don't want to bother with white spaces
	$doc->preserveWhiteSpace = false;
	
	$doc->LoadXML($str);
	
	$xpath = new DOMXPath($doc);
	
	// We starts from the root element
	$player_state = getparam($xpath, "player_state");
	
	if($player_state == 'file_playback')
	{
		process_update($hd, $xpath);
	}
	return $player_state;
}
	
function process_update ($hd, $xpath)
{
	$playback_state = getparam($xpath, "playback_state");
	$playback_url = getparam($xpath, "playback_url");
	$playback_url = substr($playback_url, 4);
	$bmovies = !(stripos($playback_url, "/movies/") === FALSE);
	
	$playback_url = str_replace("/", "\\\\", $playback_url);
	echo "$playback_url\n";
	$playback_position = getparam($xpath, "playback_position");
	$playback_duration = getparam($xpath, "playback_duration");
	
	if($playback_position > 0.1 * $playback_duration)
	{
		echo "update watched\n";
		$id = get_showid($hd, $playback_url, $bmovies ? "Movies" : "TVEpPaths", $bmovies ? "MoviePath" : "TVEpPath");
		echo	 "ID=$id\n";
		if($playback_position > 0.9 * $playback_duration)
		{
		$playback_position = 0;
		}
		update_watched($id, $hd, $playback_url, $playback_position, $bmovies ? "MoviesWatched" : "TVWatched", $bmovies ? "MovieID" : "TVEpPathID");
	}
}

function update_watched($id, $hd, $playback_url, $playback_position, $table, $col)
{
	$sql = "SELECT * FROM $table WHERE $col = $id";
	echo "$sql\n";
	$res = mysql_query($sql, $hd)
		or die ("Unable to run query");
	if ($row = mysql_fetch_assoc($res))
	{
		$count = $row["PlayCount"];
		$lp = $row["LastPlayed"];
		$t = time() - $playback_position;
		if($t > ($lp + 86400))
		{
			$count++;
		}
		$sql = "UPDATE $table SET PlayCount = $count , LastPlayed = $lp , LastPosition = $playback_position WHERE $col = $id";
		echo "$sql\n";
		$res = mysql_query($sql, $hd)
			or die ("Unable to run query");
		}
	else 
	{
		$t = time() - $playback_position;
		$sql = "INSERT INTO $table (FileName, PlayCount, LastPlayed, LastPosition, $col) VALUES (\"$playback_url\", 1, $t, $playback_position, $id)";
		echo "$sql\n";
		$res = mysql_query($sql, $hd)
			or die ("Unable to run query");
	}
//	mysql_commit($hd);
}	
	
function get_showid($hd, $playback_url, $table, $col)
{
		
	$sql = "SELECT ID FROM $table WHERE $col = \"$playback_url\"";
#	echo "$sql\n";
	$res = mysql_query($sql, $hd)
		or die ("Unable to run query");
	$id=0;
	if ($row = mysql_fetch_assoc($res))
	{
		$id = $row["ID"];
	}
	return $id;
}

function getparam($xpath, $p)
{
	$query = '//command_result/param[@name="'.$p.'"]/@value';
	$entries = $xpath->query($query);
	$rc = $entries->length ? $entries->item(0)->nodeValue : '';
//	echo "Found: $p $l {$rc}\n";
	return $rc;
}	
	
	
?>	