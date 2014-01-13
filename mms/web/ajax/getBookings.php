<?php

require_once dirname(__FILE__) . '/../common/global.php';

$resourceUri = '/mms/bookings/' . $merchantService->getMerchantId();
if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
	$resourceUri .= '/' . $_GET['bookingId'];
}
echo HttpService::get($resourceUri);

?>