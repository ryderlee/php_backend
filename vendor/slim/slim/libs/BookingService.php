<?php

require_once 'MerchantTemplateService.php';

$restaurantTemplateService = new RestaurantTemplateService();

interface BookingServiceInterface {
	public function getBestTable($merchantId, $datetime, $bookingLength, $noOfParticipants, $targetOpeningSession);
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest);
	public function makeBookingByMerchant($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength);

}

class RestaurantBookingService implements BookingServiceInterface {
	private static $bookingModuleList = "RestaurantTableBookingModule";
	public static function getTimeslotAvailability($merchantId, $bookingDatetime, $covers, $type=0){
		$moduleArr = explode(",", RestaurantBookingService::$bookingModuleList);
		$returnValue = array();
		foreach($moduleArr as $m){
			$cache = call_user_func(array($m, "getCache"), $merchantId, $bookingDatetime, $type, $covers);
			foreach($cache as $key=>$value){
				if(!isset($returnValue[$key]))
					$returnValue[$key] = 1;
				if($value ==0)
					$returnValue[$key] = 0;
			}
		}
		return $returnValue;
	}
	public function getUnavailableTables($merchantId, $datetime, $bookingLength, $noOfParticipants, $targetOpeningSession, $excludeBookingId=-1) {
		$datetimeParts = explode(' ', $datetime);
		$dateStr = $datetimeParts[0];
		$timeStr = $datetimeParts[1];
		$bookingEndDatetime = date("Y-m-d H:i:s", strtotime($datetime) + $bookingLength * 60);
		$restaurantTables= array();
		if (!empty($targetOpeningSession)) {
			$floorPlanId = $targetOpeningSession->getFloorPlanId();
			$sql = 'SELECT * FROM restaurant_table WHERE merchant_id = %d AND floor_plan_id = %d AND restaurant_table_id IN (SELECT restaurant_table_id FROM booking b JOIN booking_restaurant_table bt ON b.booking_id = bt.booking_id WHERE b.merchant_id = %d AND (NOT ((DATE_ADD(booking_ts, INTERVAL booking_length MINUTE) <= %s) OR (%s <= booking_ts))) AND b.booking_id <> %d AND status>-1) AND (%d BETWEEN min_cover AND max_cover) ORDER BY max_cover ASC, min_cover ASC';
			$tables = DB::query($sql, $merchantId, $floorPlanId, $merchantId, $datetime, $bookingEndDatetime, $excludeBookingId, $noOfParticipants);
			for($i = 0; $i < sizeof($tables); $i++){
				$bestTable = $tables[$i];
				$restaurantTables[] = new RestaurantTable($bestTable['merchant_id'], $bestTable['restaurant_table_id'], $bestTable['restaurant_table_name'], $bestTable['actual_cover'], $bestTable['min_cover'], $bestTable['max_cover']);
			}
			if(sizeof($restaurantTables) > 0)
				return $restaurantTables;
		}
		return null;
	}
	public function getVIPTableArr($merchantID, $datetime){
		global $restaurantTemplateService;
		$templateObj = $restaurantTemplateService->getTemplate($merchantID, $datetime);

		return explode(',',$templateObj->getVIPTableIds());

	}
	public function getBestTable($merchantId, $datetime,$bookingLength, $noOfParticipants, $targetOpeningSession, $type=0) {
		$tables = $this->getAvailableTables($merchantId, $datetime, $bookingLength, $noOfParticipants, $targetOpeningSession, $type);
		if($type <> 0){
			$VIPTableArr = $this->getVIPTableArr($merchantId, $datetime);
			foreach($tables as $key=>$table)
				if(!in_array($table['table_id'], $VIPTableArr))
					unset($table[$key]);
		}
		if(sizeof($tables) > 0)
			return $tables[0];
		else
			return null;
	}
	public function getAvailableTables($merchantId, $datetime, $bookingLength, $noOfParticipants, $targetOpeningSession, $excludeBookingId=-1) {
		$datetimeParts = explode(' ', $datetime);
		$dateStr = $datetimeParts[0];
		$timeStr = $datetimeParts[1];
		$bookingEndDatetime = date("Y-m-d H:i:s", strtotime($datetime) + $bookingLength * 60);
		$restaurantTables = array();
		if (!empty($targetOpeningSession)) {
			$floorPlanId = $targetOpeningSession->getFloorPlanId();
			$sql = 'SELECT * FROM restaurant_table WHERE merchant_id = %d AND floor_plan_id = %d AND restaurant_table_id NOT IN (SELECT restaurant_table_id FROM booking b JOIN booking_restaurant_table bt ON b.booking_id = bt.booking_id WHERE b.merchant_id = %d AND (NOT ((DATE_ADD(booking_ts, INTERVAL booking_length MINUTE) <= %s) OR (%s <= booking_ts))) AND b.booking_id<> %d AND status>-1) AND (%d BETWEEN min_cover AND max_cover) ORDER BY max_cover ASC, min_cover ASC';
			$tables = DB::query($sql, $merchantId, $floorPlanId, $merchantId, $datetime, $bookingEndDatetime, $excludeBookingId, $noOfParticipants);
			for($i = 0; $i < sizeof($tables); $i++){
				$bestTable = $tables[$i];
				$restaurantTables[] = new RestaurantTable($bestTable['merchant_id'], $bestTable['restaurant_table_id'], $bestTable['restaurant_table_name'], $bestTable['actual_cover'], $bestTable['min_cover'], $bestTable['max_cover']);
			}
			if(sizeof($restaurantTables) > 0)
				return $restaurantTables;
		}
		return null;
	}

