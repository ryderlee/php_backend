var mmsControllers = angular.module('mmsControllers', []);

mmsControllers.controller('BookingListCtrl', ['$scope', 'Booking', '$rootScope',
	function($scope, Booking, $rootScope) {
		$scope.bookings = [];
		// Use service to get booking records
		$scope.bookings = Booking.getBookings();
		$scope.bookings.$promise.then(function(newBookings) {
			checkTime(newBookings);
		});
		// Pre-set the predicate (sorting) field
		$scope.predicate = 'booking_ts';
		
		// Monitor the record passed
		function timePassing() {
			checkTime($scope.bookings);
			$scope.$digest();
		}
		setInterval(function() {
			timePassing();
		}, 10000);
		
		function checkTime(bookings) {
			angular.forEach(bookings, function(value, key) {
				var arr = value.booking_ts.split(' ');
				var dArr = arr[0].split('-');
				var tArr = arr[1].split(':');
				var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
				var now = new Date();
				if (d.getTime() <= now.getTime()) {
					value.past = true;
				}
			});
		}
		
		// Listen from WebSocket server for new / update records
		$rootScope.$on('newBooking', function() {
			Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
				angular.forEach(newBookings, function(value, key) {
					value.flash = true;
				});
				console.log(newBookings);
				$scope.bookings = $scope.bookings.concat(newBookings);
            });
		});
		$rootScope.$on('updateBooking', function() {
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
		});
		
		// Click on attended button
		$scope.attended = function(booking) {
			booking.loading = true;
			Booking.updateBooking({bookingId:booking.booking_id});
		};
	}
])
.controller('LoginCtrl', ['$scope',
	function($scope) {
		$scope.submit = function() {
			if ($scope.loginForm.$valid) {
				jQuery('form').submit();
			} else {
				$scope.loginForm.merchantId.$dirty = true;
				$scope.loginForm.password.$dirty = true;
			}
		};
	}
])
.directive('bookingRow', function($timeout) {
	function link(scope, element, attrs) {
		// Monitor if booking has flash flag
		scope.$watch('booking.flash', function() {
			if (scope.booking.flash) {
				scope.booking.flash = false;
				jQuery(element).css('backgroundColor', '#80AED2');
				jQuery(element).delay(5000, 'wait').animate({'backgroundColor':'none'}, {duration:1000, queue:'wait', complete:function() {
					jQuery(element).css('backgroundColor', '');
				}}).dequeue('wait');
			}
		});
		
		$(element).tooltip({
			position: {
				my: "left",
				at: "right+10 top",
				using: function( position, feedback ) {
					$( this ).css( position );
					$( "<div>" ).appendTo( this );
				}
			}
		});
	};
	
	return {
		// Restrict to use as "A"ttribute
		restrict: 'A',
		link: link
	};
	
})
.controller('CalendarCtrl', ['$scope', 'Booking', '$rootScope', 
	function($scope, Booking, $rootScope) {
		var offScreenRow = 20;
		var rowHeight = 130;
		var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
		var today = new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate(), 0, 0, 0);
		var rendered = false;
		var lastTouchY;
		var intermediateTime;
		
		$scope.loadPercentages = new Array();
		
		$scope.current = {month:monthNames[today.getMonth()], year:today.getFullYear(), date:today};
		$scope.$on('drawEnd', function() {
			if (!rendered) {
				rendered = true;
				$scope.scrollToTarget();
			}
		});
		$scope.$on('scroll', function() {
			var before = jQuery('.calendar').scrollTop() < rowHeight*offScreenRow;
			if (Math.abs(jQuery('.calendar').scrollTop() - rowHeight*offScreenRow) >= rowHeight) {
				for (i=0; i<parseInt(Math.abs(jQuery('.calendar').scrollTop() - rowHeight*offScreenRow)/rowHeight); i++) {
					var target = new Date($scope.caldates[before?0:$scope.caldates.length-1].date.getTime());
					target.setDate(target.getDate()+(before?-1:1));
					$scope.genWeek(null, target, before, true);
				}
				jQuery('.calendar').scrollTop(jQuery('.calendar').scrollTop()+(before?1:-1)*rowHeight);
			}
			$scope.updateMonth();
			$scope.$digest();
		});
		$scope.$on('mousewheel', function(event, wheelEvent) {
			var scrollY = Math.abs(wheelEvent.deltaY)>100?(wheelEvent.deltaY<0?-100:100):wheelEvent.deltaY;
			jQuery('.calendar').scrollTop(jQuery('.calendar').scrollTop()-scrollY);
			var before = jQuery('.calendar').scrollTop() < rowHeight*offScreenRow;
			if (Math.abs(jQuery('.calendar').scrollTop() - rowHeight*offScreenRow) >= rowHeight) {
				for (i=0; i<parseInt(Math.abs(jQuery('.calendar').scrollTop() - rowHeight*offScreenRow)/rowHeight); i++) {
					var target = new Date($scope.caldates[before?0:$scope.caldates.length-1].date.getTime());
					target.setDate(target.getDate()+(before?-1:1));
					$scope.genWeek(null, target, before, true);
				}
				jQuery('.calendar').scrollTop(jQuery('.calendar').scrollTop()+(before?1:-1)*rowHeight);
			}
			$scope.updateMonth();
			$scope.$digest();
		});
		$rootScope.$on('newBooking', function() {
			$scope.refreshLoads();
		});
		
		$scope.updateMonth = function() {
			var before = jQuery('.calendar').scrollTop() < rowHeight*offScreenRow;
			var current = $scope.caldates[(offScreenRow+(before?0:1))*7-1].date;
			$scope.current = {month:monthNames[current.getMonth()], year:current.getFullYear(), date:current};
		};
		
		$scope.scrollToTarget = function() {
			jQuery('.calendar').scrollTop(rowHeight*offScreenRow);
		};
		$scope.goToToday = function() {
			$scope.genForDate(today);
			$scope.scrollToTarget();
			$scope.updateMonth();
		};
		$scope.nextMonth = function() {
			var target = $scope.current.date;
			$scope.genForDate(new Date(target.getFullYear(), target.getMonth()+1, 1, 0, 0, 0));
			$scope.scrollToTarget();
			$scope.updateMonth();
		};
		$scope.prevMonth = function() {
			var target = $scope.current.date;
			$scope.genForDate(new Date(target.getFullYear(), target.getMonth()-1, 1, 0, 0, 0));
			$scope.scrollToTarget();
			$scope.updateMonth();
		};
		
		$scope.genWeek = function(target, start, before, clear) {
			for (j=0; j<7; j++) {
				var loadPercentage = $scope.loadPercentages[start.getTime()];
				var caldate = {
					date:new Date(start.getTime()),
					displayDate:start.getDate()==1?start.getDate()+' '+monthNames[start.getMonth()]:start.getDate(),
					bgColor:$scope.getBgColor(loadPercentage)
				};
				if (start.getTime()==today.getTime()) {
					caldate.className = 'today';
				}
				if (target && start.getTime()==target.getTime()) {
					caldate.id = 'target';
				}
				if (before) {
					$scope.caldates.unshift(caldate);
					if (clear) {
						$scope.caldates.pop();
					}
					start.setDate(start.getDate()-1);
				} else {
					$scope.caldates.push(caldate);
					if (clear) {
						$scope.caldates.shift();
					}
					start.setDate(start.getDate()+1);
				}
			}
		};
		$scope.genForDate = function(date) {
			jQuery('.calendar').scrollTop(0);
			$scope.caldates = new Array();
			var start = new Date(date.getTime());
			start.setDate(start.getDate()-(7*offScreenRow+start.getDay()));
			for (row=0; row<offScreenRow*2+7; row++) {
				$scope.genWeek(date, start);
			}
		};
		
		$scope.dateClick = function(date) {
			console.log(date);
		};
		
		$scope.getBgColor = function(loadPercentage) {
			var bgColor = "rgba(255, 0, 0, "+loadPercentage+")";
			if (loadPercentage <= 1/3) {
				bgColor = "rgba(0, 255, 0, "+loadPercentage+")";
			} else if (loadPercentage <= 2/3) {
				bgColor = "rgba(255, 255, 0, "+loadPercentage+")";
			}
			return {"background-color":bgColor};
		};
		$scope.refreshLoads = function() {
			Booking.getLoads().$promise.then(function(loads) {
				angular.forEach(loads, function(value, key) {
					var dArr = value.d.split('-');
					var d = new Date(dArr[0], dArr[1]-1, dArr[2], 0, 0, 0);
					$scope.loadPercentages[d.getTime()] = value.cnt/30;
				});
				angular.forEach($scope.caldates, function(value, key) {
					var loadPercentage = $scope.loadPercentages[value.date.getTime()];
					value.bgColor = $scope.getBgColor(loadPercentage);
				});
			});
		};
		
		$scope.genForDate(today);
		$scope.refreshLoads();
		
	}
])
.directive('calendarView', function() {
	return function(scope, element, attrs) {
		jQuery(element).kinetic({
			filterTarget: function(target, e){
				if (!/down|start/.test(e.type)){
					return !(/div/i.test(target.tagName));
				}
			}
		});
		jQuery(element).on('scroll', function() {
			scope.$emit('scroll');
		});
		jQuery(element).on('mousewheel', function(wheelEvent) {
			scope.$emit('mousewheel', wheelEvent);
		});
	};
})
.directive('calendarCell', function($timeout) {
	return function(scope, element, attrs) {
		if (scope.$last) {
			$timeout(function() {
				scope.$emit('drawEnd');
			});
		}
  	};
});

