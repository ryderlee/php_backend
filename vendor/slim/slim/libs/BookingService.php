<?php

require_once 'MerchantTemplateService.php';

$restaurantTemplateService = new RestaurantTemplateService();

interface BookingServiceInterface {
	public function getBestTable($merchantId, $datetime, $noOfParticipants);
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
	public function makeBookingByMerchant($tableId, $merchantId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
}

class RestaurantBookingService implements BookingServiceInterface {
	private static $bookingModuleList = "RestaurantTableBookingModule";
	public static function getTimeslotAvailability($merchantId, $bookingDatetime, $covers){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);
		$returnValue = array();
		foreach($moduleArr as $m){
			$cache = RestaurantTableBookingModule::getCache($merchantId, $bookingDatetime, $covers);
			foreach($cache as $key=>$value){
				if(!isset($returnValue[$key]))
					$returnValue[$key] = 1;
				if($value ==0)
					$returnValue[$key] = 0;
			}
		}
		return $returnValue;
	}
	public function getBestTable($merchantId, $datetime, $noOfParticipants) {
		$datetimeParts = explode(' ', $datetime);
		$dateStr = $datetimeParts[0];
		$timeStr = $datetimeParts[1];
		
		global $restaurantTemplateService;
		$merchantTemplate = $restaurantTemplateService->getTemplate($merchantId, $datetime);
		if (!empty($merchantTemplate)) {
			$targetOpeningSession = $merchantTemplate->getOpeningSession($datetime);
			if (!empty($targetOpeningSession)) {
				$floorPlanId = $targetOpeningSession->getFloorPlanId();
				
				$bestTable = DB::queryFirstRow('SELECT * FROM restaurant_table WHERE merchant_id = %d AND floor_plan_id = %d AND restaurant_table_id NOT IN (SELECT restaurant_table_id FROM booking b JOIN booking_restaurant_table bt ON b.booking_id = bt.booking_id WHERE b.merchant_id = %d AND (%s >= booking_ts AND %s < DATE_ADD(booking_ts, INTERVAL booking_length MINUTE))) AND (%d >= min_cover AND %d <= max_cover) ORDER BY max_cover ASC, min_cover ASC LIMIT 1;', $merchantId, $floorPlanId, $merchantId, $datetime, $datetime, $noOfParticipants, $noOfParticipants);
				if (!empty($bestTable)) {
					$restaurantTable = new RestaurantTable($bestTable['merchant_id'], $bestTable['restaurant_table_id'], $bestTable['restaurant_table_name'], $bestTable['actual_cover'], $bestTable['min_cover'], $bestTable['max_cover']);
					return array('booking_length'=>$targetOpeningSession->getMealDuration(), 'table'=>$restaurantTable);
				}
			}
		}
		return null;
	}
	private function lockModules($merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);

		$passed = true;
		foreach($moduleArr as $m){
			$passed = call_user_func(array($m, "lock") , $merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);	
			if(!$passed)
				break;

		}
		return $passed;
	}
	
	private function unlockModules($merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);

		$passed = true;
		foreach($moduleArr as $m){
			$passed = call_user_func(array($m, "unlock") , $merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);	
			if(!$passed)
				break;

		}
		return $passed;
	}	
	private function commitModules($merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);

		$passed = true;
		foreach($moduleArr as $m){
			$passed = call_user_func(array($m, "commit") , $merchantID, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);	
			if(!$passed)
				break;

		}
		return $passed;
	}	

	public function isAvailableModules($merchantId, $bookingDatetime, $noOfParticipants){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);

		$passed = true;
		foreach($moduleArr as $m){
			$passed = call_user_func(array($m, "isAvailable") , $merchantId, $bookingDatetime, $noOfParticipants);	
			if(!$passed)
				break;
		}
		return $passed;
	}
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {

		$arr = array('tableBookingLength' => 120, 'tableBookingInterval' => 15, 'tableCoverList'=>'1,2,3,4,5,6');
		setMerchantSettings($merchantId, $arr);
		if($this->isAvailableModules($merchantId, $datetime, $noOfParticipants)){
			$info = $this->getBestTable($merchantId, $datetime, $noOfParticipants);
			
			if (!empty($info)) {
				$restaurantTable = $info['table'];
				$bookingLength = $info['booking_length'];
				if( $this->lockModules($merchantId, $datetime, $noOfParticipants, $restaurantTable, $bookingLength)){
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
					$this->commitModules($merchantId, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);
					$this->unlockModules($merchantId, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);
					return $bookingId;
				}
				$this->unlockModules($merchantId, $datetime, $noOfParticipants, $restaurantTable, $bookingLength);
				//TODO return something?!?
			}
		}
		return -1;
	}


	public function makeBookingByMerchant($tableId, $merchantId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {
		
	}
}
