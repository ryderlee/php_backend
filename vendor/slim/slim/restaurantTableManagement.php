<?php
function convertDatetime($date_str) {

list($date, $time) = explode(' ', $date_str);
list($year, $month, $day) = explode('-', $date);
list($hour, $minute, $second) = explode(':', $time);

$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

return $timestamp;
}
class RestaurantTableBookingModule{
	public $redis = null;

	private static $merchantID = null;
	private static $bookingDatetime = null;
	private static $covers = null;
	private static $theDate = null;


	private static $sessionStartDatetime = null;
	private static $sessionEndDatetime = null;
	private static $sessionDatetime = null;

	private $isLockedDB = false;

	private static function setStaticVar($mid, $bookingDatetime, $covers){
		if(isset(RestaurantTableBookingModule::$mid) && ( RestaurantTableBookingModule::$mid == $mid) && 
			isset(RestaurantTableBookingModule::$bookingDatetime) && (RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime)){

		}else{
			RestaurantTableBookingModule::$mid = $mid;
			RestaurantTableBookingModule::$bookingDatetime = $bookingDatetime;
			global $restaurantTemplateService;
			$merchantTemplate = $restaurantTtemplateService->getTemplate($mid, $bookingDatetime);
			$session = $merchantTemplate->getSession($bookingDatetime);
			RestaurantTableBookingModule::$sessionStartDatetime = $session->getStartTime();
			RestaurantTableBookingModule::$sessionEndDatetime = $session->getStartTime() + 60 * $session->getSessionLength();

			global $redis;
			RestaurantTableBookingModule::$redis = $redis;
			RestaurantTableBookingModule::$covers = $covers;
		}
	}
	private static function getKey($cover){
		return ("restaurantTableCache|" . RestaurantTableBookingModule::$merchantID . '|' . date('Ymd', RestaurantTableBookingModule::$sessionDatetime) . '|' . $cover);
	}
	public static function isAvailable($mid, $bookingDatetime, $covers){
		$this->setStaticVar($mid, $bookingDatetime, $covers);
		$setting = getMerchantSettings($this->mid);

		$bookingLength = intval($setting['tableBookingLength']);
		$bookingInterval = intval($setting['tableBookingInterval']);
		$cache = RestaurantTableBookingModule::getCache($cover);
		$returnValue = true;
		$startDatetime = RestaurantTableBookingModule::$sessionStartDatetime;
		for($i = 0; ($i < $bookingLength) && ($returnValue == 1); $i = $i + $bookingInterval){
			$tempDate = mktime(date("H", $stateDatetime), date("i", $startDatetime) + $i, 0, date("m", $startDatetime), date("d", $startDatetime), date("Y", $startDatetime));
			//echo date("Ymd His\n", $tempDate);
			//echo date("Ymd His", $this->theDate);
			$thekey = date('Hi', $tempDate);
			$returnValue = (intval($cache[$thekey]) > 0);
			if($returnValue == false)
				break;
		}
		echo "returnValue : " . $returnValue?"TRUE":"FALSE";
		return $returnValue;
	}	
	public static function lockDB($tableID){
		$key = $this->getKey($tableID);
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
		if(!RestaurantTableBookingModule::$isLockedDB){
			if(DB::queryFirstField($sql) == 0){
				DB::insert('cache_restaurant_tables', array('thekey'=>$key, 'locked'=>0));
			}
		}
		return true;
	}
	public static function lock($mid, $bookingDatetime, $covers, $table, $bookingLength){
		$this->setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);

		$bookingLength = intval($mSetting['tableBookingLength']);
		$bookingInterval = intval($mSetting['tableBookingInterval']);
		$startDatetime = RestaurantTableBookingModule::$sessionStartDatetime;
		$cache = RestaurantTableBookingModule::getCache($cover);

		$key = RestaurantTableBookingModule::getKey($cover);
		
