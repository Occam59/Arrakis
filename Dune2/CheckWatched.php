<?php
	//Allow only one instance
	$fileHandler = fopen ('file_lock_192_168_1_92.lock', "a");
	if (!$fileHandler) {
	    die("unable to open file");
	}
	$hasLock = flock($fileHandler, LOCK_EX | LOCK_NB);
	if (!$hasLock) {
		echo "An instance of update watched is running\n";
	}
	else {
		echo "File not locked: UpdateWatched not running\n";
	}
	//release lock
	flock($fileHandler, LOCK_UN);
	fclose($fileHandler);
	
?>	