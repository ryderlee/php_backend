<?php

class MerchantTemplate {
	private $merchantId, $templateId, $templateName, $openingSessions, $templateDate;
	public function __construct($merchantId, $templateId, $templateName, $templateDate) {
		$this->merchantId = $merchantId;
		$this->templateId = $templateId;
		$this->templateName = $templateName;
		$this->templateDate = $templateDate;
		$this->openingSessions = array();
	}
	public function putOpeningSession($openingSession) {
		array_push($this->openingSessions, $openingSession);
	}
	
	public function getMerchantId() {
		return $this->merchantId;
	}
	public function getTemplateId() {
		return $this->templateId;
	}
	public function getTemplateName() {
		return $this->templateName;
	}
	public function getOpeningSessions() {
		return $this->openingSessions;
	}
	public function getTemplateDate() {
		return $this->templateDate;
	}
	public function getOpeningSession($timeStr) {
		foreach ($this->getOpeningSessions() as $openingSession) {
			$start = strtotime($openingSession->getStartTime());
			$end = $start + 60 * $openingSession->getSessionLength();
			$target = strtotime($timeStr);
			if ($target >= $start && $target <= $end) {
				return $openingSession;
				break;
			}
		}
	}
}

class OpeningSession {
	private $merchantId, $openingSessionId, $openingSessionName, $startTime, $sessionLength, $settingsJson;
	public function __construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $sessionLength, $settings) {
		$this->merchantId = $merchantId;
		$this->openingSessionId = $openingSessionId;
		$this->openingSessionName = $openingSessionName;
		$this->startTime = $startTime;
		$this->sessionLength = $sessionLength;
		$this->settingsJson = json_decode($settings, true);
	}
	public function getMerchantId() {
		return $this->merchantId;
	}
	public function getOpeningSessionId() {
		return $this->openingSessionId;
	}
	public function getOpeningSessionName() {
		return $this->openingSessionName;
	}
	public function getStartTime() {
		return $this->startTime;
	}
	public function getSessionLength() {
		return $this->sessionLength;
	}
	public function getSettingsJson() {
		return $this->settingsJson;
	}
}

class RestaurantOpeningSession extends OpeningSession {
	private $mealDuration, $floorPlanId, $restaurantTables;
	public function __construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $sessionLength, $settings) {
		parent::__construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $sessionLength, $settings);
		$settingsJson = $this->getSettingsJson();
		if (!empty($settingsJson['floorPlanId'])) {
			$this->floorPlanId = $settingsJson['floorPlanId'];
		}
		if (!empty($settingsJson['mealDuration'])) {
			$this->mealDuration = $settingsJson['mealDuration'];
		}
	}
	public function getMealDuration() {
		return $this->mealDuration;
	}
	public function getRestaurantTables() {
		$result = DB::query('SELECT * FROM restaurant_table WHERE floor_plan_id = %d', $this->getFloorPlanId());
		$this->restaurantTables = array();
		foreach ($result as $restaurantTable) {
			array_push($this->restaurantTables, new RestaurantTable($restaurantTable['merchant_id'], $restaurantTable['restaurant_table_id'], $restaurantTable['restaurant_table_name'], $restaurantTable['actual_cover'], $restaurantTable['min_cover'], $restaurantTable['max_cover']));
		}
		return $this->restaurantTables;
	}
	public function getFloorPlanId() {
		return $this->floorPlanId;
	}
}

class RestaurantTable {
	private $merchantId, $restaurantTableId, $restaurantTableName, $actualCover, $minCover, $maxCover;
	public function __construct($merchantId, $restaurantTableId, $restaurantTableName, $actualCover, $minCover, $maxCover) {
		$this->merchantId = $merchantId;
		$this->restaurantTableId = $restaurantTableId;
		$this->restaurantTableName = $restaurantTableName;
		$this->actualCover = $actualCover;
		$this->minCover = $minCover;
		$this->maxCover = $maxCover;
	}
	public function getMerchantId() {
		return $this->merchantId;
	}
	public function getTableId() {
		return $this->restaurantTableId;
	}
	public function getTableName() {
		return $this->restaurantTableName;
	}
	public function getActualCover() {
		return $this->actualCover;
	}
	public function getMinCover() {
		return $this->minCover;
	}
	public function getMaxCover() {
		return $this->maxCover;
	}
}

interface MerchantTemplateServiceInterface {
	public function getTemplate($merchantId, $date);
	public function getTemplateList($merchantId);
	public function getOpeningSessionList($merchantId);
	public function getAssignmentList($merchantId);
}

class RestaurantTemplateService implements MerchantTemplateServiceInterface {
	public function getTemplate($merchantId, $datetime) {
		$datetimeParts = explode(' ', $datetime);
		$dateStr = $datetimeParts[0];
		$timeStr = $datetimeParts[1];
		$result = DB::query('SELECT * FROM merchant_template mt JOIN merchant_template_session mts ON mt.template_id = mts.template_id JOIN merchant_opening_session mos ON mts.opening_session_id = mos.opening_session_id WHERE mt.template_id = (SELECT template_id FROM merchant_template_assignments WHERE merchant_id = %d AND (recurrence = DAYOFWEEK(%s) OR assign_date = %s) ORDER BY assign_date = %s DESC LIMIT 1)', $merchantId, $dateStr, $dateStr, $dateStr);
		$merchantTemplate = null;
		foreach ($result as $row) {
			if (empty($merchantTemplate)) {
				$merchantTemplate = new MerchantTemplate($row['merchant_id'], $row['template_id'], $row['template_name'], $dateStr);
			}
			$openingSession = new RestaurantOpeningSession($row['merchant_id'], $row['opening_session_id'], $row['opening_session_name'], $row['start_time'], $row['session_length'], $row['settings']);
			$merchantTemplate->putOpeningSession($openingSession);
		}
		return $merchantTemplate;
	}
	public function getTemplateList($merchantId) {
	}
	public function getOpeningSessionList($merchantId) {
	}
	public function getAssignmentList($merchantId) {
	}
}
