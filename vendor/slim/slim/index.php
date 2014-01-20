<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
ini_set("display_errors", "1");
error_reporting(-1);
require 'Slim/Slim.php';
require '../../../vendor/autoload.php';
\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function.
 */

DB::$host = "ikky.ciits52gpuzt.ap-southeast-1.rds.amazonaws.com";
DB::$user = "ikky";
DB::$password = "ikky1234";
DB::$dbName = "ikky";
DB::$encoding = 'utf8';
DB::$port = "3306";

$app->hook('slim.before.router', function () use ($app){
	/*
	$env = $app->environment();
	if(strstr($env['PATH_INFO'], '/api/') ===0)
		$env['PATH_INFO'] = substr($uri,3);
	$uri = $app->request()->getResourceUri();
	if(strstr($uri, '/api/') === 0)
		$app->router()->setResourceUri(substr($uri,3));
	*/
	return ;
});
$aws = Aws\Common\Aws::factory('awsSDKConfigs.php');
$sns = $aws->get('Sns');



// GET route
$app->get('/', function () {
    $template = <<<EOT
<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8"/>
            <title>Slim Framework for PHP 5</title>
            <style>
                html,body,div,span,object,iframe,
                h1,h2,h3,h4,h5,h6,p,blockquote,pre,
                abbr,address,cite,code,
                del,dfn,em,img,ins,kbd,q,samp,
                small,strong,sub,sup,var,
                b,i,
                dl,dt,dd,ol,ul,li,
                fieldset,form,label,legend,
                table,caption,tbody,tfoot,thead,tr,th,td,
                article,aside,canvas,details,figcaption,figure,
                footer,header,hgroup,menu,nav,section,summary,
                time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent;}
                body{line-height:1;}
                article,aside,details,figcaption,figure,
                footer,header,hgroup,menu,nav,section{display:block;}
                nav ul{list-style:none;}
                blockquote,q{quotes:none;}
                blockquote:before,blockquote:after,
                q:before,q:after{content:'';content:none;}
                a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent;}
                ins{background-color:#ff9;color:#000;text-decoration:none;}
                mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold;}
                del{text-decoration:line-through;}
                abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help;}
                table{border-collapse:collapse;border-spacing:0;}
                hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0;}
                input,select{vertical-align:middle;}
                html{ background: #EDEDED; height: 100%; }
                body{background:#FFF;margin:0 auto;min-height:100%;padding:0 30px;width:440px;color:#666;font:14px/23px Arial,Verdana,sans-serif;}
                h1,h2,h3,p,ul,ol,form,section{margin:0 0 20px 0;}
                h1{color:#333;font-size:20px;}
                h2,h3{color:#333;font-size:14px;}
                h3{margin:0;font-size:12px;font-weight:bold;}
                ul,ol{list-style-position:inside;color:#999;}
                ul{list-style-type:square;}
                code,kbd{background:#EEE;border:1px solid #DDD;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:0 4px;color:#666;font-size:12px;}
                pre{background:#EEE;border:1px solid #DDD;border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;padding:5px 10px;color:#666;font-size:12px;}
                pre code{background:transparent;border:none;padding:0;}
                a{color:#70a23e;}
                header{padding: 30px 0;text-align:center;}
            </style>
        </head>
        <body>
            <header>
                <a href="http://www.slimframework.com"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHIAAAA6CAYAAABs1g18AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABRhJREFUeNrsXY+VsjAMR98twAo6Ao4gI+gIOIKOgCPICDoCjCAjXFdgha+5C3dcv/QfFB5i8h5PD21Bfk3yS9L2VpGnlGW5kS9wJMTHNRxpmjYRy6SycgRvL18OeMQOTYQ8HvIoJKiiz43hgHkq1zvK/h6e/TyJQXeV/VyWBOSHA4C5RvtMAiCc4ZB9FPjgRI8+YuKcrySO515a1hoAY3nc4G2AH52BZsn+MjaAEwIJICKAIR889HljMCcyrR0QE4v/q/BVBQva7Q1tAczG18+x+PvIswHEAslLbfGrMZKiXEOMAMy6LwlisQCJLPFMfKdBtli5dIihRyH7A627Iaiq5sJ1ThP9xoIgSdWSNVIHYmrTQgOgRyRNqm/M5PnrFFopr3F6B41cd8whRUSufUBU5EL4U93AYRnIWimCIiSI1wAaAZpJ9bPnxx8eyI3Gt4QybwWa6T/BvbQECUMQFkhd3jSkPFgrxwcynuBaNT/u6eJIlbGOBWSNIUDFEIwPZFAtBfYrfeIOSRSXuUYCsprCXwUIZWYnmEhJFMIocMDWjn206c2EsGLCJd42aWSyBNMnHxLEq7niMrY2qyDbQUbqrrTbwUPtxN1ZZCitQV4ZSd6DyoxhmRD6OFjuRUS/KdLGRHYowJZaqYgjt9Lchmi3QYA/cXBsHK6VfWNR5jgA1DLhwfFe4HqfODBpINEECCLO47LT/+HSvSd/OCOgQ8qE0DbHQUBqpC4BkKMPYPkFY4iAJXhGAYr1qmaqQDbECCg5A2NMchzR567aA4xcRKclI405Bmt46vYD7/Gcjqfk6GP/kh1wovIDSHDfiAs/8bOCQ4cf4qMt7eH5Cucr3S0aWGFfjdLHD8EhCFvXQlSqRrY5UV2O9cfZtk77jUFMXeqzCEZqSK4ICkSin2tE12/3rbVcE41OBjBjBPSdJ1N5lfYQpIuhr8axnyIy5KvXmkYnw8VbcwtTNj7fDNCmT2kPQXA+bxpEXkB21HlnSQq0gD67jnfh5KavVJa/XQYEFSaagWwbgjNA+ywstLpEWTKgc5gwVpsyO1bTII+tA6B7BPS+0PiznuM9gPKsPVXbFdADMtwbJxSmkXWfRh6AZhyyzBjIHoDmnCGaMZAKjd5hyNJYCBGDOVcg28AXQ5atAVDO3c4dSALQnYblfa3M4kc/cyA7gMIUBQCTyl4kugIpy8yA7ACqK8Uwk30lIFGOEV3rPDAELwQkr/9YjkaCPDQhCcsrAYlF1v8W8jAEYeQDY7qn6tNGWudfq+YUEr6uq6FZzBpJMUfWFDatLHMCciw2mRC+k81qCCA1DzK4aUVfrJpxnloZWCPVnOgYy8L3GvKjE96HpweQoy7iwVQclVutLOEKJxA8gaRCjSzgNI2zhh3bQhzBCQQPIHGaHaUd96GJbZz3Smmjy16u6j3FuKyNxcBarxqWWfYFE0tVVO1Rl3t1Mb05V00MQCJ71YHpNaMcsjWAfkQvPPkaNC7LqTG7JAhGXTKYf+VDeXAX9IvURoAwtTFHvyYIxtnd5tPkywrPafcwbeSuGVwFau3b76NO7SHQrvqhfFE8kM0Wvpv8gVYiYBlxL+fW/34bgP6bIC7JR7YPDubcHCPzIp4+cum7U6NlhZgK7lua3KGLeFwE2m+HblDYWSHG2SAfINuwBBfxbJEIuWZbBH4fAExD7cvaGVyXyH0dhiAYc92z3ZDfUVv+jgb8HrHy7WVO/8BFcy9vuTz+nwADAGnOR39Yg/QkAAAAAElFTkSuQmCC" alt="Slim"/></a>
            </header>
            <h1>Welcome to Slim!</h1>
            <p>
                Congratulations! Your Slim application is running. If this is
                your first time using Slim, start with this <a href="http://www.slimframework.com/learn" target="_blank">"Hello World" Tutorial</a>.
            </p>
            <section>
                <h2>Get Started</h2>
                <ol>
                    <li>The application code is in <code>index.php</code></li>
                    <li>Read the <a href="http://docs.slimframework.com/" target="_blank">online documentation</a></li>
                    <li>Follow <a href="http://www.twitter.com/slimphp" target="_blank">@slimphp</a> on Twitter</li>
                </ol>
            </section>
            <section>
                <h2>Slim Framework Community</h2>

                <h3>Support Forum and Knowledge Base</h3>
                <p>
                    Visit the <a href="http://help.slimframework.com" target="_blank">Slim support forum and knowledge base</a>
                    to read announcements, chat with fellow Slim users, ask questions, help others, or show off your cool
                    Slim Framework apps.
                </p>

                <h3>Twitter</h3>
                <p>
                    Follow <a href="http://www.twitter.com/slimphp" target="_blank">@slimphp</a> on Twitter to receive the very latest news
                    and updates about the framework.
                </p>
            </section>
            <section style="padding-bottom: 20px">
                <h2>Slim Framework Extras</h2>
                <p>
                    Custom View classes for Smarty, Twig, Mustache, and other template
                    frameworks are available online in a separate repository.
                </p>
                <p><a href="https://github.com/codeguy/Slim-Extras" target="_blank">Browse the Extras Repository</a></p>
            </section>
        </body>
    </html>
EOT;
    echo $template;
});

$app->group('/api', function () use($app){

	$app->get('/phpinfo', function(){
		phpinfo();
	});

	$app->get('/restaurantInfo', function() use ($app){
		$id = $app->request()->params('id');
		$returnValue = array(
			"RESTAURANT_NAME"=>"Name of the restaurant",
			"RESTAURANT_ADDRESS" => "address of the restaurant",
			"RESTAURANT_PHONE" => "(852)1234567",
			"RESTAURANT_CUISINE" => "Italy with little india style",
			"RESTAURANT_PRICE" => "5 - 5000",
			"RESTAURANT_HOURS" => "4AM - 5AM",
			"RESTAURANT_PARKING" => "YES with tickets every 10 mins",
			"RESTAURANT_DESCRIPTION" => "Description of a restaurant which is Italy with little india style",
			"RESTAURANT_MENU" => "1 dish only",
			"RESTAURANT_REVIEW_OVERALL" => 4,
			"RESTAURANT_REVIEW_FOOD" => 4,
			"RESTAURANT_REVIEW_SERVICE" => 4,
			"RESTAURANT_REVIEW_AMBIANCE" => 4,
			"RESTAURANT_REVIEWS" => array("good","bad", "good", "bad")
		);


		echo json_encode($returnValue );
		//echo json_encode($returnValue, JSON_PRETTY_PRINT);
		//echo $output;
	});

	$app->post('/users', function() use ($app){
		$result = array();	

		//$userID = 0;
		//$merchantID = 0;
		//$timeslot = "19:00";
		$firstName = $app->request()->params('firstName');
		$lastName = $app->request()->params('lastName');
		$email = $app->request()->params('email');
		$phone = $app->request()->params('phone');
		$password = $app->request()->params('password');
		
		DB::insert('user', $values = array(
			'first_name' => $firstName, 
			'last_name' => $lastName,
			'email' => $email,
			'phone' => $phone,
			'password' => $password,
			'create_ts' => DB::sqleval('NOW()')
		));
		$values['userID'] = DB::insertId();
		$values['token'] = "1231231234";
		$result['result'] = true;
		$result['values'] = $values;
		echo json_encode($result);
	});

	$app->post('/users/:email', function($email) use ($app){
		$action = $app->request()->params('action');
		
		$firstName = $app->request()->params('firstName');
		$lastName = $app->request()->params('lastName');
		$phone = $app->request()->params('phone');
		$returnValue = array();
		$returnValue['result'] = false;
		if($action == "updateProfile"){
			DB::update('users', array(
				"firstname" => $firstName,
				"lastname" => $lastName,
				"phone" => $phone
			), "email=%s", $email);
			$returnValue['result'] = true;
		}
		//var_dump($rs);
		echo json_encode($returnValue);

	});

	$app->post('/users/session/:email', function($email) use ($app){
		$action = $app->request()->params('action');
		$password= $app->request()->params('password');
		$result = array();
		$result['result'] = false;
		if($action == "login"){
			$returnValue = DB::queryFirstRow("SELECT * FROM user WHERE email = %s AND password = %s" , $email, $password);
			if(!is_null($returnValue)){
				$result['result'] = true;
				$result['user']['first_name'] = $returnValue['first_name'];
				$result['user']['last_name'] = $returnValue['last_name'];
				$result['user']['email'] = $returnValue['email'];
				$result['user']['phone'] = $returnValue['phone'];
				$result['user']['user_id'] = $returnValue['user_id'];
				$result['user']['token'] = '1231231234';
			}
		}
		//var_dump($rs);
		echo json_encode($result);
	});


	$app->put('/reservations/:bookingID', function($bookingID) use ($app){
		$returnValue = array();
		$values = array();
		$returnValue['result'] = false;
		
		if($app->request()->params('status') <> null)
			$values['status'] = $app->request()->params('status');
		if($app->request()->params('special_request') <> null)
			$values['special_request'] = $app->request()->params('special_request');
		if($app->request()->params('numberOfParticipant') <> null)
			$values['no_of_participants'] = $app->request()->params('numberOfParticipant');
		
		if($app->request()->params('datetime') <> null){
			$datetime = $app->request()->params('datetime');
			$timeArr = strptime($datetime, '%Y-%m-%d %H:%M:%S');
			$ts = mktime(intval($timeArr['tm_hour']), intval($timeArr['tm_min']), intval($timeArr['tm_sec']), intval($timeArr['tm_mon']) + 1 , intval($timeArr['tm_mday']) , intval($timeArr['tm_year'] + 1900));
			$values['booking_ts'] = date('Y-m-d H:i:s', $ts);
		}
		if(sizeof($values) > 0){
			DB::update('booking', $values, 'booking_id=%d', $bookingID);
			$returnValue['result'] = true;
			$returnValue['values'] = $values;
			
			// Publish new message (Amazon SNS)
			global $sns;
			$message = array(
				'topic'=>'1001',
				'bookingId'=>$bookingID,
				'action'=>'update'
			);
			$sns->publish(array(
				'Message' => json_encode($message),
				'TopicArn' => 'arn:aws:sns:ap-southeast-1:442675153455:merchant-1001'
			));
		}
		echo json_encode($returnValue);
	});

	$app->get('/merchants/:merchantID', function($merchantID) use ($app){
		$action = $app->request()->params('action');
		$rs = DB::queryFirstRow("SELECT * FROM restaurants_hongkong_csv WHERE LICNO = %s" , $merchantID);
		//var_dump($rs);
		$returnValue = array(
			"RESTAURANT_ID"=>$rs['LICNO'],
			"RESTAURANT_NAME"=>$rs['SS'],
			"RESTAURANT_ADDRESS" => $rs['ADR'],
			"RESTAURANT_PHONE" => "(852)1234567",
			"RESTAURANT_CUISINE" => "Italian with little india style",
			"RESTAURANT_PRICE" => "100 - 5000",
			"RESTAURANT_HOURS" => "4AM - 5AM",
			"RESTAURANT_PARKING" => "YES with tickets every 15 mins",
			"RESTAURANT_DESCRIPTION" => "Description of a restaurant which is Italy with little india style",
			"RESTAURANT_MENU" => "1 dish only",
			"RESTAURANT_REVIEW_OVERALL" => 4,
			"RESTAURANT_REVIEW_FOOD" => 4,
			"RESTAURANT_REVIEW_SERVICE" => 4,
			"RESTAURANT_REVIEW_AMBIANCE" => 4,
			"RESTAURANT_REVIEWS" => array("good","bad", "good", "bad"),
			"RESTAURANT_BOOKING_SLOTS" => array('18:00','18:15', '18:30','18:45', '19:00','19:15', '19:30','19:45', '20:00','20:15', '20:30','20:45', '21:00','21:15', '21:30', '21:45')
		);
		echo json_encode($returnValue);
	});

	$app->post('/reservations', function() use ($app){
		$result = array();	

		//$userID = 0;
		//$merchantID = 0;
		//$timeslot = "19:00";
		$userID = $app->request()->params('userID');
		$merchantID = $app->request()->params('merchantID');
		$datetime = $app->request()->params('datetime');
		$numberOfParticipant = $app->request()->params('numberOfParticipant');
		$specialRequest = is_null($app->request()->params('specialRequest'))?'':$app->request()->params('specialRequest');
		$timeArr = strptime($datetime, '%Y-%m-%d %H:%M:%S');
		$ts = mktime(intval($timeArr['tm_hour']), intval($timeArr['tm_min']), intval($timeArr['tm_sec']), intval($timeArr['tm_mon']) + 1 , intval($timeArr['tm_mday']) , intval($timeArr['tm_year'] + 1900));
		
		DB::insert('booking', $values = array(
			'user_id' => $userID, 
			'merchant_id' => $merchantID,
			'booking_ts' => date('Y-m-d H:i:s' , $ts),
			'no_of_participants' => $numberOfParticipant,
			'special_request' => $specialRequest,
			'status' => 0,
			'create_ts' => DB::sqleval('NOW()')
		));
		$result['bookingID'] = DB::insertId();
		$result['result'] = true;
		$result['values'] = $values;

		// Publish new message (Amazon SNS)
		global $sns;
		$message = array(
			'topic'=>'1001',
			'bookingId'=>$result['bookingID'],
			'action'=>'new'
		);
		$sns->publish(array(
			'Message' => json_encode($message),
			'TopicArn' => 'arn:aws:sns:ap-southeast-1:442675153455:merchant-1001'
		));

		echo json_encode($result);
	});


	$app->get('/restaurant', function() use ($app){
		$keyword = $app->request()->params('k');
		$page = $app->request()->params('p');
		$lat= $app->request()->params('lat');
		$lng = $app->request()->params('lng');
		$distanceUnit = $app->request()->params('du');
		$distance = $app->request()->params('dt');
		if(is_null($page))
			$page = 0;
		$resultPerPage = 10;
		$hasLocation = (!is_null($lat) && !is_null($lng));
		if(!$hasLocation)
			$sql = "SELECT * FROM restaurants_hongkong_csv ";
		else{	
			if(is_null($distance))
				$distance = 0.3;
			$unit = ($distanceUnit =="km"?6371:3959);
			$sql = "SELECT *,  (" . $unit . "* acos( cos( radians(" . $lat . "))* cos( radians( lat_dec ))* cos( radians( lng_dec )- radians( " . $lng . "))+ sin( radians(" . $lat . "))* sin( radians( lat)))) AS distance FROM restaurants_hongkong_csv ";
		}
		if (!is_null($keyword) ){
			$sql = $sql . " WHERE ";
			if(!is_null($keyword))
				$sql = $sql . " (SS LIKE '%" . $keyword . "%' OR ADR LIKE '%" . $keyword . "%')";
		}
		if($hasLocation)
			$sql = $sql . ' HAVING distance < ' . $distance . ' ';


		$sql = $sql . ' ORDER BY LICNO LIMIT ' . $page * $resultPerPage .  ',' . $resultPerPage;
		$rs = DB::query($sql);
			
		/*
		if (is_null($keyword)){ 
			//echo $page * $resultPerPage;
			$rs= DB::query("SELECT * FROM restaurants_hongkong_csv ORDER BY LICNO LIMIT %d, %d",  $page * $resultPerPage , $resultPerPage);
		}else{
			$rs= DB::query("SELECT * FROM restaurants_hongkong_csv WHERE SS LIKE %s OR ADR LIKE %s ORDER BY LICNO LIMIT %d, %d", '%'.$keyword.'%', '%'.$keyword.'%', $page * $resultPerPage , $resultPerPage);
		}
		//echo "test";
		//var_dump($rs);
		*/
		$images = array(
			"http://giverny.org/hotels/corniche/piscine2.jpg",
			"http://giverny.org/hotels/corniche/terrasse-resto.jpg",
			"http://giverny.org/hotels/corniche/restaurant-room.jpg",
			"http://giverny.org/hotels/corniche/standard-bedroom.jpg",
			"http://giverny.org/hotels/corniche/superior-bedroom.jpg",
			"http://giverny.org/hotels/corniche/cuisine2.jpg",
			"http://giverny.org/hotels/corniche/cuisine3.jpg",
			"http://giverny.org/hotels/corniche/cuisine1.jpg",
			"http://giverny.org/tour/versailles.jpg",
			"http://giverny.org/tour/ravoux.jpg",
			"http://giverny.org/hotels/corniche/piscine2.jpg",
			"http://giverny.org/hotels/corniche/terrasse-resto.jpg",
			"http://giverny.org/hotels/corniche/restaurant-room.jpg",
			"http://giverny.org/hotels/corniche/standard-bedroom.jpg",
			"http://giverny.org/hotels/corniche/superior-bedroom.jpg",
			"http://giverny.org/hotels/corniche/cuisine2.jpg",
			"http://giverny.org/hotels/corniche/cuisine3.jpg",
			"http://giverny.org/hotels/corniche/cuisine1.jpg",
			"http://giverny.org/tour/versailles.jpg",
			"http://giverny.org/tour/ravoux.jpg"
		);

		foreach ($rs as $idx => $restaurant) {
			$rs[$idx]['IMAGE'] = $images[array_rand($images)];
		}
		echo json_encode($rs);
		//echo json_encode($returnValue, JSON_PRETTY_PRINT);
		//echo $output;
	});
	$app->post('/login', function() use ($app){

		$returnValue = DB::queryFirstRow("SELECT * FROM user WHERE email = %s" , $email);
		//var_dump($rs);



		$username = $app->request()->params('username');
		$pwd = $app->request()->params('pwd');
		if ($username == 'ikky@ikky.com' && $pwd=='123456') {
			$result['result'] = true;
			$result['user']['first_name'] = 'Ikky';
			$result['user']['last_name'] = 'Limited';
			$result['user']['email'] = 'ikky@ikky.com';
			$result['user']['phone'] = '12345678';
			$result['user']['uid'] = '1';
		} else if ($username == 'marvin@ikky.com' && $pwd == '123456') {
			$result['result'] = true;
			$result['user']['first_name'] = 'Marvin';
			$result['user']['last_name'] = 'Lam';
			$result['user']['email'] = 'marvin@ikky.com';
			$result['user']['phone'] = '12345678';
			$result['user']['uid'] = '2';
		} else if ($username == 'ryder@ikky.com' && $pwd == '123456') {
			$result['result'] = true;
			$result['user']['first_name'] = 'Ryder';
			$result['user']['last_name'] = 'Lee';
			$result['user']['email'] = 'marvin@ikky.com';
			$result['user']['phone'] = '12345678';
			$result['user']['uid'] = '3';
		} else {
			$result['result'] = false;
		}

		echo json_encode($result);
	});

	$app->get('/reservations', function() use ($app){
		$returnValue = array();
		if( ($userID = $app->request()->params('userID')) <> null){
			$returnValue = DB::query("SELECT booking.booking_id, booking.user_id, booking.booking_ts, booking.no_of_participants, booking.special_request, booking.status, restaurants_hongkong_csv.LICNO, restaurants_hongkong_csv.SS, restaurants_hongkong_csv.ADR FROM booking LEFT JOIN restaurants_hongkong_csv ON booking.merchant_id = restaurants_hongkong_csv.LICNO WHERE booking.user_id = %d AND booking.status IN (0, 1) ORDER BY booking.status DESC, booking.booking_ts ASC", $userID);
		}
		echo json_encode($returnValue);
	});

	$app->get('/mms/bookings/:merchantID', function($merchantID) use ($app){
		$returnValue = array();
		if ($merchantID != null) {
			$returnValue = DB::query("SELECT CONCAT(first_name, ' ', last_name) name, phone, b.booking_id, b.booking_ts, b.no_of_participants, b.special_request, b.status FROM booking b JOIN user u ON b.user_id = u.user_id WHERE merchant_id = %d", $merchantID);
		}
		echo json_encode($returnValue);
	});
	$app->get('/mms/bookings/:merchantID/:bookingID', function($merchantID, $bookingID) use ($app){
		$returnValue = array();
		if ($merchantID != null && $bookingID != null) {
			$returnValue = DB::query("SELECT CONCAT(first_name, ' ', last_name) name, phone, b.booking_id, b.booking_ts, b.no_of_participants, b.special_request, b.status FROM booking b JOIN user u ON b.user_id = u.user_id WHERE merchant_id = %d AND booking_id = %d", $merchantID, $bookingID);
		}
		echo json_encode($returnValue);
	});

	//var_dump($rs);
});


// POST route
$app->post('/post', function () {
    echo 'This is a POST route';
});

// PUT route
$app->put('/put', function () {
    echo 'This is a PUT route';
});

// DELETE route
$app->delete('/delete', function () {
    echo 'This is a DELETE route';
});


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
