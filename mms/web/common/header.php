<?php

require_once dirname(__FILE__) . '/global.php';

$resourceUri = '/merchants/' . $merchantService->getMerchantId();
$merchantInfo = json_decode(HttpService::get($resourceUri));

?>
<!doctype html>
<html ng-app="mmsApp">
	<head>
		<title>ikky</title>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-resource.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-route.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-animate.min.js"></script>
		<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
		<script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
		<script src="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		<script src="js/jquery.mousewheel.js"></script>
		<script src="js/jquery.kinetic.js"></script>
		<script src="js/app.js"></script>
		<script src="js/controllers.js"></script>
		<script src="js/services.js"></script>
		<script src="js/filters.js"></script>
		<script src="js/animations.js"></script>
		<script src="js/moment.min.js"></script>
		<script src="js/datetimepicker.js"></script>
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/smoothness/jquery-ui.css">
		<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
		<link rel="stylesheet" type="text/css" href="css/style.css">
		<meta charset="UTF-8">
		<meta name="viewport" content="user-scalable=no, minimal-ui, target-densitydpi=device-dpi" />
	</head>
	<body>
		<div class="main-wrapper">
			<div class="header-background"></div>
	    	<div class="header">
	    		<div class="name"><?php echo $merchantInfo->RESTAURANT_NAME ?></div>
				<div class="address"><?php echo ucwords(strtolower($merchantInfo->RESTAURANT_ADDRESS)) ?></div>
				<div class="notification" ng-controller="NotificationCtrl"><a>{{notification.message}}</a></div>
				<div class="button logout"><a href="logout.php">Log Out</a></div>
	    	</div>