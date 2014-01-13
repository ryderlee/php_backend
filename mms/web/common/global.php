<?php

session_save_path('/tmp');
session_start();

$project_root = dirname(__FILE__) . '/../../';

// require configs
require_once $project_root . 'configs/configs.php';
require_once $project_root . 'configs/constants.php';
require_once $project_root . 'configs/sitemap.php';

// require services
require_once $project_root . 'service/SitemapService.php';
require_once $project_root . 'service/HttpService.php';
require_once $project_root . 'service/MerchantService.php';
require_once $project_root . 'service/BookingService.php';

// instantiate services
$sitemapService = new SitemapService();
$merchantService = new MerchantService();


// check login session
$pathParts = pathinfo($_SERVER['REQUEST_URI']);
if (!$merchantService->isLogin() && (is_null($sitemapService->getPage($pathParts['basename'])) || $sitemapService->getPage($pathParts['basename'])->auth)) {
	$sitemapService->redirect('index.php');
}

?>