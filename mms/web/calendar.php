<?php

require_once dirname(__FILE__) . '/common/global.php';

$resourceUri = '/merchants/' . $merchantService->getMerchantId();
$merchantInfo = json_decode(HttpService::get($resourceUri));

?>
<!doctype html>
<html ng-app="mmsApp">
	<head>
		<title>ikky</title>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-resource.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-animate.min.js"></script>
		<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
		<script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
		<script src="js/jquery.mousewheel.js"></script>
		<script src="js/jquery.kinetic.js"></script>
		<script src="js/app.js"></script>
		<script src="js/controllers.js"></script>
		<script src="js/services.js"></script>
		<script src="js/filters.js"></script>
		<script src="js/animations.js"></script>
		<link rel="stylesheet" type="text/css" href="css/style.css">
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/smoothness/jquery-ui.css">
		<meta charset="UTF-8">
		<meta name="viewport" content="user-scalable=no, minimal-ui, target-densitydpi=device-dpi" />
	</head>
	<body>
		<div class="main-wrapper">
			<div class="header-background"></div>
	    	<div class="header">
	    		<div class="name"><?php echo $merchantInfo->RESTAURANT_NAME ?></div>
				<div class="address"><a href="http://maps.apple.com/?ll=<?php echo $merchantInfo->RESTAURANT_LAT.",".$merchantInfo->RESTAURANT_LONG ?>"><?php echo ucwords(strtolower($merchantInfo->RESTAURANT_ADDRESS)) ?></a></div>
				<div class="notification" ng-controller="NotificationCtrl"><a>{{notification.message}}</a></div>
				<div class="logout"><a href="logout.php">Log Out</a></div>
	    	</div>
	    	
	    	<div class="calendar-wrapper" ng-controller="CalendarCtrl" ng-show="show">
	    		<div class="calendar-header">
	    			<div class="buttons-panel">
		    			<a ng-click="prevMonth()"> &#x25C0 </a>
		    			<a ng-click="goToToday()"> Today </a>
		    			<a ng-click="nextMonth()"> &#x25B6 </a>
		    		</div>
		    		<div class="current-date">
		    			<span class="current-month">{{current.month}}</span>
		    			<span>{{current.year}}</span>
		    		</div>
		    	</div>
	    		<div class="week">
	    			<ul>
						<li><div class="week-cell">Sun</div></li>
						<li><div class="week-cell">Mon</div></li>
						<li><div class="week-cell">Tue</div></li>
						<li><div class="week-cell">Wed</div></li>
						<li><div class="week-cell">Thu</div></li>
						<li><div class="week-cell">Fri</div></li>
						<li><div class="week-cell">Sat</div></li>
					</ul>
	    		</div>
				<div class="calendar" calendar-view>
					<ul>
			      		<li ng-repeat="caldate in caldates" id="{{caldate.id}}" calendar-cell>
			      			<div class="calendar-cell {{caldate.className}}" ng-click="dateClick(caldate)" ng-style="caldate.bgColor">{{caldate.displayDate}}</div>
			      		</li>
					</ul>
				</div>
			</div>
			
			<div class="dayview-wrapper" ng-controller="BookingListCtrl" ng-show="show">
				<div class="dayview-header">
					<div class="buttons-panel">
		    			<a ng-click="back()"> &#x25C0</a>
		    			<a ng-click="back()">Back</a>
		    		</div>
		    		<div class="current-date">
		    			<span>{{current.day}}</span>
		    			<span class="current-month">{{current.month}}</span>
		    			<span>{{current.year}}</span>
		    		</div>
				</div>
				<div class="daylist">
					<table class="datagrid">
						<thead>
							<tr>
								<th><a href="" ng-click="predicate='booking_ts'; reverse=!reverse">Time</a></th>
								<th><a href="" ng-click="predicate='status'; reverse=!reverse">Status</a></th>
								<th><a href="" ng-click="predicate='name'; reverse=!reverse">Customer</a></th>
								<th>Phone</th>
								<th><a href="" ng-click="predicate='no_of_participants'; reverse=!reverse">Participants</a></th>
								<th>Special Request</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-show="loading">
								<td colspan="7">Loading...</td>
							</tr>
							<tr ng-show="!loading && bookings.length==0">
								<td colspan="7">No booking found</td>
							</tr>
							<tr ng-repeat="booking in bookings | orderBy:predicate:reverse" class="bookingRow" booking-row ng-class="{'past':booking.past||booking.status==2}">
								<td>{{booking.booking_ts | bookingDatetime}}</td>
								<td ng-class="{'-1':'red', '0':'blue', '1':'green', '2':'lightgreen'}[booking.status]">{{booking.status | bookingStatus}}</td>
								<td><a class="bookingName" title="Name:{{booking.name}} <br/> Phone:{{booking.phone}}">{{booking.name}}</a></td>
								<td>{{booking.phone}}</td>
								<td>{{booking.no_of_participants}}</td>
								<td>{{booking.special_request}}</td>
								<td><div class="attended-button" ng-class="{'hide':booking.status!='1'&&booking.status!='0'}" ng-click="attended(booking)" ng-disabled="booking.loading">Attended</div></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			
		</div>
	</body>
</html>
