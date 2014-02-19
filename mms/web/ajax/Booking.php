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
			$paraMap = array('status'=>'2');
			echo HttpService::put($resourceUri, $paraMap);
		}
	} else if ($_GET['action'] == 'getOccupancyRate') {
		$resourceUri = '/mms/occupancy/' . $merchantService->getMerchantId();
		echo HttpService::get($resourceUri);
	}
}

?>