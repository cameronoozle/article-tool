<?php
class Asana_API extends Requester {
	private $api_key;
	private $url;
	
	function __construct($api_key){
		parent::__construct();
		$this->api_key = $api_key;
		curl_setopt($this->ch,CURLOPT_USERPWD,$this->api_key.":");
		$this->url = "https://app.asana.com/api/1.0";
	}
	
	function as_post($method,$request_body){
		return $this->post($this->url.$method,array("Content-type: text/json"),$request_body);
	}
	
	function as_get($method,$params = array()){
		$query_str = http_build_query($params);
		return $this->get($this->url.$method."?".$query_str);
	}
	
	function as_put($method,$params = array()){
		$params = json_encode($params);
		return $this->put($this->url.$method,array("Content-type: text/json"),$params);
	}
}
?>