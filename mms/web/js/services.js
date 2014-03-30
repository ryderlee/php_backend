var mmsServices = angular.module('mmsServices', ['ngResource']);

mmsServices.factory('Booking', ['$resource',
	function($resource){
	return $resource('ajax/Booking.php?bookingId=:bookingId&date=:date&userId=:userId', {}, {
		getBookings: {method:'GET', params:{'action':'get'}, isArray:true},
		attendBooking: {method:'GET', params:{'action':'update', 'status':'2'}},
		cancelBooking: {method:'GET', params:{'action':'update', 'status':'-1'}},
		getOccupancyRate: {method:'GET', params:{'action':'getOccupancyRate'}, isArray:true},
		getHistory: {method:'GET', params:{'action':'getHistory'}, isArray:true},
		editBooking: {method:'GET', params:{'action':'edit', 'bookingTs':':bookingTs', 'noOfParticipants':':noOfParticipants', 'tableId':':tableId', 'bookingLength':':bookingLength', 'forced':':forced'}},
		getTables: {method:'GET', params:{'action':'getTables', 'bookingTs':':bookingTs', 'noOfParticipants':':noOfParticipants', 'bookingLength':':bookingLength'}},
		addBooking: {method:'GET', params:{'action':'addBooking', 'firstName':':firstName', 'lastName':':lastName', 'phone':':phone', 'bookingTs':':bookingTs', 'noOfParticipants':':noOfParticipants', 'specialRequest':':specialRequest', 'tableId':':tableId', 'bookingLength':':bookingLength', 'forced':':forced', 'email':':email'}}
	});
}]);