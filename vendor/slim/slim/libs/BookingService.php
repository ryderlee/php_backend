<?php

require_once 'MerchantTemplateService.php';

$restaurantTemplateService = new RestaurantTemplateService();

interface BookingServiceInterface {
	public function getBestTable($merchantId, $datetime, $noOfParticipants);
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
	public function makeBookingByMerchant($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength);

}

class RestaurantBookingService implements BookingServiceInterface {
	private static $bookingModuleList = "RestaurantTableBookingModule";
	private $merchantTemplate  = null;
	public static function getTimeslotAvailability($merchantId, $bookingDatetime, $covers){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);
		$returnValue = array();
		foreach($moduleArr as $m){
			$cache = call_user_func(array($m, "getCache"), $merchantId, $bookingDatetime, $covers);
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
		$this->merchantTemplate = $merchantTemplate;
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
	public function addBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength) {
		if(sizeof($arrOfTables) > 0){
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
				'status' => $status,
				'attendance' => $attendance,
				'create_ts' => DB::sqleval('NOW()')
			);
			DB::insert('booking', $values);
			$bookingId = DB::insertId();
			if ($bookingId > 0) {
				foreach($arrOfTables as $tableObj){
					DB::insert('booking_restaurant_table', array(
						'booking_id' => $bookingId,
						'restaurant_table_id' => $tableObj->getTableId(),
						'create_ts' => DB::sqleval('NOW()')
					));
				}
			}
			return $bookingId;
		}else{
			return false;
		}

	}

	public function checkBookingConflict($bookingId=null, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength){
	//do something
	$sql = "SELECT DISTINCT b.booking_id FROM booking_restaurant_table brt LEFT JOIN booking b ON booking_id WHERE (b.booking_ts BETWEEN (%s AND %s)) OR (DATE_ADD(b.booking_ts, INTERVAL b.booking_length MINUTE) BETWEEN (%s AND %s))) AND brt.restaurant_table_id = %d";
	$rs = DB::query($sql, $datetime, $bookingEndDatetime, $bookingStartDatetime, $bookingEndDatetime, $tableId);
	if(sizeof($rs) > 0){	
		$returnValue = array();
		foreach($rs as $r){
			$returnValue[] = $r['booking_id'];
		}
		return $returnValue;
	}else{
		return false;
	}
}

	public function makeBookingByMerchant($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength, $forced = false) {
		if(!$forced){
			//do something
			//return $value; 
		}

		if( $this->lockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength)){
			if( $bookingId = $this->addBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength)){
				$this->commitModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
			}
			$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
			return $bookingId;
		}
		$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
		//TODO return something?!?
		return true;
			
	}
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {

		$arr = array('tableBookingLength' => 120, 'tableBookingInterval' => 15, 'tableCoverList'=>'1,2,3,4,5,6');
		setMerchantSettings($merchantId, $arr);
		if($this->isAvailableModules($merchantId, $datetime, $noOfParticipants)){
			$info = $this->getBestTable($merchantId, $datetime, $noOfParticipants);
			
			if (!empty($info)) {
				$restaurantTable = $info['table'];
				$bookingLength = $info['booking_length'];
				$arrOfTables = array($restaurantTable);
				if( $this->lockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength)){
					if( $bookingId = $this->addBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, 0, 0, $arrOfTables, $bookingLength)){
						$this->commitModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
					}
					$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
					return $bookingId;
				}
				$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
				//TODO return something?!?
			}
		}
		return -1;
	}
	public function editBooking($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength) {

		if( $this->lockModules($merchantId, $datetime, $noOfParticipants, $arrayOfTables, $bookingLength)){
			$values = array(
				'is_guest' => $isGuest,
				'first_name' => $firstName,
				'last_name' => $lastName,
				'phone' => $phone,
				'booking_ts' => $datetime,
				'booking_length' => $bookingLength,
				'no_of_participants' => $noOfParticipants,
				'special_request' => $specialRequest,
				'status' => $status,
				'attendance' => $attendance
			);
			DB::update('booking',$values, "booking_id=%d",  $bookingId);

			/* for booking_restaurant_table */

			$sql = "SELECT restaurant_table_id FROM booking_restaurant_table WHERE booking_id=%d" ;

			$rs = DB::query($sql, $bookingId);
			$arrayOfTableIds = array();
			foreach($arrayOfTables as $t){
				$arrayOfTableIds[] = $t->getTableId();
			}
			for($i =0; $i < sizeof($rs); $i++){
				if(!in_array($rs[$i]['restaurant_table_id'], $arrayOfTableIds)){
					DB::delete('booking_restaurant_table', "booking_id=%d AND restaurant_table_id=%d", $bookingId, $rs[$i]['restaurant_table_id']);

				}else{
					if(($key = array_search($rs[$i]['restaurant_table_id'] , $arrayOfTableIds)) !== false){
						unset($arrayOfTableIds[$key]);
					}
				}
			}
			foreach($arrayOfTableIds as $value){
				DB::insert('booking_restaurant_table', array(
					'booking_id' => $bookingId,
					'restaurant_table_id' => $value,
					'create_ts' => DB::sqleval('NOW()')
				));
			}
			/* end:for booking_restaurant_table */


			$this->commitModules($merchantId, $datetime, $noOfParticipants, $arrayOfTables, $bookingLength);
			$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrayOfTables, $bookingLength);
			return $bookingId;
		}
		$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrayOfTables, $bookingLength);
		//TODO always return true
		return false;
	}
}
