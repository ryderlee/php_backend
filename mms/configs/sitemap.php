<?php

// information in array: array(<URL>, <AUTH>);

define('SITEMAP__PAGES', serialize(array(
	array('index.php', false),
	array('login.php', false),
	array('calendar.php', true),
	array('settings.php', true)
)));

?>