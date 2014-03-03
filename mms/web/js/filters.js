angular.module('mmsFilters', []).filter('bookingStatus', function() {
  return function(input) {
    switch(input) {
    case '0':
    	return "Pending";
    	break;
    case '1':
    	return "Confirmed";
    	break;
    case '2':
    	return "Attended";
    	break;
    case '-1':
    	return "Cancelled";
    	break;
    default:
    	break;
    }
  };
}).filter('bookingDatetime', function($filter) {
	var angularDateFilter = $filter('date');
	return function (input) {
		// console.log(input);
		var arr = input.split(' ');
		var dArr = arr[0].split('-');
		var tArr = arr[1].split(':');
		var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
		// console.log(d);
		return angularDateFilter(d.getTime(), 'shortTime');
   };
}).filter('historyDatetime', function($filter) {
	var angularDateFilter = $filter('date');
	return function (input) {
		var arr = input.split(' ');
		var dArr = arr[0].split('-');
		var tArr = arr[1].split(':');
		var d = new Date(dArr[0], dArr[1]-1, dArr[2], tArr[0], tArr[1], tArr[2]);
		return angularDateFilter(d.getTime(), 'd MMM yyyy - h:mm a');
   };
}).filter('newline', function($filter) {
	return function (input) {
		return input.replace(/\n/g, '<br />');
	};
});