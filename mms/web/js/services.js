var mmsServices = angular.module('mmsServices', ['ngResource']);

mmsServices.factory('Booking', ['$resource',
	function($resource){
	return $resource('ajax/Booking.php?bookingId=:bookingId&date=:date', {}, {
		getBookings: {method:'GET', params:{'action':'get'}, isArray:true},
		updateBooking: {method:'GET', params:{'action':'update'}},
		getLoads: {method:'GET', params:{'action':'loads'}, isArray:true}
	});
}]);