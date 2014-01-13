<?php

require_once dirname(__FILE__) . '/../common/global.php';

$url = CONFIG__API_URL . '/mms/bookings/' . $merchantService->getMerchantId();
if (isset($_GET['bookingId']) && $_GET['bookingId'] > 0) {
	$url .= '/' . $_GET['bookingId'];
}
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

echo $result;

?>