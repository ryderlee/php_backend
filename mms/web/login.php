<?php

require_once dirname(__FILE__) . '/common/global.php';

if ($merchantService->isLogin()) {
	$sitemapService->redirect('calendar.php');
} else if (isset($_POST['merchantId']) && isset($_POST['password'])) {
	$merchantService->login($_POST['merchantId'], $_POST['password']);
	$sitemapService->redirect('calendar.php');
}

?>
<!doctype html>
<html ng-app="mmsApp">
  <head>
  	<title>Merchant Management System - Login</title>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-resource.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-animate.min.js"></script>
    <script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="http://code.jquery.com/ui/1.9.2/jquery-ui.js"></script>
    <script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/controllers.js"></script>
    <script src="js/services.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/animations.js"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/smoothness/jquery-ui.css">
    <meta charset="UTF-8">
  </head>
  <body>
    <div class="main-wrapper">
      <h1>LOGIN</h1>
      <hr>
      <form method="POST" name="loginForm" ng-submit="submit()" ng-controller="LoginCtrl" novalidate>
	      <table>
	      	<tr>
	      		<td>Merchant ID: </td><td><input name="merchantId" ng-model="merchantId" name="merchantId" pattern="\d*" ng-pattern="/^\d{10}$/" ng-trim="false" required autofocus /></td>
	      		<td><span class="error" ng-show="loginForm.merchantId.$dirty && loginForm.merchantId.$error.required">Required</span>
		      		<span class="error" ng-show="loginForm.merchantId.$dirty && loginForm.merchantId.$error.pattern">Should be a 10-digit ID</span></td>
	      	</tr>
	      	<tr>
	      		<td>Password: </td><td><input name="password" type="password" ng-model="password" name="password" required /></td>
	      		<td><span class="error" ng-show="loginForm.password.$dirty && loginForm.password.$error.required">Required</span></td>
      		</tr>
	      	<tr><td colspan="2"><input type="submit" value="login" style="width:100%" /></td></tr>
	      </table>
      </form>
  	</div>
  </body>
</html>
