var mmsControllers = angular.module('mmsControllers', []);

mmsControllers.controller('BookingListCtrl', ['$scope', 'Booking',
	function($scope, Booking) {
		$scope.bookings = [];
		// Use service to get booking records
		$scope.bookings = Booking.getBookings();
		// Pre-set the predicate (sorting) field
		$scope.predicate = 'booking_ts';
		
		// Monitor the record passed
		function timePassing() {
			angular.forEach($scope.bookings, function(value, key) {
				var arr = value.booking_ts.split(' ');
				var dArr = arr[0].split('-');
				var tArr = arr[1].split(':');
				var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
				var now = new Date();
				if (d.getTime() <= now.getTime()) {
					value.past = true;
				}
			});
			$scope.$digest();
		}
		setInterval(function() {
			timePassing();
		}, 1000);
		
		// Listen from WebSocket server for new / update records		
		var wsuri = "ws://ikky-phpapp-env.elasticbeanstalk.com:8081";
		ab.connect(wsuri, function (session) {
			session.subscribe("1001",
				function (topic, event) {
					console.log("got event1: " + event);
					var json = JSON.parse(event);
					if (json.action == 'new') {
						Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
							angular.forEach(newBookings, function(value, key) {
								value['flash'] = true;
							});
							console.log(newBookings);
							$scope.bookings = $scope.bookings.concat(newBookings);
		                });
					} else if (json.action == 'update') {
						Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
							angular.forEach($scope.bookings, function(value1, key1) {
								angular.forEach(newBookings, function(value2, key2) {
									if (value1.booking_id == value2.booking_id) {
										angular.forEach(value1, function(value, key) {
											value1[key] = value2[key];
										});
										value1['flash'] = true;
									}
								});
							});
		                });
					}
				});
			}, function (code, reason) {
				console.log(reason);
			}
		);
	}
])
.directive('bookingRow', function($timeout) {
	function link(scope, element, attrs) {
		// Monitor if booking has flash flag
		scope.$watch('booking.flash', function() {
			if (scope.booking.flash) {
				scope.booking.flash = false;
				jQuery(element).css('backgroundColor', '#80AED2');
				jQuery(element).delay(5000, 'wait').animate({'backgroundColor':'#ffffff'}, {duration:1000, queue:'wait'}).dequeue('wait');
			}
		});
	};
	
	return {
		// Restrict use as "A"ttribute
		restrict: 'A',
		link: link
	};
});
