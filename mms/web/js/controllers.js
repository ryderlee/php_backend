var mmsControllers = angular.module('mmsControllers', []);

mmsControllers.controller('BookingListCtrl', ['$scope', 'Booking',
	function($scope, Booking) {
		$scope.bookings = [];
		$scope.bookings = Booking.getBookings();
		
		$scope.predicate = 'booking_ts';
		
		var wsuri = "ws://ikky-phpapp-env.elasticbeanstalk.com:8081";
		ab.connect(wsuri, function (session) {
			session.subscribe("1001",
				function (topic, event) {
					console.log("got event1: " + event);
					var json = JSON.parse(event);
					if (json.action == 'new') {
						Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
							$scope.bookings = $scope.bookings.concat(newBookings);
							console.log($scope.bookings);
		                });
					} else if (json.action == 'update') {
						Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
							angular.forEach($scope.bookings, function(value1, key1) {
								angular.forEach(newBookings, function(value2, key2) {
									if (value1.booking_id == value2.booking_id) {
										angular.forEach(value1, function(value, key) {
											value1[key] = value2[key];
										});
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
]);
