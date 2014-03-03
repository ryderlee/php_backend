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
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-route.js"></script>
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
				<div class="address"><?php echo ucwords(strtolower($merchantInfo->RESTAURANT_ADDRESS)) ?></div>
				<div class="notification" ng-controller="NotificationCtrl"><a>{{notification.message}}</a></div>
				<div class="logout"><a href="logout.php">Log Out</a></div>
	    	</div>
	    	
	    	<div class="calendar-wrapper" ng-controller="CalendarCtrl" ng-show="show">
	    		<div class="calendar-header">
	    			<div class="buttons-panel">
	    				<div ng-click="prevMonth()" class="lightbutton">
	    					<a>&#x25C0</a>
	    				</div>
		    			<div ng-click="goToToday()" class="lightbutton">
		    				<a>Today</a>
	    				</div>
		    			<div ng-click="nextMonth()" class="lightbutton">
		    				<a>&#x25B6</a>
		    			</div>
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
			      			<div class="calendar-cell {{caldate.className}}" ng-click="dateClick(caldate)" ng-style="caldate.bgColor">{{caldate.displayDate}}</div></a>
			      		</li>
					</ul>
				</div>
			</div>
			
			<div class="dayview-wrapper" ng-controller="BookingListCtrl" ng-show="show">
				<div class="dayview-message-table" ng-show="loading">
					<div class="dayview-message-cell">Loading...</div>
				</div>
				<div class="dayview-header">
					<div class="buttons-panel">
						<div ng-click="back()" class="lightbutton">
							<a>&#x25C0&nbsp;&nbsp;Back</a>
						</div>
		    		</div>
		    		<div class="current-date">
		    			<span>{{current.day}}</span>
		    			<span class="current-month">{{current.month}}</span>
		    			<span>{{current.year}}</span>
		    		</div>
				</div>
				<div class="daylist" ng-hide="loading">
					<table class="datagrid">
						<thead>
							<tr>
								<!-- <th class="time"><a href="" ng-click="predicate='booking_ts'; reverse=!reverse">Time</a></th>
								<th class="name"><a href="" ng-click="predicate='name'; reverse=!reverse">Name</a></th>
								<th class="part"><a href="" ng-click="predicate='no_of_participants'; reverse=!reverse">#</a></th>
								<th class="status"><a href="" ng-click="predicate='status'; reverse=!reverse">Status</a></th> -->
								<th class="time">Time</th>
								<th class="name">Name</th>
								<th class="part">#</th>
								<th class="status">Status</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-show="!loading && bookings.length==0">
								<td class="center" colspan="4">No booking found</td>
							</tr>
							<tr ng-repeat="booking in bookings | orderBy:predicate:reverse" class="bookingRow" booking-row ng-class="{'past':booking.past||booking.status==2}" ng-click="showDetail(booking)">
								<td class="time">{{booking.booking_ts | bookingDatetime}}</td>
								<td class="customer"><div class="picture"><img ng-if="booking.pictureSmall != null" ng-src="{{booking.pictureSmall}}"></div><div class="name">{{booking.name}}</div></td>
								<td class="part">{{booking.no_of_participants}}<span ng-if="booking.special_request">&nbsp;*</span></td>
								<td class="status" ng-class="{'-1':'red', '0':'blue', '1':'green', '2':'lightgreen'}[booking.status]">{{booking.status | bookingStatus}}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			
			<div class="bookingdetail-wrapper" ng-controller="BookingDetailCtrl" ng-show="show">
				<div class="bookingdetail-message-table" ng-hide="booking">
					<div class="bookingdetail-message-cell">&#8604;&nbsp;Click on a booking to show details</div>
				</div>
				<div class="bookingdetail" ng-if="booking">
					<div class="bookingdetail-basic">
						<div class="bookingdetail-cover">
							<div class="bookingdetail-profile-pic"><img ng-src="{{booking.picture}}"/></div>
							<div class="bookingdetail-current">
								<div>
									<div class="bookingdetail-name">{{booking.name}}</div>
									<div class="bookingdetail-info">reserved a table for <span>{{booking.no_of_participants}}</span> at <span>{{booking.booking_ts | bookingDatetime}}</span></div>
									<div class="bookingdetail-buttonpanel">
										<div class="lightbutton" ng-class="{'hide':booking.status!='1'&&booking.status!='0'}" ng-click="attend(booking)" ng-disabled="booking.loading">
											<a>&#10004;&nbsp;Attend</a>
										</div>
										<div class="lightbutton" ng-class="{'hide':booking.status!='1'&&booking.status!='0'}" ng-click="cancel(booking)" ng-disabled="booking.loading">
											<a>&#10008;&nbsp;Cancel</a>
										</div>
									</div>
								</div>
							</div>
							<div class="clear"></div>
						</div>
						<div>
							<div>
								<div class="bookingdetail-col1">
									<table class="bookingdetail-table">
										<tr><td>Phone:</td><td>{{booking.phone}}</td></tr>
										<tr><td>Status:</td><td ng-class="{'-1':'red', '0':'blue', '1':'green', '2':'lightgreen'}[booking.status]">{{booking.status | bookingStatus}}</td></tr>
									</table>
								</div>
								<div class="bookingdetail-col2">
									<table class="bookingdetail-table">
										<tr ng-if="booking.special_request"><td class="bookingdetail-request">Special Request:</td><td ng-bind-html="booking.special_request"></td></tr>
									</table>
								</div>
								<div class="clear"></div>
							</div>
							<div class="bookingdetail-tables-line"></div>
							<div class="bookingdetail-rating">
								<table class="bookingdetail-table">
									<tr><td>Ratings:</td><td>4.5/5</td></tr>
									<tr ng-if="booking.status=='2'"><td>Your Rating:</td>
										<td>
											<div class="lightbutton"><a>5</a></div>
											<div class="lightbutton"><a>4</a></div>
											<div class="lightbutton"><a>3</a></div>
											<div class="lightbutton"><a>2</a></div>
											<div class="lightbutton"><a>1</a></div>
										</td>
									</tr>
									<tr ng-if="booking.status=='2'"><td>Your Comment:</td><td><textarea></textarea></td></tr>
									<tr ng-if="booking.status=='2'"><td></td><td><div class="lightbutton"><a>Submit</a></div></td></tr>
								</table>
							</div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="bookingdetail-history">
						<div class="bookingdetail-history-title">Recent Bookings</div>
						<div class="bookingdetail-history-message" ng-show="loading">
							Loading...
						</div>
						<div ng-hide="loading">
							<table class="datagrid">
								<thead>
									<tr><th>Date & Time</th><th>#</th><th>Status</th></tr>
								</thead>
								<tbody>
									<tr ng-show="!loading && histories.length==0">
										<td class="center" colspan="3">No other booking found</td>
									</tr>
									<tr ng-repeat="history in histories | orderBy:predicate:reverse"><td>{{history.booking_ts | historyDatetime}}</td><td>{{history.no_of_participants}}</td><td ng-class="{'-1':'red', '0':'blue', '1':'green', '2':'lightgreen'}[history.status]">{{history.status | bookingStatus}}</td></tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</body>
</html>