	public function getAllTables($merchantId) {
		$tables = DB::query('SELECT * FROM restaurant_table WHERE merchant_id = %d ORDER BY max_cover ASC, min_cover ASC', $merchantId);
		if (!empty($tables)) {
			for($i = 0; $i < sizeof($tables); $i++) {
				$bestTable = $tables[$i];
				$restaurantTables[] = new RestaurantTable($bestTable['merchant_id'], $bestTable['restaurant_table_id'], $bestTable['restaurant_table_name'], $bestTable['actual_cover'], $bestTable['min_cover'], $bestTable['max_cover']);
			}
			return $restaurantTables;
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
			return true;
		}
	}



	public function checkOutOfSessionConflict($bookingId=null, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength){
		global $restaurantTemplateService;
		$merchantTemplate = $restaurantTemplateService->getTemplate($merchantId, $datetime);
		$arr = $merchantTemplate->getOpeningSession($datetime);
		if(empty($arr)){
			return $arr;
		}else{
			//no session
			return false;
		}
	}

	public function checkBookingConflict($bookingId=null, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength){
		$tableIDs = array();
		foreach($arrayOfTables as $t)
			$tableIDs[] = $t->getTableId();
		$tableStr = "('" . implode("','", $tableIDs) . "')";
		$bookingEndDatetime = date("Y-m-d H:i:s", strtotime($datetime) + $bookingLength * 60);
		$sql = "SELECT DISTINCT b.booking_id FROM booking_restaurant_table brt LEFT JOIN booking b ON b.booking_id=brt.booking_id WHERE (NOT ((DATE_ADD(b.booking_ts, INTERVAL b.booking_length MINUTE) <= %s) OR (%s <= b.booking_ts))) AND status>=0 AND brt.restaurant_table_id IN " . $tableStr;
                if(!is_null($bookingId))
                        $sql = $sql . " AND b.booking_id != " . $bookingId;
                $rs = DB::query($sql, $datetime, $bookingEndDatetime );
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

	public function markAllBookingConflictByTemplate($template){
		$openSessions = $template->getOpeningSessions();

		$sessionStartTime = $template->getTemplateDate()." ".$openSessions[0]->getStartTime();
		$sessionEndTime = date("Y-m-d H:i:s", strtotime($openSessions[0]->getStartTime()) + $openSessions[0]->getSessionLength() * 60);

		for($i = 1 ; $i < sizeof($openSessions) ; $i++){
			if($sessionStartTime > ($template->getTemplateDate() . " " . $openSessions[$i]->getStartTime())) {
				$sessionStartTime = $template->getTemplateDate()." ".$openSessions[$i]->getStartTime();
			}
			if($sessionEndTime < (date("Y-m-d H:i:s", strtotime($openSessions[$i]->getStartTime()) + $openSessions[$i]->getSessionLength() * 60))) {
				$sessionEndTime = date("Y-m-d H:i:s", strtotime($openSessions[$i]->getStartTime()) + $openSessions[$i]->getSessionLength() * 60);
			}
		}

		$sessionStartTimestamp = strtotime($sessionStartTime);
		$sessionEndTimestamp = strtotime($sessionEndTime);

		$sql = "SELECT * FROM booking WHERE booking.booking_ts BETWEEN %s AND %s";
		$beforeBookingArr = DB::query($sql, $sessionStartTime, $sessionEndTime);

		$sql = "UPDATE booking SET conflict_code=0 WHERE booking.booking_ts BETWEEN %s AND %s";
		DB::query($sql, $sessionStartTime, $sessionEndTime);
		$bookingIdArr = array();
		$rs = DB::query("SELECT DISTINCT b1.booking_id FROM booking_restaurant_table brt1 LEFT JOIN booking b1 on brt1.booking_id = b1.booking_id, booking_restaurant_table brt2 LEFT JOIN booking b2 ON brt2.booking_id = b2.booking_id WHERE ABS(TIMESTAMPDIFF(MINUTE , b1.booking_ts , b2.booking_ts)) <b1.booking_length AND brt1.restaurant_table_id = brt2.restaurant_table_id AND b1.booking_id <> b2.booking_id AND b1.booking_ts BETWEEN %s AND %s", $sessionStartTime, $sessionEndTime);
		foreach ($rs as $row) {
			$bookingIdArr[] = $row['booking_id'];
		}
		if(sizeof($bookingIdArr) > 0)
			DB::query("UPDATE booking SET conflict_code=1 WHERE booking_id IN (" . implode(",", $bookingIdArr) . ")");
		
		$sql = "SELECT * FROM booking WHERE booking.booking_ts BETWEEN %s AND %s";
		$afterBookingArr = DB::query($sql, $sessionStartTime, $sessionEndTime);

		global $sns;		
		for ($i=0; $i<sizeof($beforeBookingArr); $i++) {
			if ($beforeBookingArr[$i]['conflict_code'] != $afterBookingArr[$i]['conflict_code']) {
				// Publish new message (Amazon SNS)
				$message = array(
					'topic'=>'1001',
					'bookingId'=>$beforeBookingArr[$i]['booking_id'],
					'bookingDate'=>$beforeBookingArr[$i]['booking_ts'],
					'action'=>'update'
				);
				$sns->publish(array(
					'Message' => json_encode($message),
					'TopicArn' => 'arn:aws:sns:ap-southeast-1:442675153455:merchant-1001'
				));
			}
		}
	}

	public function makeBookingByMerchant($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength, $forced = false) {
		global $restaurantTemplateService;
		if(!$forced){
			$returnValue = array();
			
			if(!(($arr = $this->checkBookingConflict(null, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength)) === false)){
				
				$conflict['name'] = 'checkBookingConflict';
				$conflict['data'] = $arr;
				$conflict['description'] = "Conflicts with other booking";
				$returnValue[] = $conflict;
			}
			if(!(($arr = $this->checkOutOfSessionConflict(null, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength))=== false)){
				$conflict['name'] = 'checkOutOfSessionConflict';
				$conflict['data'] = $arr;
				$conflict['description'] = "Not in any opening session";
				$returnValue[] = $conflict;
			}
			if(sizeof($returnValue) > 0)
				return $returnValue;
		}

		if( $this->lockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength)){
			if( $bookingId = $this->addBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength)){
				$this->commitModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
			}
			$templateObj = $restaurantTemplateService->getTemplate($merchantId, $datetime);
			$this->markAllBookingConflictByTemplate($templateObj);
			if(!(($arr = $this->checkOutOfSessionConflict($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrOfTables, $bookingLength))=== false)){
				DB::update('booking', array('conflict_code'=>'3'), 'booking_id=%d', $bookingId);
			
			}
			$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
			return $bookingId;
		}


