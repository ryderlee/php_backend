<?php

date_default_timezone_set('Asia/Hong_Kong');

date_default_timezone_set('UTC');

require 'Slim/Slim.php';
require '../../predis/predis/autoload.php';
require '../../../vendor/autoload.php';
require_once 'libs/MerchantTemplateService.php';
require_once 'libs/BookingService.php';
require_once 'RestaurantTableBookingModule.php';
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
$sns = Aws\Sns\SnsClient::factory(array(
	'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
    'secret' => $_ENV['AWS_SECRET_KEY'],
    'region' => 'ap-southeast-1'
));

$ses = Aws\Ses\SesClient::factory(array(
	'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
    'secret' => $_ENV['AWS_SECRET_KEY'],
    'region' => 'us-east-1'
));

$redis = new Predis\Client(array(
	//'host' => 'elasticcache.eeqrho.0001.apse1.cache.amazonaws.com',
	 'host' => '127.0.0.1',
	'database' => 0,
	'port'	=> 6379
));





$mid = 2214036464;

$time = mktime(18, 0 , 0, 3, 12, 2014);

$restaurantBookingService = new RestaurantBookingService();
$bookingId = 352;
$merchantId = 2214036464;
$userId = 19;
$isGuest = 1;
$sessionId = 'curl';
$firstName = 'Marvin';
$lastName = 'Lam';
$phone = '1234';
$datetime = '2014-03-13 22:00:00';
$noOfParticipants = 5;
$specialRequest = "test Special";
$status = 0;
$attendance = 0;

$tables = array();
$tables[] = new RestaurantTable($merchantId, 8, "123", 4, 3, 5);
//$tables[] = new RestaurantTable($merchantId, 7, "123", 4, 3, 5);
$tables[] = new RestaurantTable($merchantId, 6, "123", 4, 3, 5);

$bookingLength = 90;

$restaurantBookingService->editBooking($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $tables, $bookingLength);

//$restaurantBookingService->makeBookingByMerchant($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $tables, $bookingLength); 
