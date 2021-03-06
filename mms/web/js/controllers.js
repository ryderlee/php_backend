var mmsControllers = angular.module('mmsControllers', []);
var monthNames = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

Date.prototype.yyyymmdd = function() {
	var yyyy = this.getFullYear().toString();
	var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
	var dd  = this.getDate().toString();
	return yyyy + (mm[1]?mm:"0"+mm[0]) + (dd[1]?dd:"0"+dd[0]); // padding
};

mmsControllers.controller('BookingListCtrl', ['$scope', 'Booking', '$rootScope', '$sce', '$location',
	function($scope, Booking, $rootScope, $sce, $location) {
		// Pre-set the predicate (sorting) field
		$scope.predicate = 'booking_ts';		
		$scope.bookings = new Array();
		
		$rootScope.$on('showDayView', function(event, date, empty) {
			if (!$scope.current || $scope.current.date.getTime() != date.getTime()) {
				$scope.current = {year:date.getFullYear(), month:monthNames[date.getMonth()], day:date.getDate(), date:date};
				$scope.loading = true;
				$scope.bookings = new Array();
				if (!empty) {
					$scope.bookings = Booking.getBookings({date:date.yyyymmdd()});
					$scope.bookings.$promise.then(function(newBookings) {
						/** To be replaced: Server Timestamp **/
						$rootScope.lastResponseTs = Math.floor(new Date().getTime()/1000);
						$scope.loading = false;
						$scope.processBooking(newBookings);
						
						if ($scope.pendingDetailBookingId) {
							angular.forEach(newBookings, function(booking, key) {
								if (booking.booking_id == $scope.pendingDetailBookingId) {
									$rootScope.$emit('displayDetail', booking);
								}
							});
							$scope.pendingDetailBookingId = null;
						}
					});
				} else {
					$scope.loading = false;
				}
			}
			$scope.show = true;
		});
		
		// Monitor the record passed
		$scope.timePassing = function() {
			$scope.processBooking($scope.bookings);
			$scope.$digest();
		};
		setInterval(function() {
			$scope.timePassing();
		}, 10000);
		$scope.processBooking = function(bookings) {
			angular.forEach(bookings, function(value, key) {
				var arr = value.booking_ts.split(' ');
				var dArr = arr[0].split('-');
				var tArr = arr[1].split(':');
				var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
				var now = new Date();
				if (d.getTime() <= now.getTime()) {
					value.past = true;
				}
				if (value.social_network_user_id) {
					value.picture = "http://graph.facebook.com/"+value.social_network_user_id+"/picture?type=large&width=150&height=150";
					value.pictureSmall = "http://graph.facebook.com/"+value.social_network_user_id+"/picture?type=square&width=32&height=32";
				} else {
					value.picture = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
					value.pictureSmall = "http://static.ak.fbcdn.net/rsrc.php/v2/yo/r/UlIqmHJn-SK.gif";
				}
				if (value.special_request.length > 0) {
					value.special_request = $sce.trustAsHtml(value.special_request.replace(/\n/g, '<br>'));
				}
			});
		};
		
		$scope.showDetail = function(booking) {
			$location.path('/'+$scope.current.date.yyyymmdd()+'/'+booking.booking_id);
		};
		
		$rootScope.$on("showDetail", function(event, booking_id) {
			if ($scope.loading) {
				$scope.pendingDetailBookingId = booking_id;
			} else {
				angular.forEach($scope.bookings, function(booking, key) {
					if (booking.booking_id == booking_id) {
						$rootScope.$emit('displayDetail', booking);
					}
				});
			}
		});
		
		$rootScope.$on('newBooking', function(event, json) {
			var arr = json.bookingDate.split(' ');
			var dArr = arr[0].split('-');
			var d = new Date(dArr[0], dArr[1]-1, dArr[2], 0, 0, 0);
			if ($scope.current && d.getTime() == $scope.current.date.getTime()) {
				Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
					$scope.processBooking(newBookings);
					angular.forEach(newBookings, function(value, key) {
						value.flash = true;
					});
					console.log(newBookings);
					$scope.bookings = $scope.bookings.concat(newBookings);
	            });
           }
		});
		$rootScope.$on('updateBooking', function(event, json) {
			var arr = json.bookingDate.split(' ');
			var dArr = arr[0].split('-');
			var d = new Date(dArr[0], dArr[1]-1, dArr[2], 0, 0, 0);
			if ($scope.current && d.getTime() == $scope.current.date.getTime()) {
				Booking.getBookings({bookingId:json.bookingId}).$promise.then(function(newBookings) {
					/** To be replaced: Server Timestamp **/
					$rootScope.lastResponseTs = Math.floor(new Date().getTime()/1000);
					$scope.processBooking(newBookings);
					angular.forEach($scope.bookings, function(oBooking, key) {
						angular.forEach(newBookings, function(nBooking, key2) {
							$rootScope.$emit("updateBookingDetail", nBooking);
							if (oBooking.booking_id == nBooking.booking_id) {
								nBooking.flash = true;
								$scope.bookings[key] = nBooking;
							}
						});
					});
				});
			}
		});
		$rootScope.$on('wake', function() {
			if ($scope.show) {
				Booking.getBookings({date:$scope.current.date.yyyymmdd(), lastResponseTs:$rootScope.lastResponseTs}).$promise.then(function(bookings) {
					/** To be replaced: Server Timestamp **/
					$rootScope.lastResponseTs = Math.floor(new Date().getTime()/1000);
					$scope.processBooking(bookings);
					angular.forEach($scope.bookings, function(oBooking, key) {
						angular.forEach(bookings, function(nBooking, key2) {
							if (oBooking.booking_id == nBooking.booking_id) {
								nBooking.flash = true;
								$scope.bookings[key] = nBooking;
							}
						});
					});
				});
			}
		});
		
		$scope.back = function() {
			$location.path('/');
		};
		
		$rootScope.$on('showCalendar', function() {
			$scope.show = false;
		});
	}
])
.controller('LoginCtrl', ['$scope',
	function($scope) {
		$scope.merchantId = '2214036464';
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
	};
	
	return {
		// Restrict to use as "A"ttribute
		restrict: 'A',
		link: link
	};
	
})
.controller('CalendarCtrl', ['$scope', 'Booking', '$rootScope', '$location', 
	function($scope, Booking, $rootScope, $location) {
		var offScreenRow = isMobile?5:20;
		var rowHeight = 130;
		var today = new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate(), 0, 0, 0);
		var rendered = false;
		var lastTouchY;
		var intermediateTime;

		$scope.show = true;
		$rootScope.occupancyRates = new Array();
		
		$scope.current = {month:monthNames[today.getMonth()], year:today.getFullYear(), date:today};
		$rootScope.$on('showCalendar', function(event, date) {
			if (date) {
				$scope.genForDate(date);
				$scope.updateMonth();
				$scope.scrollToTarget();
			}
			$scope.show = true;
		});
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
			if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mac/i.test(navigator.platform) && wheelEvent.deltaFactor > 0) {
				console.log(wheelEvent);
				// scrollY *= wheelEvent.deltaFactor;
			}
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
			$scope.refreshOccupancyRate();
		});
		$rootScope.$on('wake', function() {
			$scope.refreshOccupancyRate();
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
				var occupancyRate = $rootScope.occupancyRates[start.getTime()];
				var caldate = {
					date:new Date(start.getTime()),
					displayDate:start.getDate()==1?start.getDate()+' '+monthNames[start.getMonth()]:start.getDate(),
					bgColor:$scope.getBgColor(occupancyRate)
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
		
		$rootScope.$on("showDayView", function() {
			$scope.show = false;
		});
		
		$scope.dateClick = function(caldate) {
			$location.path('/'+caldate.date.yyyymmdd());
		};
		
		$scope.getBgColor = function(occupancyRate) {
			var bgColor = "rgba(255, 0, 0, "+occupancyRate+")";
			if (occupancyRate <= 1/3) {
				bgColor = "rgba(0, 255, 0, "+occupancyRate+")";
			} else if (occupancyRate <= 2/3) {
				bgColor = "rgba(255, 255, 0, "+occupancyRate+")";
			}
			return {"background-color":bgColor};
		};
		$scope.refreshOccupancyRate = function() {
			Booking.getOccupancyRate().$promise.then(function(occupancy) {
				/** To be replaced: Server Timestamp **/
				$rootScope.lastResponseTs = Math.floor(new Date().getTime()/1000);
				angular.forEach(occupancy, function(value, key) {
					var dArr = value.d.split('-');
					var d = new Date(dArr[0], dArr[1]-1, dArr[2], 0, 0, 0);
					$rootScope.occupancyRates[d.getTime()] = value.cnt/30;
				});
				angular.forEach($scope.caldates, function(value, key) {
					var occupancyRate = $rootScope.occupancyRates[value.date.getTime()];
					value.bgColor = $scope.getBgColor(occupancyRate);
				});
			});
		};
		
		$scope.genForDate(today);
		$scope.refreshOccupancyRate();
		
	}
])
.directive('calendarView', function() {
	return function(scope, element, attrs) {
		if (isMobile) {
			jQuery(element).kinetic({
				filterTarget: function(target, e){
					if (e.type == 'touchstart') {
						jQuery(target).data('cancel', false);
						setTimeout(function() {
							jQuery(target).data('cancel', true);
						}, 150);
					} else if (e.type == 'touchend') {
						if (!jQuery(target).data('cancel')) {
							jQuery(target).click();
						}
					}
					return true;
				}
		});
		}
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
})
.controller('NotificationCtrl', ['$scope', '$rootScope',
	function($scope, $rootScope) {
		$scope.notification = {show:false, message:''};
		$rootScope.$on('newBooking', function(event, json) {
			var arr = json.bookingDate.split(' ');
			var dArr = arr[0].split('-');
			var tArr = arr[1].split(':');
			var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
			$scope.notification.message = 'A new booking on ' + d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
			jQuery('.notification').slideUp(0).slideDown().delay(3000).slideUp();
		});
	}
])
.controller('BookingDetailCtrl', ['$scope', '$rootScope', 'Booking',
	function($scope, $rootScope, Booking) {
		$scope.predicate = 'booking_ts';
		$scope.reverse = true;
		
		$rootScope.$on('showDayView', function(event, date, empty) {
			$scope.show = true;
			$scope.booking = null;
		});
		$rootScope.$on('showCalendar', function() {
			$scope.show = false;
			$scope.booking = null;
		});
		$rootScope.$on('displayDetail', function(event, booking) {
			if (!$scope.booking || $scope.booking.user_id != booking.user_id) {
				$scope.histories = null;
				$scope.loading = true;
				$scope.histories = Booking.getHistory({userId:booking.user_id});
				$scope.histories.$promise.then(function(histories) {
					$scope.loading = false;
					var indexToRemove = null;
					angular.forEach(histories, function(value, key) {
						if (value.booking_ts == booking.booking_ts) {
							indexToRemove = key;
						}
					});
					histories.splice(indexToRemove, 1);
				});
			}
			$scope.booking = booking;
		});
		$scope.show = false;
		
		$scope.attend = function(booking) {
			Booking.attendBooking({bookingId:booking.booking_id});
		};
		
		$scope.cancel = function(booking) {
			Booking.cancelBooking({bookingId:booking.booking_id});
		};
		
		$rootScope.$on('updateBookingDetail', function(event, booking) {
			if (booking.booking_id == $scope.booking.booking_id) {
				$scope.booking = booking;
			}
		});
	}
]);

