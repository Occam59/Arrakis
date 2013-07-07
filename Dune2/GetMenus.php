<?php

include 'StartUW.php';

	$file = 'menus.xml';
	
	if (file_exists($file)) {
		header('Content-Description: File Transfer');
//		header('Content-Type: image/aai');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
		exit;
	}
	else {
		echo "Error 2\n";
	}
?>