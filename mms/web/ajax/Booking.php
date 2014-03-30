<?php

require_once dirname(__FILE__) . '/../common/global.php';

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'get') {
		if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
			$resourceUri = '/mms/bookings/' . $_GET['bookingId'];
		} else if (isset($_GET['date']) && $_GET['date'] > 0) {
			$resourceUri = '/mms/bookings/' . $merchantService->getMerchantId() . '/' . $_GET['date'];
			if (isset($_GET['lastResponseTs'])) {
				$resourceUri .= '/' . $_GET['lastResponseTs'];
			}
		}
		echo HttpService::get($resourceUri);
	} else if ($_GET['action'] == 'update') {
		if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
			$resourceUri = '/reservations/' . $_GET['bookingId'];
			$paraMap = array('status'=>$_GET['status']);
			echo HttpService::put($resourceUri, $paraMap);
		}
	} else if ($_GET['action'] == 'getOccupancyRate') {
		$resourceUri = '/mms/occupancy/' . $merchantService->getMerchantId();
		echo HttpService::get($resourceUri);
	} else if ($_GET['action'] == 'getHistory') {
		if (isset($_GET['userId'])) {
			$resourceUri = '/mms/history/' . $merchantService->getMerchantId() . '/' . $_GET['userId'];
			echo HttpService::get($resourceUri);
		}
	} else if ($_GET['action'] == 'edit') {
		if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
			$resourceUri = '/reservations/' . $_GET['bookingId'];
			$paraMap = array('booking_ts'=>$_GET['bookingTs'], 'no_of_participants'=>$_GET['noOfParticipants'], 'table_id'=>$_GET['tableId'], 'booking_length'=>$_GET['bookingLength'], 'forced'=>$_GET['forced']=='true'?true:false);
			echo HttpService::put($resourceUri, $paraMap);
		}
	} else if ($_GET['action'] == 'getTables') {
		$resourceUri = '/restaurant/'.$merchantService->getMerchantId().'/tables';
		$paraMap = array('datetime'=>$_GET['bookingTs'], 'no_of_participants'=>$_GET['noOfParticipants'], 'booking_length'=>$_GET['bookingLength']);
		echo HttpService::get($resourceUri, $paraMap);
	}else if ($_GET['action'] == 'addBooking') {
		if (isset($_GET['email'])) {
			$resourceUri = '/reservation/'.$merchantService->getMerchantId().'/'.$_GET['email'];
			$paraMap = array('first_name'=>$_GET['firstName'], 'last_name'=>$_GET['lastName'], 'phone'=>$_GET['phone'], 'booking_ts'=>$_GET['bookingTs'], 'no_of_participants'=>$_GET['noOfParticipants'], 'special_request'=>$_GET['specialRequest'], 'table_id'=>$_GET['tableId'], 'booking_length'=>$_GET['bookingLength'], 'forced'=>$_GET['forced']=='true'?true:false);
			echo HttpService::put($resourceUri, $paraMap);
		}
	} 
}

?>