<?php

class Page {
	public $url;
	public $auth; // only accessible after login
	
	public function __construct($pageConfig) {
		$this->url = $pageConfig[0];
		$this->auth = $pageConfig[1];
	}
};

class SitemapService {

	private $pages;

	public function __construct() {
		$this->pages = array();
		foreach (unserialize(SITEMAP__PAGES) as $pageConfig) {
			$page = new Page($pageConfig);
			$this->pages[$page->url] = $page;
		}
	}
		
	public function getPage($url) {
		if (isset($this->pages[$url])) {
			return $this->pages[$url];
		}
		return null;
	}
	
	public function redirect($location) {
		header('Location: '.$location);
		exit;
	}
	
};

?>