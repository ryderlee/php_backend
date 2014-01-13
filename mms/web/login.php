<?php

require_once dirname(__FILE__) . '/common/global.php';

if ($merchantService->isLogin()) {
	$sitemapService->redirect('bookings.php');
} else if (isset($_POST['merchantId']) && isset($_POST['password'])) {
	$merchantService->login($_POST['merchantId'], $_POST['password']);
	$sitemapService->redirect('bookings.php');
}

?>
<!doctype html>
<html ng-app="mmsApp">
  <head>
  	<title>Merchant Management System - Login</title>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.7/angular-resource.js"></script>
    <script src="js/app.js"></script>
    <script src="js/controllers.js"></script>
    <script src="js/services.js"></script>
    <script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css">
  </head>
  <body>
    <div class="main-wrapper">
      <h1>LOGIN</h1>
      <hr>
      <form method="POST" action="login.php">
	      <table>
	      	<tr><td>Merchant ID: </td><td><input name="merchantId" /></td></tr>
	      	<tr><td>Password: </td><td><input name="password" type="password" /></td></tr>
	      	<tr><td colspan="2"><input type="submit" value="login" style="width:100%" /></td></tr>
	      </table>
      </form>
  	</div>
  </body>
</html>
