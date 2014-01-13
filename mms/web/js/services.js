var mmsServices = angular.module('mmsServices', ['ngResource']);

//http://ikky-phpapp-env.elasticbeanstalk.com/

mmsServices.factory('Booking', ['$resource',
	function($resource){
	return $resource('ajax/getBookings.php?bookingId=:bookingId', {}, {
		getBookings: {method:'GET', params:{}, isArray:true}
	});
}]);