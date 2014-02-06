<?php

require_once dirname(__FILE__) . '/../common/global.php';

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'get') {
		$resourceUri = '/mms/bookings/' . $merchantService->getMerchantId();
		if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
			$resourceUri .= '/' . $_GET['bookingId'];
		}
		echo HttpService::get($resourceUri);
	} else if ($_GET['action'] == 'update') {
		if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
			$resourceUri = '/reservations/' . $_GET['bookingId'];
			$paraMap = array('status'=>'2');
			HttpService::put($resourceUri, $paraMap);
		}
	} else if ($_GET['action'] == 'loads') {
		$resourceUri = '/mms/loads/' . $merchantService->getMerchantId();
		echo HttpService::get($resourceUri);
	}
}

?>