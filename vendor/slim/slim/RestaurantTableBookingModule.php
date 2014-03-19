<?php
function convertDatetime($date_str) {

list($date, $time) = explode(' ', $date_str);
list($year, $month, $day) = explode('-', $date);
list($hour, $minute, $second) = explode(':', $time);

$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

return $timestamp;
}
function getMerchantSettings($mid){
	global $redis;
	$limit = 1000;
	$returnValue = array();
	if($result = $redis->keys("merchantSetting|" . $mid . "*")){
		unset($result['merchantSetting|' . $mid . "|_general"]);
		$values = call_user_func(array($redis ,"mget"), $result);
		foreach($values as $value){
			parse_str($value, $tempArr);
			$returnValue = array_merge($returnValue, $tempArr);
		}
	}
	$value = $redis->get("merchantSetting|" . $mid . "|_general");
	parse_str($value, $tempArr);
	$returnValue = array_merge($returnValue, $tempArr);
	return $returnValue;

}
function resetMerchantSettingsCache($mid = null){
	if(is_null($mid))
		$sql = "SELECT * FROM merchant_setting";
	else
		$sql = "SELECT * FROM merchant_setting WHERE key LIKE '" . $mid . "|%";
	
	$result = DB::query($sql);
	$returnValue = array();
	$temp = $result[$mid . '|_general'];
	unset($result[$mid . '|_general']);
	foreach($result as $key=>$value){
		parse_str($value, $result);
		$returnValue = array_merge($returnValue, $result);
	}
	parse_str($temp, $tempArr);
	$returnValue = array_merge($returnValue, $tempArr);
	return $returnValue;
	
		
}
function setMerchantSettings($mID, $values){
	global $redis;
	$limit = 900;
	ksort($values);
	if(strlen(http_build_query($values)) > $limit){
		$tempArr = $values;
		uasort($tempArr, function($a, $b){
			$alen = strlen($a);
			$blen = strlen($b);
			if($alen == $blen)
				return 0;
			return ($alen > $blen?-1:1);
		});
		$loop = true;
		$tempArr2 = array();
		while ($loop && list($key, $val) = each($tempArr)){
			if(strlen(http_build_query(array($key=>$val))) > $limit){
				$theKey = $mID . "|" . $key;
				$theValue= http_build_query(array($key=>$val));
				DB::insertUpdate('merchant_settings', $values = array(
					'key' => $theKey,
					'value' => $theValue
				));

				$redis->set('merchantSetting|' . $theKey, $theValue);
				unset($tempArr[$key]);
			} else
				$loop = false;
		}
		ksort($tempArr);
		$theKey = $mID . "|" . "_general";
		$theValue = http_build_query($tempArr);
		/*	
		DB::insert('merchants_settings', $values = array(
			'key' => $theKey,
			'value' => $theValue
		));
		*/
		DB::insertUpdate('merchant_settings', $values = array(
			'key' => $theKey,
			'value' => $theValue
		));
		$redis->set('merchantSetting|' . $theKey, $theValue);
	}else{
		$theKey = $mID . "|" . "_general";
		$theValue = http_build_query($values);
		/*
		DB::insert('merchants_settings', $values = array(
			'key' => $theKey,
			'value' => $theValue
		));
		*/
		$redis->set('merchantSetting|' . $theKey, $theValue);
		$theKey = $mID . "|" . "_general";
	}
}


class RestaurantTableBookingModule{
	public static $redis = null;

	private static $merchantID = null;
	private static $bookingDatetime = null;
	private static $covers = null;
	private static $theDate = null;


	private static $bookingStartDatetime = null;
	private static $bookingEndDatetime = null;
	private static $bookingStartTimestamp= null;
	private static $bookingEndTimestamp= null;
	private static $sessionDate = null;
	private static $sessionStartDatetime = null;
	private static $sessionEndDatetime = null;
	private static $sessionStartTimestamp= null;
	private static $sessionEndTimestamp= null;