		$this->unlockModules($merchantId, $datetime, $noOfParticipants, $arrOfTables, $bookingLength);
		//TODO return something?!?
		return true;
			
	}

	public function getVIPType($userId, $merchantId){
		$sql = "SELECT * FROM user_merchant_vip WHERE user_id = %d AND LICNO=%s";
		$result = DB::query($sql, $userId, $merchantId);
		if(sizeof($result) > 0){
			return $result[0]['type'];
		}else{
			return 0;
		}
	}
	public function makeBooking($userId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest) {
		$arr = array('tableBookingLength' => 120, 'tableBookingInterval' => 15, 'tableCoverList'=>'1,2,3,4,5,6');
		setMerchantSettings($merchantId, $arr);
		global $restaurantTemplateService;
		if($this->isAvailableModules($merchantId, $datetime, $noOfParticipants)){
		
			$merchantTemplate = $restaurantTemplateService->getTemplate($merchantId, $datetime);
			if(!empty($merchantTemplate)){
				$targetOpeningSession = $merchantTemplate->getOpeningSession($datetime);
				if(!empty($targetOpeningSession)){
					$type = $this->getVIPType($userId, $merchantId);
					$tableBookingLength = $targetOpeningSession->getMealDuration();
					$table = $this->getBestTable($merchantId, $datetime, $tableBookingLength, $noOfParticipants, $targetOpeningSession, $type);
					if($this->isBookingOverlap($userId, $datetime, $tableBookingLength, 0))
						return -1;
					if (!empty($table)) {
						$bookingLength = $tableBookingLength;
						$arrOfTables = array($table);
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
			}
		}
		return -1;
	}
	public function editBookingByMerchant($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength, $forced = false) {
		
		global $restaurantTemplateService;
		
		if(!$forced){


			$returnValue = array();

			if(!(($arr = $this->checkBookingConflict($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength)) === false)){
				
				$conflict['name'] = 'checkBookingConflict';
				$conflict['data'] = $arr;
				$conflict['description'] = "Conflicts with other booking";
				$returnValue[] = $conflict;
			}
			if(!(($arr = $this->checkOutOfSessionConflict($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength))=== false)){
				$conflict['name'] = 'checkOutOfSessionConflict';
				$conflict['data'] = $arr;
				$conflict['description'] = "Not in any opening session";
				$returnValue[] = $conflict;
			}
			if(sizeof($returnValue) > 0)
				return $returnValue;

		}

		$origDatetime =DB::queryFirstField("SELECT booking_ts FROM booking WHERE booking_id = %d", $bookingId);
		
		
		
		if( $this->editBooking($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength)){
			$templateObj = $restaurantTemplateService->getTemplate($merchantId, $datetime);

			$this->markAllBookingConflictByTemplate($templateObj);

			$templateObj2 = $restaurantTemplateService->getTemplate($merchantId, $origDatetime);

			$this->markAllBookingConflictByTemplate($templateObj2);
			if(!(($arr = $this->checkOutOfSessionConflict($bookingId, $merchantId, $isGuest, $sessionId, $firstName, $lastName, $phone, $datetime, $noOfParticipants, $specialRequest, $status, $attendance, $arrayOfTables, $bookingLength))=== false)){
				DB::update('booking', array('conflict_code'=>'3'), 'booking_id=%d', $bookingId);
			} else {
				DB::update('booking', array('conflict_code'=>'0'), 'booking_id=%d AND conflict_code=%d', $bookingId, 3);
			}
		}
		return true;

	}
	public function isBookingOverlap($userId, $newBookingDatetime, $newBookingLength, $timeGap){
		$startQueryDatetime = date("Y-m-d H:i:s", strtotime($newBookingDatetime) - $timeGap * 60);
		$endQueryDatetime = date("Y-m-d H:i:s", strtotime($newBookingDatetime) + ($newBookingLength + $timeGap) * 60);
		$sql = "SELECT booking_id FROM booking WHERE user_id=%d AND (NOT ((DATE_ADD(booking_ts, INTERVAL booking_length MINUTE) <= %s) OR (%s <= booking_ts))) AND status > -1";
		if(sizeof($rs = DB::query($sql, $userId, $startQueryDatetime, $endQueryDatetime)) > 0){
			return $rs;
		}else{
			return false;
		}
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
			if (!empty($arrayOfTables)) {
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