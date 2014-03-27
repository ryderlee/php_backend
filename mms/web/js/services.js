var mmsServices = angular.module('mmsServices', ['ngResource']);

mmsServices.factory('Booking', ['$resource',
	function($resource){
	return $resource('ajax/Booking.php?bookingId=:bookingId&date=:date&userId=:userId', {}, {
		getBookings: {method:'GET', params:{'action':'get'}, isArray:true},
		attendBooking: {method:'GET', params:{'action':'update', 'status':'2'}},
		cancelBooking: {method:'GET', params:{'action':'update', 'status':'-1'}},
		getOccupancyRate: {method:'GET', params:{'action':'getOccupancyRate'}, isArray:true},
		getHistory: {method:'GET', params:{'action':'getHistory'}, isArray:true},
		editBooking: {method:'GET', params:{'action':'edit', 'bookingTs':':bookingTs', 'noOfParticipants':':noOfParticipants', 'tableId':':tableId'}},
		getTables: {method:'GET', params:{'action':'getTables', 'bookingTs':':bookingTs', 'noOfParticipants':':noOfParticipants'}}
	});
}]);