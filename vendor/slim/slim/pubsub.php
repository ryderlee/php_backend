<script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
<script>
var conn = new ab.Session(
		        'ws://ikky-phpapp-env.elasticbeanstalk.com:8080' // The host (our Ratchet WebSocket server) to connect to
		      , function() {            // Once the connection has been established
		            conn.subscribe("1001", function(topic, data) {
			                // This is where you would add the new article to the DOM (beyond the scope of this tutorial)
			                console.log('Info | Time: ' + data.info + ' | ' + data.time);
			            });
		        }
		      , function() {            // When the connection is closed
		            console.warn('WebSocket connection closed');
		        }
		      , {                       // Additional parameters, we're ignoring the WAMP sub-protocol for older browsers
		            'skipSubprotocolCheck': true
		        }
		    );
</script>
