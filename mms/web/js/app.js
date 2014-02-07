var mmsApp = angular.module('mmsApp', [
  'mmsControllers',
  'mmsServices',
  'mmsFilters',
  'mmsAnimations'
])
.run(function($rootScope) {
	// Listen from WebSocket server for new / update records		
		var wsuri = "ws://ikky-phpapp-env.elasticbeanstalk.com:8081";
		ab.connect(wsuri, function (session) {
			session.subscribe("1001",
				function (topic, event) {
					console.log("got event1: " + event);
					var json = JSON.parse(event);
					if (json.action == 'new') {
						$rootScope.$emit('newBooking', json);
					} else if (json.action == 'update') {
						$rootScope.$emit('updateBooking', json);
					}
			});
		}, function (code, reason) {
			console.log(reason);
		}
	);
});