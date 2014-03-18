<?php

date_default_timezone_set('Asia/Hong_Kong');
include('temp.php');
include('restaurantTableManagement.php');

$mid = 2214036464;

$time = mktime(18, 0 , 0, 3, 12, 2014);

$a = array('tableSeatOptions'=>'2,4','tableFor2'=>5, 'tableFor4'=>10, 'tableBookingLength' => 120, 'tableBookingInterval'=>15, 'tableBookingStart' => '1800', 'tableBookingEnd' => 360, 'tableCoverList' => '1,2,3,4,5,6');
setMerchantSettings($mid, $a);

$rtm = RestaurantTableManagement::getInstance($mid, $time);

$rtm->resetCache();

$cache = $rtm->getCache(2);
//var_dump($cache);
if($result = $rtm->lock(4)){
	echo $result;
	echo "GOOD";
//	$rtm->commit(4);

}else{

	echo "FAIL";

}
$rtm->unlock(4);
