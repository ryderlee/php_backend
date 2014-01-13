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
});

// GET route
$app->get('/', function () use ($app){
	$uri = $app->request()->getResourceUri();
	phpinfo();
});
$app->group('/api', function () use($app){

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

	$app->get('/reservations', function() use ($app){
		$returnValue = array();
		if( ($userID = $app->request()->params('userID')) <> null){
			$returnValue = DB::query("SELECT booking.booking_id, booking.user_id, booking.booking_ts, booking.no_of_participants, booking.special_request, booking.status, restaurants_hongkong_csv.LICNO, restaurants_hongkong_csv.SS, restaurants_hongkong_csv.ADR FROM booking LEFT JOIN restaurants_hongkong_csv ON booking.merchant_id = restaurants_hongkong_csv.LICNO WHERE booking.user_id = %d AND booking.status > -1 ORDER BY booking.status DESC, booking.booking_ts ASC", $userID);
		}
		echo json_encode($returnValue);
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
			
		}
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
		$specialRequest = $app->request()->params('specialRequest');
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
		echo json_encode($result);
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

	$app->get('/restaurant', function() use ($app){
		$keyword = $app->request()->params('k');
		$page = $app->request()->params('p');
		if(is_null($page))
			$page = 0;
		$resultPerPage = 10;
		if (is_null($keyword)){ 
			//echo $page * $resultPerPage;
			$rs= DB::query("SELECT * FROM restaurants_hongkong_csv ORDER BY LICNO LIMIT %d, %d",  $page * $resultPerPage , $resultPerPage);
		}else{
			$rs= DB::query("SELECT * FROM restaurants_hongkong_csv WHERE SS LIKE %s OR ADR LIKE %s ORDER BY LICNO LIMIT %d, %d", '%'.$keyword.'%', '%'.$keyword.'%', $page * $resultPerPage , $resultPerPage);
		}
		//echo "test";
		//var_dump($rs);

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

$app->get('/phpinfo', function(){
	phpinfo();
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
