<?php

include dirname(__FILE__) . '/common/header.php';

?>
	    	
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
      			<div class="calendar-cell {{caldate.className}}" ng-click="dateClick(caldate)" ng-style="caldate.bgColor"><span>{{caldate.displayDay}}</span><span class="calendar-month">{{caldate.displayMonth}}</span><span class="calendar-year">{{caldate.displayYear}}</span></div></a>
      		</li>
		</ul>
	</div>
</div>

<div class="dayview-wrapper" ng-controller="BookingListCtrl" ng-show="show">
	<div class="dayview-message-table" ng-show="loading">
		<div class="dayview-message-cell">Loading...</div>
	</div>
	<div class="dayview-message-table" ng-show="bookings.length==0">
		<div class="dayview-message-cell">No booking found</div>
	</div>
	<div class="dayview-header">
		<div class="buttons-panel">
			<div ng-click="back()" class="lightbutton">
				<a>&#x25C0&nbsp;&nbsp;Back</a>
			</div>
			<div ng-click="newBooking()" class="lightbutton">
				<a>&#43;&nbsp;Booking</a>
			</div>
		</div>
		<div class="current-date">
			<span>{{current.day}}</span>
			<span class="current-month">{{current.month}}</span>
			<span>{{current.year}}</span>
		</div>
	</div>
	<div class="daylist" ng-hide="loading||bookings.length==0">
		<ul class="daylist-table">
			<li>
				<div class="daylist-row daylist-header">
					<div class="daylist-picture"></div>
					<div class="daylist-name">Name</div>
					<div class="daylist-tablename">Table</div>
					<div class="daylist-part">#</div>
					<div class="daylist-status">Status</div>
				</div>
			</li>
			<li ng-repeat="booking in bookings | orderBy:predicate:reverse | groupBy:'booking_ts'" booking-row ng-class="{'past':booking.past||booking.status==2, 'conflict':booking.conflict_code!=0}">
				<div class="daylist-time" ng-show="booking.booking_ts_CHANGED">
					{{booking.booking_ts | bookingDatetime}}
				</div>
				<div class="daylist-row" ng-click="showDetail(booking)">
					<div class="daylist-picture"><img ng-if="booking.pictureSmall != null" ng-src="{{booking.pictureSmall}}"></div>
					<div class="daylist-name">{{booking.name}}</div>
					<div class="daylist-tablename">{{booking.table_names}}</div>
					<div class="daylist-part">{{booking.no_of_participants}}<span ng-if="booking.special_request">&nbsp;*</span></div>
					<div class="daylist-status" ng-class="{'-1':'red', '0':'blue', '1':'green', '2':'lightgreen'}[booking.status]">{{booking.status | bookingStatus}}</div>
				</div>
			</li>
		</ul>
		
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
						<div class="bookingdetail-info" ng-hide="editing">
							<div class="bookingdetail-details">reserved a table for <span class="highlight">{{booking.no_of_participants}}</span> at <span class="highlight">{{booking.booking_ts | bookingDatetime}}</span></div>
							<div class="lightbutton" ng-class="{'hide':booking.status!='1'&&booking.status!='0'}" ng-click="edit()" ng-disabled="updating">
								<a>&#9998;&nbsp;Edit</a>
							</div>
							<div class="clear"></div>
						</div>
						<div class="bookingdetail-edit" ng-show="editing">
							<div class="bookingdetail-edit-details">
								<div>
									reserved a table for 
									<select ng-model="newBooking.no_of_participants">
										<option value="1" ng-selected="booking.no_of_participants==1">1</option>
										<option value="2" ng-selected="booking.no_of_participants==2">2</option>
										<option value="3" ng-selected="booking.no_of_participants==3">3</option>
										<option value="4" ng-selected="booking.no_of_participants==4">4</option>
										<option value="5" ng-selected="booking.no_of_participants==5">5</option>
										<option value="6" ng-selected="booking.no_of_participants==6">6</option>
										<option value="7" ng-selected="booking.no_of_participants==7">7</option>
										<option value="8" ng-selected="booking.no_of_participants==8">8</option>
										<option value="9" ng-selected="booking.no_of_participants==9">9</option>
										<option value="10" ng-selected="booking.no_of_participants==10">10</option>
										<option value="11" ng-selected="booking.no_of_participants==11">11</option>
										<option value="12" ng-selected="booking.no_of_participants==12">12</option>
										<option value="13" ng-selected="booking.no_of_participants==13">13</option>
										<option value="14" ng-selected="booking.no_of_participants==14">14</option>
										<option value="15" ng-selected="booking.no_of_participants==15">15</option>
										<option value="16" ng-selected="booking.no_of_participants==16">16</option>
										<option value="17" ng-selected="booking.no_of_participants==17">17</option>
										<option value="18" ng-selected="booking.no_of_participants==18">18</option>
										<option value="19" ng-selected="booking.no_of_participants==19">19</option>
										<option value="20" ng-selected="booking.no_of_participants==20">20</option>
									</select> at
								</div>
								<div class="dropdown">
									<a class="dropdown-toggle" id="dropdown2" role="button" data-toggle="dropdown" data-target="#">
										<div class="input-group"><span class="form-control">{{ newBooking.booking_ts | date:'yyyy-MM-dd HH:mm:ss' }}</span><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										</div>
									</a>
									<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
										<datetimepicker data-ng-model="pickerDate" data-on-set-time="onTimeSet" data-datetimepicker-config="{ dropdownSelector: '#dropdown2', minuteStep: 15, startView: 'hour' }"/>
									</ul>
								</div>
								
							</div>
							<div class="clear"></div>
						</div>
						<div class="bookingdetail-buttonpanel">
							<div class="lightbutton" ng-hide="(booking.status!='1'&&booking.status!='0')||editing" ng-click="attend()" ng-disabled="updating">
								<a>&#10004;&nbsp;Attend</a>
							</div>
							<div class="lightbutton" ng-hide="(booking.status!='1'&&booking.status!='0')||editing" ng-click="cancel()" ng-disabled="updating">
								<a>&#10008;&nbsp;Cancel</a>
							</div>
							<div class="lightbutton" ng-hide="!editing" ng-click="save(false)" ng-disabled="updating">
								<a>&#8629;&nbsp;Save</a>
							</div>
							<div class="lightbutton" ng-hide="!editing" ng-click="discard()" ng-disabled="updating">
								<a>&#8635;&nbsp;Discard</a>
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
							<tr ng-hide="editing"><td>Table:</td><td>{{booking.table_names}}</td></tr>
							<tr ng-show="editing"><td>Table:</td><td><select ng-model="table.choice" ng-options="t.name for t in tables"></select></td></tr>
							<tr ng-hide="editing"><td>Duration:</td><td>{{booking.booking_length|minToHour}}</td></tr>
							<tr ng-show="editing"><td>Duration:</td><td><select ng-model="table.booking_length" ng-options="option.label for option in lengthOptions"></select></td></tr>
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

