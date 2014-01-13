<?php

require_once dirname(__FILE__) . '/common/global.php';

$merchantService->logout();
$sitemapService->redirect('index.php');

?>