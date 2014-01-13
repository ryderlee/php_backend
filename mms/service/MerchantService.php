<?php

class MerchantService {

	function login($merchantId, $password) {
		$_SESSION['merchantId'] = $merchantId;
		return true;
	}
	
	function tokenLogin($token) {
		$info = resolveToken($token);
		$_SESSION['merchantId'] = $info['merchantId'];
		return true;
	}
	
	function isLogin() {
		return isset($_SESSION['merchantId']);
	}
	
	function resolveToken($token) {
		return "";
	}
	
	function logout() {
		session_destroy();
	}
	
	function getMerchantId() {
		return $_SESSION['merchantId'];
	}
	
};

?>