		if(RestaurantTableBookingModule::isLockReady($table->getTableId()) || !RestaurantTableBookingModule::lockDB($table->getTableId())){
			return -1;
		}else{
		}
		return true;
		
	}
	
	public static function unlock($mid, $bookingDatetime, $covers, $restaurantTable, $bookingLength){
		$this->setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);
		$key = RestaurantTableBookingModule::getKey($restaurantTable->getTableId());
		$sql = "SELECT count(*) FROM cache_restaurant_tables WHERE thekey='" . $key . "'";
		
		if(DB::queryFirstField($sql) == 0){
			RestaurantTableBookingModule::resetCache();
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
		DB::update('cache_restaurant_tables', array('locked'=>0), "locked=%d AND thekey=%s", 1, $key);
		if(DB::affectedRows() == 0){
			//ERROR!!!
			DB::rollback();
			return false;
		}else{
			DB::commit();
			return true;
		}
	}

	public static function commit($mid, $bookingDatetime, $covers, $restaurantTable, $bookingLength){

		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$mSetting = getMerchantSettings($mid);
		$bookingLength = intval($mSetting['tableBookingLength']);
		$bookingInterval = intval($mSetting['tableBookingInterval']);
		$bookingStart = RestaurantTableBookingModule::$sessionStartDatetime;
		$bookingEnd = RestaurantTableBookingModule::$sessionEndDatetime;
		$passed = true;
		$tempResult = array();
		//$this->redis->multi();
		$tempResult[] = $key;

		$sql = "SELECT * FROM restaurant_table WHERE table_id = $d" ;
		$tableID = $restaurantTable->getTableId();
		$rs = DB::queryFirstRow($sql, $tableID);
		$minCover = $rs['min_cover'];
		$maxCover = $rs['max_cover'];
		for($cover = intval($minCover); $cover <= intval($maxCover) ; $cover++){
			$key = RestaurantTableBookingModule::getKey($cover);
			$tempArr = RestaurantTableBookingModule::$redis->hgetall($key);
			for($i = 0; ($i < $bookingLength) && $passed; $i = $i + $bookingInterval){
				$tempDate = mktime(date("H", $bookingStart), date("i", $bookingStart) + $i, 0, date("m", $bookingStart), date("d", $bookingStart), date("Y", $bookingStart));
				
				$thekey = date('Hi', $tempDate);
				$passed = (intval($tempArr[$thekey]) > 0);
				//$this->redis->hmset($key, $thekey, intval($tempArr[$thekey]) - 1);
				//$tempResult[$thekey] = intval($tempArr[$thekey]) - 1;
			}	
			if($passed){
				RestaurantTableBookingModule::resetCache();
				//call_user_func_array(array($this->redis, "hmset"), $tempResult);
			}else{
				//$this->redis->discard();
			}
		}

		return $passed;
	}

	public static function resetCache($mid, $bookingDatetime, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$OSCache = getMerchantSettings($mid);

		$seatArr = array();
		
		$bookingSeatList = ($OSCache['tableSeatOptions']);
		$tmpArr = explode(",", $bookingSeatList); 
		/*
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
		$bookingStartDatetime = RestaurantTableBookingModule::$sessionStartDatetime;
		$bookingEndDatetime = RestaurantTableBookingModule::$sessionEndDatetime;
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
					for($j = $bookingStartDatetime; $j <= $bookingEndDatetime; $j = $j + ($bookingInterval * 60)){
						$timeKey = date('Hi', $j);
						if(!isset($tableArr[$cover]["" . $timeKey]))			
							$tableArr[$cover]["" . $timeKey] = 0;
						$tableArr[$cover]["" . $timeKey] ++;
					}
				}
			}
		}
		$sql = "SELECT *, (DATE_ADD(b.booking_ts, INTERVAL b.booking_length MINUTE)) AS booking_end_ts FROM booking_restaurant_table brt LEFT JOIN restaurant_table rt ON brt.restaurant_table_id = rt.restaurant_table_id LEFT JOIN booking b ON brt.booking_id = b.booking_id WHERE rt.merchant_id = %d AND (b.booking_ts BETWEEN %s AND %s)";
		$rs2 = DB::query($sql , $mid, date("Y-m-d H:i:s", $bookingStartDatetime), date("Y-m-d H:i:s", $bookingEndDatetime));
		
		echo sizeof($rs2);
		for($j = 0; $j < sizeof($rs2); $j++){
			for($k = intval($rs[$j]['min_cover']) ; $k <= intval($rs[$j]['max_cover']) ; $k++){
				if(in_array($k, $bookingCoverList)){
					for($t = convertDatetime($rs2[$j]['booking_ts']) ; $t < convertDatetime($rs2[$j]['booking_end_ts']) ; $t = $t + 60){
						$timeKey = date('Hi', $t);
						if(isset($tableArr[$k]["" . $timeKey]))
							$tableArr[$k]["" . $timeKey] -- ;
					}
				}
			}
		}
		
		foreach($bookingCoverList as $cover){
			$key = RestaurantTableBookingModule::getKey($cover);
			RestaurantTableBookingModule::$redis->del($key);
			RestaurantTableBookingModule::$redis->multi();
			for($i = $bookingStartDatetime ; $i <= $bookingEndDatetime ; $i = $i + ($bookingInterval * 60)){
				if(isset($tableArr[$cover])){
					$timeKey = date('Hi', $i);
					echo $tableArr[$cover]["" . $timeKey];
					RestaurantTableBookingModule::$redis->hmset($key, $timeKey, $tableArr[$cover]["" . $timeKey]);		
				}
			}
			$RestaurantTableBookingModule::$redis->exec();
		}
		

	}

	public static function getCache($mid, $bookingDatetime, $covers){
		RestaurantTableBookingModule::setStaticVar($mid, $bookingDatetime, $covers);
		$OSCache = getMerchantSettings($mid);
		$returnValue = array();

		$key = RestaurantTableBookingModule::getKey($seats);

		$tempArr = array();

		$tempArr = RestaurantTableBookingModule::$redis->hgetall($key);
		$generated = false;

		while(!$generated && sizeof($tempArr) == 0){
			$generated = true;
			RestaurantTableBookingModule::resetCache();
		}
		if(sizeof($tempArr) == 0){
			return 0;
		}else{
			return $tempArr;
		}
	}
}
