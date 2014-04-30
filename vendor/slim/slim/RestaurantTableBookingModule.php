<?php
function convertDatetime($date_str) {

list($date, $time) = explode(' ', $date_str);
list($year, $month, $day) = explode('-', $date);
list($hour, $minute, $second) = explode(':', $time);

$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

return $timestamp;
}
function getMerchantSettings($mid){
	
	$arr = array("tableBookingInterval" => 15, "tableCoverList" => "1,2,3,4,5,6");
	setMerchantSettings($mid, $arr);
	
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
	private static $currentSessionDate = null;
	private static $currentSessionStartDatetime = null;
	private static $currentSessionEndDatetime = null;
	private static $currentSessionStartTimestamp= null;
	private static $currentSessionEndTimestamp= null;
	private static $bookingLength = null;
	private static $merchantTemplate = null;
	private static $VIPType = null;
	
	private static $openingSessions = null;


	private static function setStaticVar($mid, $bookingDatetime, $VIPType, $covers){
		if(isset(RestaurantTableBookingModule::$mid) && ( RestaurantTableBookingModule::$mid == $mid) && 
			isset(RestaurantTableBookingModule::$bookingDatetime) && (RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime)){

		}else{
			RestaurantTableBookingModule::$merchantID = $mid;
			RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime;
			RestaurantTableBookingModule::$VIPType = $VIPType;
			global $restaurantTemplateService;
			$merchantTemplate = $restaurantTemplateService->getTemplate($mid, $bookingDatetime);
			RestaurantTableBookingModule::$merchantTemplate = $merchantTemplate;
			/*$session = $merchantTemplate->getSession($bookingDatetime);
			RestaurantTableBookingModule::$bookingStartDatetime = $session->getStartTime();
			RestaurantTableBookingModule::$bookingEndDatetime = $session->getStartTime() + 60 * $session->getSessionLength();
			*/
			//for testing purpose

			RestaurantTableBookingModule::$bookingStartDatetime = $bookingDatetime;			
			// $ts = strtotime($bookingDatetime);
			// RestaurantTableBookingModule::$currentSessionDate = date("Y-n-j H:i:s", mktime(0, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			// RestaurantTableBookingModule::$sessionStartDatetime = date("Y-n-j H:i:s", mktime(18, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			// RestaurantTableBookingModule::$sessionEndDatetime = date("Y-n-j H:i:s", mktime(25, 0, 0, date("n", $ts), date("j", $ts), date("Y", $ts))) ;	
			// RestaurantTableBookingModule::$bookingEndDatetime =  date("Y-n-j H:i:s", strtotime(RestaurantTableBookingModule::$bookingStartDatetime) + 60 * 120);
			
			
			RestaurantTableBookingModule::$openingSessions = array();
			if (!empty($merchantTemplate)) {
				$openingSession = $merchantTemplate->getOpeningSession($bookingDatetime);
				
				RestaurantTableBookingModule::$openingSessions = $merchantTemplate->getOpeningSessions();
				RestaurantTableBookingModule::$currentSessionDate = $merchantTemplate->getTemplateDate();
				if (!empty($openingSession)) {
					RestaurantTableBookingModule::$currentSessionStartDatetime = $merchantTemplate->getTemplateDate()." ".$openingSession->getStartTime();
					RestaurantTableBookingModule::$currentSessionEndDatetime = date("Y-m-d H:i:s", strtotime(RestaurantTableBookingModule::$currentSessionStartDatetime) + 60 * $openingSession->getSessionLength());
					RestaurantTableBookingModule::$bookingEndDatetime = date("Y-m-d H:i:s", strtotime($bookingDatetime) + 60 * $openingSession->getMealDuration());
					
					RestaurantTableBookingModule::$currentSessionStartTimestamp = strtotime(RestaurantTableBookingModule::$currentSessionStartDatetime);
					RestaurantTableBookingModule::$currentSessionEndTimestamp = strtotime(RestaurantTableBookingModule::$currentSessionEndDatetime);
					RestaurantTableBookingModule::$bookingStartTimestamp = strtotime(RestaurantTableBookingModule::$bookingStartDatetime);
					RestaurantTableBookingModule::$bookingEndTimestamp = strtotime(RestaurantTableBookingModule::$bookingEndDatetime);
					RestaurantTableBookingModule::$bookingLength = $openingSession->getMealDuration();
				}
			}
			

			global $redis;
			RestaurantTableBookingModule::$redis = $redis;
			RestaurantTableBookingModule::$covers = $covers;
		}
		return true;
	}
	private static function getKey($cover){
		return ("restaurantTableCache|" . RestaurantTableBookingModule::$merchantID . '|' . date('Ymd', strtotime(RestaurantTableBookingModule::$currentSessionDate)) . '|' . RestaurantTableBookingModule::$VIPType . '|' . $cover);
	}
	public static function isAvailable($mid, $bookingDatetime, $VIPType, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
		$setting = getMerchantSettings($mid);
		$bookingLength = RestaurantTableBookingModule::$bookingLength;
		$bookingInterval = intval($setting['tableBookingInterval']);
		$returnValue = true;
		
		$cache = RestaurantTableBookingModule::getCache($mid, $bookingDatetime, $VIPType, $covers);
		$startDatetime = RestaurantTableBookingModule::$bookingStartDatetime;
		$startTimestamp = RestaurantTableBookingModule::$bookingStartTimestamp;
		for($i = 0; ($i < $bookingLength) && ($returnValue == true); $i = $i + $bookingInterval){
			$tempDate = mktime(date("H", $startTimestamp), date("i", $startTimestamp) + $i, 0, date("m", $startTimestamp), date("d", strtotime($startTimestamp)), date("Y", $startTimestamp));
			//echo date("Ymd His\n", $tempDate);
			//echo date("Ymd His", $this->theDate);
			$thekey = date('Hi', $tempDate);
			//var_dump($cache);
			if(isset($cache[$thekey])){
				$returnValue = (intval($cache[$thekey]) > 0);
			}
			if($returnValue == false)
				break;
		}
		return $returnValue;
	}	
	public static function lockDB($tables){
		$keys = array();
		foreach($tables as $t){
			$keys[] = RestaurantTableBookingModule::getKey($t->getTableId());
		}
		$tempStr = "'" .  implode("', '", $keys) . "'";
		DB::startTransaction();
		//$sql = "UPDATE cache_restaurant_tables SET locked=1 WHERE locked=0 AND thekey='" . $key . "'";
		DB::update('cache_restaurant_tables', array('locked'=>1), "locked=%d AND thekey IN (" . $tempStr . ")", 0);
		//echo "ROW:" . DB::affectedRows();
		if(DB::affectedRows() == sizeof($tables)){
			DB::commit();
			return true;
		}else{
			DB::rollback();
			return false;
		}

	}
	public static function isLockReady($tables){
		$keys = array();
		foreach($tables as $t){
			$keys[] = RestaurantTableBookingModule::getKey($t->getTableId());
		}
		$tempStr = "'" . implode("', '", $keys) . "'";
		$sql = "select count(*) from cache_restaurant_tables where thekey IN (" . $tempStr . ")";
		if(DB::queryFirstField($sql) <> sizeof($tables)){
			foreach($keys as $key){
				DB::insertIgnore('cache_restaurant_tables', array('thekey'=>$key, 'locked'=>0));
			}
		}
		return true;
	}
	public static function lock($mid, $bookingDatetime, $VIPType, $covers, $tables, $bookingLength){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
		$mSetting = getMerchantSettings($mid);

		$bookingLength = RestaurantTableBookingModule::$bookingLength;
		$bookingInterval = intval($mSetting['tableBookingInterval']);
		$startDatetime = RestaurantTableBookingModule::$bookingStartDatetime;

		$key = RestaurantTableBookingModule::getKey($covers);
		if(RestaurantTableBookingModule::isLockReady($tables) && RestaurantTableBookingModule::lockDB($tables)){
			return true;
		}else{
			return false ;
		}
		
	}
	
	public static function unlock($mid, $bookingDatetime, $covers, $restaurantTables, $bookingLength){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
		$mSetting = getMerchantSettings($mid);
		$cache = RestaurantTableBookingModule::getCache($mid, $bookingDatetime, $VIPType, $covers);
		
		if(sizeof($cache) == 0){
			RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $VIPType, $covers);
		}
		if(RestaurantTableBookingModule::unlockDB($restaurantTables)){
			return 1;
		}else{
			return -1;
		}

	}

	public static function unlockDB($tables){
		$keys = array();
		foreach($tables as $t){
			$keys[] = RestaurantTableBookingModule::getKey($t->getTableId());
		}
		$tempStr = "'" .  implode("', '", $keys) . "'";
		DB::startTransaction();
		DB::update('cache_restaurant_tables', array('locked'=>0), "thekey IN (" . $tempStr . ")" );
		if(DB::affectedRows() == sizeof($tables)){
			DB::commit();
			return true;
		}else{
			//ERROR!!! but unlock it whatever
			DB::commit();
			return false;
		}
	}

	public static function commit($mid, $bookingDatetime, $VIPType, $covers, $restaurantTable, $bookingLength){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
		$mSetting = getMerchantSettings($mid);
		RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $VIPType, $covers);
		return true;
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

	public static function resetCache($mid, $bookingDatetime, $VIPType, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
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
		
		
		$bookingLength = RestaurantTableBookingModule::$bookingLength;
		$bookingInterval = intval($OSCache['tableBookingInterval']);
		$bookingStartDatetime = RestaurantTableBookingModule::$bookingStartDatetime;
		$bookingEndDatetime = RestaurantTableBookingModule::$bookingEndDatetime;
		$bookingStartTimestamp= RestaurantTableBookingModule::$bookingStartTimestamp;
		$bookingEndTimestamp= RestaurantTableBookingModule::$bookingEndTimestamp;
		
		$bookingCoverList = explode(',', $OSCache['tableCoverList']);
		
		foreach($bookingCoverList as $cover){
			$key = RestaurantTableBookingModule::getKey($cover);	
			RestaurantTableBookingModule::$redis->del($key);
		}
		
		foreach(RestaurantTableBookingModule::$openingSessions as $os){
			$currentSessionStartTimestamp= strtotime(RestaurantTableBookingModule::$merchantTemplate->getTemplateDate() . " " .   $os->getStartTime());
			$currentSessionEndTimestamp= $currentSessionStartTimestamp + 60 * $os->getSessionLength();
			//$bookingStartDatetime = mktime(intval(substr($bookingStart, 0, 2)), intval(substr($bookingStart, 2, 2)),   0,  date("n", $this->theDate), date("j", $this->theDate), date("Y", $this->theDate));
			//$bookingEndDatetime = mktime(intval(substr($bookingStart, 0, 2)), intval(substr($bookingStart, 2, 2)) + intval($OSCache['tableBookingEnd']),   0,  date("n", $bookingStartDatetime), date("j", $bookingStartDatetime), date("Y", $bookingStartDatetime));
			global $restaurantTemplateService;	
			
	
			$maxBookingCover = max($bookingCoverList);
			$minBookingCover = min($bookingCoverList);
			$templateObj = $restaurantTemplateService->getTemplate($mid, $bookingDatetime);
			$VIPTablesArr = explode(',', $templateObj->getVIPTableIds());
			$sql = "SELECT * FROM restaurant_table WHERE merchant_id = %d AND max_cover <= %d AND min_cover >= %d";
			$rs = DB::query($sql, $mid, $maxBookingCover, $minBookingCover);
			$tableArr = array();

			foreach($bookingCoverList as $cover){
				for($i = 0; $i < sizeof($rs) ; $i++){
					if($VIPType == 0 || in_array($rs[$i]['restaurant_table_id'] , $VIPTablesArr)){
						if(intval($rs[$i]['min_cover']) <= $cover && intval($rs[$i]['max_cover']) >= $cover){
							for($j = $currentSessionStartTimestamp; $j <= $currentSessionEndTimestamp; $j = $j + ($bookingInterval * 60) ){
								$timeKey = date('Hi', $j);
								if(!isset($tableArr[$cover]["" . $timeKey]))
									$tableArr[$cover]["" . $timeKey] = 0;
								$tableArr[$cover]["" . $timeKey] ++;
							}
						}
					}
				}
			}
			$sql = "SELECT *, (DATE_ADD(b.booking_ts, INTERVAL b.booking_length MINUTE)) AS booking_end_ts FROM booking_restaurant_table brt LEFT JOIN restaurant_table rt ON brt.restaurant_table_id = rt.restaurant_table_id LEFT JOIN booking b ON brt.booking_id = b.booking_id WHERE rt.merchant_id = %d AND (b.booking_ts BETWEEN %s AND %s) AND b.status>=0";
			$rs2 = DB::query($sql , $mid, date("Y-m-d H:i:s", $currentSessionStartTimestamp), date("Y-m-d H:i:s", $currentSessionEndTimestamp));
			
			for($j = 0; $j < sizeof($rs2); $j++){
				if($VIPType == 0 || in_array($rs2[$j]['restaurant_table_id'] , $VIPTablesArr)){
					for($k = intval($rs2[$j]['min_cover']) ; $k <= intval($rs2[$j]['max_cover']) ; $k++){
						if(in_array($k, $bookingCoverList)){
							for($t = convertDatetime($rs2[$j]['booking_ts']) ; $t < convertDatetime($rs2[$j]['booking_end_ts']) ; $t = $t + 60){
								$timeKey = date('Hi', $t);
								if(isset($tableArr[$k]["" . $timeKey]) && ($tableArr[$k]["".$timeKey] > 0)){
									$tableArr[$k]["" . $timeKey] -- ;
		
								}
							}
						}
					}
				}
			}
			
			foreach($bookingCoverList as $cover){
				$key = RestaurantTableBookingModule::getKey($cover);
				
				RestaurantTableBookingModule::$redis->multi();
				for($i = $currentSessionStartTimestamp; $i <= $currentSessionEndTimestamp; $i = $i + ($bookingInterval * 60)){
					if(isset($tableArr[$cover])){
						$timeKey = date('Hi', $i);
						//echo $tableArr[$cover]["" . $timeKey];
						RestaurantTableBookingModule::$redis->hmset($key, $timeKey, $tableArr[$cover]["" . $timeKey]);		
					}
				}
				RestaurantTableBookingModule::$redis->exec();
			}
		}

	}

	public static function getCache($mid, $bookingDatetime, $VIPType, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $VIPType, $covers);
		$OSCache = getMerchantSettings($mid);
		$returnValue = array();
		$key = RestaurantTableBookingModule::getKey($covers);
		$tempArr = array();
		$tempArr = RestaurantTableBookingModule::$redis->hgetall($key);
		$generated = false;
		$safeCount = 3;
		while($safeCount > 0 && (!$generated) && sizeof($tempArr) == 0 && !isset($tempArr[date("Hi", strtotime($bookingDatetime))]) ){
			$generated = true;
			$safeCount -- ;
			RestaurantTableBookingModule::resetCache($mid, $bookingDatetime, $VIPType, $covers);
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