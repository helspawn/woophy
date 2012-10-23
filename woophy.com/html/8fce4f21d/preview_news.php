<?php
		$bodynews = "body_newsletter.txt";
		$handle = fopen($bodynews, "r");
		print fread($handle, filesize($bodynews));
		fclose($handle);
?>