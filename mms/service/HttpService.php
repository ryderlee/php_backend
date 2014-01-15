<?php

class HttpService {

	static public function get($resourceUri) {
		$ch = curl_init(CONFIG__API_URL . $resourceUri);
		
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	static public function post($resourceUri, $paraMap) {
		$ch = curl_init(CONFIG__API_URL . $resourceUri);
		
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paraMap));
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	static public function put($resourceUri, $paraMap) {
		$ch = curl_init(CONFIG__API_URL . $resourceUri);
		
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paraMap));
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
}

?>