<div class="bookingdetail-wrapper" ng-controller="AddBookingCtrl" ng-show="show">
	<form ng-submit="submit">
	<div class="bookingdetail">
		<div class="bookingdetail-basic">
			<div class="bookingdetail-cover">
				<div class="bookingdetail-profile-pic"><img ng-src="{{newBooking.picture}}"/></div>
				<div class="bookingdetail-current">
					<div>
						<div class="bookingdetail-name"><input placeholder="First Name" ng-model="newBooking.first_name" ng-required><input placeholder="Last Name" ng-model="newBooking.last_name" ng-required></div>
						<div class="bookingdetail-edit">
							<div class="bookingdetail-edit-details">
								<div>
									reserved a table for 
									<select ng-model="newBooking.no_of_participants">
										<option value="1" ng-selected="newBooking.no_of_participants==1">1</option>
										<option value="2" ng-selected="newBooking.no_of_participants==2">2</option>
										<option value="3" ng-selected="newBooking.no_of_participants==3">3</option>
										<option value="4" ng-selected="newBooking.no_of_participants==4">4</option>
										<option value="5" ng-selected="newBooking.no_of_participants==5">5</option>
										<option value="6" ng-selected="newBooking.no_of_participants==6">6</option>
										<option value="7" ng-selected="newBooking.no_of_participants==7">7</option>
										<option value="8" ng-selected="newBooking.no_of_participants==8">8</option>
										<option value="9" ng-selected="newBooking.no_of_participants==9">9</option>
										<option value="10" ng-selected="newBooking.no_of_participants==10">10</option>
										<option value="11" ng-selected="newBooking.no_of_participants==11">11</option>
										<option value="12" ng-selected="newBooking.no_of_participants==12">12</option>
										<option value="13" ng-selected="newBooking.no_of_participants==13">13</option>
										<option value="14" ng-selected="newBooking.no_of_participants==14">14</option>
										<option value="15" ng-selected="newBooking.no_of_participants==15">15</option>
										<option value="16" ng-selected="newBooking.no_of_participants==16">16</option>
										<option value="17" ng-selected="newBooking.no_of_participants==17">17</option>
										<option value="18" ng-selected="newBooking.no_of_participants==18">18</option>
										<option value="19" ng-selected="newBooking.no_of_participants==19">19</option>
										<option value="20" ng-selected="newBooking.no_of_participants==20">20</option>
									</select> at
								</div>
								<div class="dropdown">
									<a class="dropdown-toggle" id="dropdown2" role="button" data-toggle="dropdown" data-target="#">
										<div class="input-group"><span class="form-control">{{ newBooking.booking_ts | date:'yyyy-MM-dd HH:mm:ss' }}</span><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										</div>
									</a>
									<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
										<datetimepicker data-ng-model="pickerDate" data-on-set-time="onTimeSet" data-datetimepicker-config="{ dropdownSelector: '#dropdown2', minuteStep: 15, startView: 'hour' }"/>
									</ul>
								</div>
								
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<div>
				<div>
					<div class="bookingdetail-col1">
						<table class="bookingdetail-table">
							<tr><td>Email:</td><td><input placeholder="Email" ng-model="newBooking.email" ng-required></td></tr>
							<tr><td>Phone:</td><td><input placeholder="Phone number" ng-model="newBooking.phone" ng-required ng-minlength="8" ng-maxlength="8"></td></tr>
							<tr><td>Table:</td><td><select ng-model="table.choice" ng-options="t.name for t in tables"></select></td></tr>
							<tr><td>Duration:</td><td><select ng-model="table.booking_length" ng-options="option.label for option in lengthOptions"></select></td></tr>
						</table>
					</div>
					<div class="bookingdetail-col2">
						<table class="bookingdetail-table">
							<tr><td class="bookingdetail-request">Special Request:</td><td><textarea ng-model="newBooking.special_request"></textarea></td></tr>
						</table>
					</div>
					<div class="clear"></div>
					<div class="bookingdetail-buttonpanel">
						<div class="lightbutton" ng-click="save(false)" ng-disabled="updating">
							<a>&#8629;&nbsp;Save</a>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
	</div>
	</form>
</div>

<?php

include dirname(__FILE__) . '/common/footer.php';

?>