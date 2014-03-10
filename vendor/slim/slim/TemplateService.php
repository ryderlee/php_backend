<?php

class MerchantTemplate {
	private $merchantId, $templateId, $templateName, $openingSessions;
	public function __construct($merchantId, $templateId, $templateName) {
		$this->merchantId = $merchantId;
		$this->templateId = $templateId;
		$this->templateName = $templateName;
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
}

class OpeningSession {
	private $merchantId, $openingSessionId, $openingSessionName, $startTime, $endTime, $settingsJson;
	public function __construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $endTime, $settings) {
		$this->merchantId = $merchantId;
		$this->openingSessionId = $openingSessionId;
		$this->openingSessionName = $openingSessionName;
		$this->startTime = $startTime;
		$this->endTime = $endTime;
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
	public function getEndTime() {
		return $this->endTime;
	}
	public function getSettingsJson() {
		return $this->settingsJson;
	}
}

class RestaurantOpeningSession extends OpeningSession {
	private $tableSettings, $mealDuration;
	public function __construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $endTime, $settings) {
		parent::__construct($merchantId, $openingSessionId, $openingSessionName, $startTime, $endTime, $settings);
		$this->tableSettings = array();
		$settingsJson = $this->getSettingsJson();
		if (!empty($settingsJson['tables'])) {
			$this->tableSettings = $settingsJson['tables'];
		}
		if (!empty($settingsJson['mealDuration'])) {
			$this->mealDuration = $settingsJson['mealDuration'];
		}
	}
	public function getTableSettings() {
		return $this->tableSettings;
	}
	public function getMealDuration() {
		return $this->mealDuration;
	}
}

interface TemplateServiceInterface {
	public function getTemplate($merchantId, $date);
	public function getTemplateList($merchantId);
	public function getAssignmentList($merchantId);
}

class RestaurantTemplateService implements TemplateServiceInterface {
	public function getTemplate($merchantId, $date) {
		$result = DB::query('SELECT * FROM merchant_template mt JOIN merchant_template_session mts ON mt.template_id = mts.template_id JOIN merchant_opening_session mos ON mts.opening_session_id = mos.opening_session_id WHERE mt.template_id = (SELECT template_id FROM merchant_template_assignments WHERE merchant_id = %d AND (recurrence = DAYOFWEEK(%s) OR assign_date = %s) ORDER BY assign_date = %s DESC LIMIT 1)', $merchantId, $date, $date, $date);
		$merchantTemplate = null;
		foreach ($result as $row) {
			if (empty($merchantTemplate)) {
				$merchantTemplate = new MerchantTemplate($row['merchant_id'], $row['template_id'], $row['template_name']);
			}
			$openingSession = new RestaurantOpeningSession($row['merchant_id'], $row['opening_session_id'], $row['opening_session_name'], $row['start_time'], $row['end_time'], $row['settings']);
			$merchantTemplate->putOpeningSession($openingSession);
		}
		return $merchantTemplate;
	}
	public function getTemplateList($merchantId) {
		$result = DB::query('SELECT * FROM merchant_template WHERE merchant_id = %d', $merchantId);
	}
	public function getAssignmentList($merchantId) {
		
	}
}