	private static function setStaticVar($mid, $bookingDatetime, $covers){
		if(isset(RestaurantTableBookingModule::$mid) && ( RestaurantTableBookingModule::$mid == $mid) && 
			isset(RestaurantTableBookingModule::$bookingDatetime) && (RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime)){

		}else{
			RestaurantTableBookingModule::$merchantID = $mid;
			RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime;
			global $restaurantTemplateService;
			$merchantTemplate = $restaurantTemplateService->getTemplate($mid, $bookingDatetime);
			
			/*$session = $merchantTemplate->getSession($bookingDatetime);
			RestaurantTableBookingModule::$bookingStartDatetime = $session->getStartTime();
			RestaurantTableBookingModule::$bookingEndDatetime = $session->getStartTime() + 60 * $session->getSessionLength();
			*/
			//for testing purpose

			RestaurantTableBookingModule::$bookingStartDatetime = $bookingDatetime;			
			$ts = strtotime($bookingDatetime);
			RestaurantTableBookingModule::$sessionDate = date("Y-n-j H:i:s", mktime(0, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			RestaurantTableBookingModule::$sessionStartDatetime = date("Y-n-j H:i:s", mktime(18, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			RestaurantTableBookingModule::$sessionEndDatetime = date("Y-n-j H:i:s", mktime(25, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			RestaurantTableBookingModule::$bookingEndDatetime =  date("Y-n-j H:i:s", strtotime(RestaurantTableBookingModule::$bookingStartDatetime) + 60 * 120);


			
			RestaurantTableBookingModule::$sessionStartTimestamp = strtotime(RestaurantTableBookingModule::$sessionStartDatetime);
			RestaurantTableBookingModule::$sessionEndTimestamp = strtotime(RestaurantTableBookingModule::$sessionEndDatetime);
			RestaurantTableBookingModule::$bookingStartTimestamp = strtotime(RestaurantTableBookingModule::$bookingStartDatetime);
			RestaurantTableBookingModule::$bookingEndTimestamp = strtotime(RestaurantTableBookingModule::$bookingEndDatetime);
			

			global $redis;
			RestaurantTableBookingModule::$redis = $redis;
			RestaurantTableBookingModule::$covers = $covers;
		}
	}
	private static function getKey($cover){
		return ("restaurantTableCache|" . RestaurantTableBookingModule::$merchantID . '|' . date('Ymd', strtotime(RestaurantTableBookingModule::$sessionDate)) . '|' . $cover);
	}
	public static function isAvailable($mid, $bookingDatetime, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$setting = getMerchantSettings($mid);
		$bookingLength = intval($setting['tableBookingLength']);
		$bookingInterval = intval($setting['tableBookingInterval']);
		$returnValue = true;
		
		$cache = RestaurantTableBookingModule::getCache($mid, $bookingDatetime, $covers);
		$startDatetime = RestaurantTableBookingModule::$bookingStartDatetime;
		$startTimestamp = RestaurantTableBookingModule::$bookingStartTimestamp;
		for($i = 0; ($i < $bookingLength) && ($returnValue == true); $i = $i + $bookingInterval){
			$tempDate = mktime(date("H", $startTimestamp), date("i", $startTimestamp) + $i, 0, date("m", $startTimestamp), date("d", strtotime($startTimestamp)), date("Y", $startTimestamp));
			//echo date("Ymd His\n", $tempDate);
			//echo date("Ymd His", $this->theDate);
			$thekey = date('Hi', $tempDate);
			//var_dump($cache);
			$returnValue = (intval($cache[$thekey]) > 0);
			if($returnValue == false)
				break;
		}
		return $returnValue;
	}	
	public static function lockDB($tableID){
		$key = RestaurantTableBookingModule::getKey($tableID);
		DB::startTransaction();
		//$sql = "UPDATE cache_restaurant_tables SET locked=1 WHERE locked=0 AND thekey='" . $key . "'";
		DB::update('cache_restaurant_tables', array('locked'=>1), "locked=%d AND thekey=%s", 0, $key);
		//echo "ROW:" . DB::affectedRows();
		if(DB::affectedRows() == 0){
			DB::rollback();
			return false;
		}else{
			DB::commit();
			return true;
		}

	}
	public static function isLockReady($tableID){
		$key = RestaurantTableBookingModule::getKey($tableID);

		$sql = "select count(*) from cache_restaurant_tables where thekey='" . $key . "'";
		if(DB::queryFirstField($sql) == 0){
			DB::insert('cache_restaurant_tables', array('thekey'=>$key, 'locked'=>0));
		}
		return true;
	}
	public static function lock($mid, $bookingDatetime, $covers, $table, $bookingLength){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);

		$bookingLength = intval($mSetting['tableBookingLength']);
		$bookingInterval = intval($mSetting['tableBookingInterval']);
		$startDatetime = RestaurantTableBookingModule::$bookingStartDatetime;

		$key = RestaurantTableBookingModule::getKey($covers);
		
		if(RestaurantTableBookingModule::isLockReady($table->getTableId()) && RestaurantTableBookingModule::lockDB($table->getTableId())){
			return true;
		}else{
			return false ;
		}
		
	}
	
	public static function unlock($mid, $bookingDatetime, $covers, $restaurantTable, $bookingLength){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);
		$key = RestaurantTableBookingModule::getKey($restaurantTable->getTableId());
		$sql = "SELECT count(*) FROM cache_restaurant_tables WHERE thekey='" . $key . "'";
		
		if(DB::queryFirstField($sql) == 0){
			RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $covers);
		}
		if(RestaurantTableBookingModule::unlockDB($restaurantTable->getTableId())){
			return 1;
		}else{
			return -1;
		}

	}

	public static function unlockDB($tableID){
		$key = RestaurantTableBookingModule::getKey($tableID);
		DB::startTransaction();
		DB::update('cache_restaurant_tables', array('locked'=>0), "thekey=%s", $key);
		if(DB::affectedRows() == 0){
			//ERROR!!! but unlock it whatever
			DB::commit();
			return false;
		}else{
			DB::commit();
			return true;
		}
	}

	public static function commit($mid, $bookingDatetime, $covers, $restaurantTable, $bookingLength){

		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);
		if(RestaurantTableBookingModule::isAvailable($mid, $bookingDatetime, $covers, $restaurantTable, $bookingLength)){
			RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $covers);
			return true;
		}else{
			return false;
		}
		/*
		$bookingLength = intval($mSetting['tableBookingLength']);
		$bookingInterval = intval($mSetting['tableBookingInterval']);
		$bookingStart = RestaurantTableBookingModule::$bookingStartDatetime;
		$bookingEnd = RestaurantTableBookingModule::$bookingEndDatetime;
		$bookingStartTimestamp = RestaurantTableBookingModule::$bookingStartTimestamp;
		$bookingEndTimestamp = RestaurantTableBookingModule::$bookingEndTimestamp;
		$passed = true;
		//$tempResult = array();
		//$this->redis->multi();
		//$tempResult[] = $key;

		$tableID = $restaurantTable->getTableId();
		//$sql = "SELECT * FROM restaurant_table WHERE restaurant_table_id = %d" ;
		//$rs = DB::queryFirstRow($sql, $tableID);
		$minCover = $restaurantTable->getMinCover();
		$maxCover = $restaurantTable->getMaxCover();
		for($cover = intval($minCover); $cover <= intval($maxCover) ; $cover++){
			$tempArr = RestaurantTableBookingModule::getCache($mid, $bookingDatetime, $covers);
			for($i = 0; $passed && ($i < $bookingLength) ; $i = $i + $bookingInterval){
				$tempDate = mktime(date("H", $bookingStartTimestamp), date("i", $bookingStartTimestamp) + $i, 0, date("m", $bookingStartTimestamp), date("d", $bookingStartTimestamp), date("Y", $bookingStartTimestamp));
				
				$thekey = date('Hi', $tempDate);
				$passed = (intval($tempArr[$thekey]) > 0);
				//$this->redis->hmset($key, $thekey, intval($tempArr[$thekey]) - 1);
				//$tempResult[$thekey] = intval($tempArr[$thekey]) - 1;
			}	
			if($passed){
				RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $covers);
				//call_user_func_array(array($this->redis, "hmset"), $tempResult);
			}else{
				//$this->redis->discard();
			}
		}

		return $passed;
		*/
	}

	public static function resetCache($mid, $bookingDatetime, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$OSCache = getMerchantSettings($mid);

		$seatArr = array();
		
		//echo "resetCache\n";
		/*
		$bookingSeatList = ($OSCache['tableSeatOptions']);
		$tmpArr = explode(",", $bookingSeatList); 
		foreach($tmpArr as $v){
			$seatArr[] = intval($v);
		}
		foreach($cache as $key=>$value){ 
			if((substr($key, 0, 8) == 'tableFor') && (intval(substr($key, 8)) > 0)){
				$seatArr[] = intval(substr($key, 8));
			}
		}
		*/
		$bookingLength = intval($OSCache['tableBookingLength']);
		$bookingInterval = intval($OSCache['tableBookingInterval']);
		$bookingStartDatetime = RestaurantTableBookingModule::$bookingStartDatetime;
		$bookingEndDatetime = RestaurantTableBookingModule::$bookingEndDatetime;
		$bookingStartTimestamp= RestaurantTableBookingModule::$bookingStartTimestamp;
		$bookingEndTimestamp= RestaurantTableBookingModule::$bookingEndTimestamp;
		$sessionStartTimestamp= RestaurantTableBookingModule::$sessionStartTimestamp;
		$sessionEndTimestamp= RestaurantTableBookingModule::$sessionEndTimestamp;
		//$bookingStartDatetime = mktime(intval(substr($bookingStart, 0, 2)), intval(substr($bookingStart, 2, 2)),   0,  date("n", $this->theDate), date("j", $this->theDate), date("Y", $this->theDate));
		//$bookingEndDatetime = mktime(intval(substr($bookingStart, 0, 2)), intval(substr($bookingStart, 2, 2)) + intval($OSCache['tableBookingEnd']),   0,  date("n", $bookingStartDatetime), date("j", $bookingStartDatetime), date("Y", $bookingStartDatetime));
		
		$bookingCoverList = explode(',', $OSCache['tableCoverList']);

		$maxBookingCover = max($bookingCoverList);
		$minBookingCover = min($bookingCoverList);
		$sql = "SELECT * FROM restaurant_table WHERE merchant_id = %d AND max_cover <= %d AND min_cover >= %d";
		$rs = DB::query($sql, $mid, $maxBookingCover, $minBookingCover);
		$tableArr = array();
		foreach($bookingCoverList as $cover){
			for($i = 0; $i < sizeof($rs) ; $i++){
				if(intval($rs[$i]['min_cover']) <= $cover && intval($rs[$i]['max_cover']) >= $cover){
					for($j = $sessionStartTimestamp; $j <= $sessionEndTimestamp; $j = $j + ($bookingInterval * 60) ){
						$timeKey = date('Hi', $j);
						if(!isset($tableArr[$cover]["" . $timeKey]))
							$tableArr[$cover]["" . $timeKey] = 0;
						$tableArr[$cover]["" . $timeKey] ++;
					}
				}
			}
		}
		$sql = "SELECT *, (DATE_ADD(b.booking_ts, INTERVAL b.booking_length MINUTE)) AS booking_end_ts FROM booking_restaurant_table brt LEFT JOIN restaurant_table rt ON brt.restaurant_table_id = rt.restaurant_table_id LEFT JOIN booking b ON brt.booking_id = b.booking_id WHERE rt.merchant_id = %d AND (b.booking_ts BETWEEN %s AND %s)";
		$rs2 = DB::query($sql , $mid, date("Y-m-d H:i:s", $sessionStartTimestamp), date("Y-m-d H:i:s", $sessionEndTimestamp));
		
		for($j = 0; $j < sizeof($rs2); $j++){
			for($k = intval($rs2[$j]['min_cover']) ; $k <= intval($rs2[$j]['max_cover']) ; $k++){
				if(in_array($k, $bookingCoverList)){
					for($t = convertDatetime($rs2[$j]['booking_ts']) ; $t < convertDatetime($rs2[$j]['booking_end_ts']) ; $t = $t + 60){
						$timeKey = date('Hi', $t);
						if(isset($tableArr[$k]["" . $timeKey])){
							$tableArr[$k]["" . $timeKey] -- ;

						}
					}
				}
			}
		}
		
		foreach($bookingCoverList as $cover){
			$key = RestaurantTableBookingModule::getKey($cover);
			RestaurantTableBookingModule::$redis->del($key);
			RestaurantTableBookingModule::$redis->multi();
			for($i = $sessionStartTimestamp; $i <= $sessionEndTimestamp; $i = $i + ($bookingInterval * 60)){
				if(isset($tableArr[$cover])){
					$timeKey = date('Hi', $i);
					//echo $tableArr[$cover]["" . $timeKey];
					RestaurantTableBookingModule::$redis->hmset($key, $timeKey, $tableArr[$cover]["" . $timeKey]);		
				}
			}
			RestaurantTableBookingModule::$redis->exec();
		}
		

	}

	public static function getCache($mid, $bookingDatetime, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$OSCache = getMerchantSettings($mid);
		$returnValue = array();
		$key = RestaurantTableBookingModule::getKey($covers);
		$tempArr = array();
		$tempArr = RestaurantTableBookingModule::$redis->hgetall($key);
		$generated = false;
		$safeCount = 3;
		while($safeCount > 0 && (!$generated) && sizeof($tempArr) == 0 ){
			$generated = true;
			$safeCount -- ;
			RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $covers);
		}
		if(sizeof($tempArr) == 0){
			if($generated){
				$tempArr = RestaurantTableBookingModule::$redis->hgetall($key);
				return $tempArr;
			}else{
				return 0;
			}
		}else{
			return $tempArr;
		}
	}
}