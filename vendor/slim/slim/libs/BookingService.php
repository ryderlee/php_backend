<?php

require_once 'MerchantTemplateService.php';

$restaurantTemplateService = new RestaurantTemplateService();

interface BookingServiceInterface {
	public function getBestTable($merchantId, $datetime, $noOfParticipants);
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
	public function makeBookingByMerchant($tableId, $merchantId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
}

class RestaurantBookingService implements BookingServiceInterface {
	public function getBestTable($merchantId, $datetime, $noOfParticipants) {
		$datetimeParts = explode(' ', $datetime);
		$dateStr = $datetimeParts[0];
		$timeStr = $datetimeParts[1];
		
		global $restaurantTemplateService;
		$merchantTemplate = $restaurantTemplateService->getTemplate($merchantId, $datetime);
		$targetOpeningSession = null;
		foreach ($merchantTemplate->getOpeningSessions() as $openingSession) {
			$start = strtotime($openingSession->getStartTime());
			$end = $start + 60 * $openingSession->getSessionLength();
			$target = strtotime($timeStr);
			// echo "START:$start END:$end TARGET:$target |||||||||||";
			if ($target >= $start && $target <= $end) {
				$targetOpeningSession = $openingSession;
				break;
			}
		}
		if (!empty($targetOpeningSession)) {
			$floorPlanId = $targetOpeningSession->getFloorPlanId();
			
			$bestTable = DB::queryFirstRow('SELECT * FROM restaurant_table WHERE merchant_id = %d AND floor_plan_id = %d AND restaurant_table_id NOT IN (SELECT restaurant_table_id FROM booking b JOIN booking_restaurant_table bt ON b.booking_id = bt.booking_id WHERE b.merchant_id = %d AND (%s >= booking_ts AND %s < DATE_ADD(booking_ts, INTERVAL booking_length MINUTE))) AND (%d >= min_cover AND %d <= max_cover) ORDER BY max_cover ASC, min_cover ASC LIMIT 1;', $merchantId, $floorPlanId, $merchantId, $datetime, $datetime, $noOfParticipants, $noOfParticipants);
			if (!empty($bestTable)) {
				$restaurantTable = new RestaurantTable($bestTable['merchant_id'], $bestTable['restaurant_table_id'], $bestTable['restaurant_table_name'], $bestTable['actual_cover'], $bestTable['min_cover'], $bestTable['max_cover']);
				return array('booking_length'=>$targetOpeningSession->getMealDuration(), 'table'=>$restaurantTable);
			}
		}
		return null;
	}
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {
		$info = $this->getBestTable($merchantId, $datetime, $noOfParticipants);
		$restaurantTable = $info['table'];
		$bookingLength = $info['booking_length'];
		
		if (!empty($restaurantTable)) {
			$values = array(
				'user_id' => $userId, 
				'merchant_id' => $merchantId,
				'is_guest' => $isGuest,
				'session_id' => $sessionId,
				'first_name' => $firstName,
				'last_name' => $lastName,
				'phone' => $phone,
				'booking_ts' => $datetime,
				'booking_length' => $bookingLength,
				'no_of_participants' => $noOfParticipants,
				'special_request' => $specialRequest,
				'status' => 0,
				'attendance' => 0,
				'create_ts' => DB::sqleval('NOW()')
			);
			DB::insert('booking', $values);
			$bookingId = DB::insertId();
			if ($bookingId > 0) {
				DB::insert('booking_restaurant_table', array(
					'booking_id' => $bookingId,
					'restaurant_table_id' => $restaurantTable->getTableId(),
					'create_ts' => DB::sqleval('NOW()')
				));
			}
			return $bookingId;
		}
		return -1;
	}
	public function makeBookingByMerchant($tableId, $merchantId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {
		
	}
}
