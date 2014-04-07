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
}).filter('minToHour', function($filter) {
	return function (input) {
		return Math.floor(input/60)+':'+(Math.floor(input%60)<10?'0'+Math.floor(input%60):Math.floor(input%60));
	};
}).filter('groupBy', function($filter) {
	return function(list, groupBy) {

    var filtered = [];
    var prevItem = null;
    var groupChanged = false;
    // this is a new field which is added to each item where we append "_CHANGED"
    // to indicate a field change in the list
    var newField = groupBy + '_CHANGED';

    // loop through each item in the list
    angular.forEach(list, function(item) {

        groupChanged = false;

        // if not the first item
        if (prevItem !== null) {

            // check if the group by field changed
            if (prevItem[groupBy] !== item[groupBy]) {
                groupChanged = true;
            }

        // otherwise we have the first item in the list which is new
        } else {
            groupChanged = true;
        }

        // if the group changed, then add a new field to the item
        // to indicate this
        if (groupChanged) {
            item[newField] = true;
        } else {
            item[newField] = false;
        }

        filtered.push(item);
        prevItem = item;

    });

    return filtered;
    };
});