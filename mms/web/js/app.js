var mmsApp = angular.module('mmsApp', [
  'mmsControllers',
  'mmsServices',
  'mmsFilters',
  'mmsAnimations',
  'ui.bootstrap.datetimepicker'
])
.run(['$rootScope', '$location',
	function($rootScope, $location) {
	// Listen from WebSocket server for new / update records		
	var wsuri = "ws://ikky-phpapp-env.elasticbeanstalk.com:8081";
	/** To be replaced: Server Timestamp **/
	// ab.debug(true, true);
	var connectWS = function() {
		ab.connect(wsuri, function (session) {
			$rootScope.session = session;
			console.log("[Session Opened]");
			console.log(session);
			session.subscribe("1001",
				function (topic, event) {
					if ($rootScope.session) {
						console.log("got event: " + event);
						var json = JSON.parse(event);
						if (json.action == 'new') {
							$rootScope.$emit('newBooking', json);
						} else if (json.action == 'update') {
							$rootScope.$emit('updateBooking', json);
						}
					} else {
						console.log("got event but session is closing, event ignored");
					}
				});
			}, function (code, reason, detail) {
				if (code == 0) { // closed by explicit call to session.close()
					connectWS();
				} else if (code == 2) {
					console.log('connection lost and number of retries exceeded');
				}
				console.log("[Session Closed] Code: "+code+" | Reason: "+reason+" | Detail: "+detail);
			}
		);
	};
	connectWS();
	
	// Browser heartbeat
	var lastBeat = new Date().getTime();
    var heartbeatCheck = function() {
        var now = new Date().getTime();
        if (now - lastBeat > 10000) {
        	console.log('on wake');
        	$rootScope.session.close();
        	$rootScope.session = null;
            $rootScope.$emit('wake');
        } else {
        	console.log('heart beating');
        }
        lastBeat = now;
        setTimeout(heartbeatCheck, 500);
    };
    heartbeatCheck();
    
    // Monitor location
    $rootScope.$watch(function() {
    	return $location.path();
    }, function(newVal, oldVal) {
    	var paths = newVal.replace(/^\//, '').split('/');
    	if (paths[0].length == 0) {
    		$rootScope.$emit('showCalendar', $rootScope.viewDate);
    	} else if (paths.length >= 1) {
    		var date = new Date(paths[0].substring(0, 4), paths[0].substring(4, 6)-1, paths[0].substring(6), 0, 0, 0);
    		$rootScope.viewDate = date;
    		$rootScope.$emit('showDayView', date, false);
    		if (paths.length >= 2) {
    			if (paths[1] == 'new') {
    				console.log('new booking');
    				$rootScope.$emit('addBooking', date);
    			} else {
    				console.log('show detail');
    				$rootScope.$emit('showDetail', paths[1]);
    			}
    		}
    	}
    });
	
}])
.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(false).hashPrefix('!');
}]);