<?php
class Requester {
	protected $ch;
	function __construct(){
		$this->ch = curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
		$ckfile = tempnam("/tmp","CURLCOOKIE");
		curl_setopt($this->ch,CURLOPT_COOKIEJAR,$ckfile);
		curl_setopt($this->ch,CURLOPT_SSL_VERIFYPEER,0);
	}	

	
	private function build_and_execute($ch,$url,$headers = array()){
		if (!empty($headers))
			curl_setopt($this->ch,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->ch,CURLOPT_URL,$url);
		$contents = curl_exec($ch);
		$return_array = array();
		$return_array['info'] = curl_getinfo($ch);
		$return_array['contents'] = $contents;
		return $return_array;		
	}
	
	public function post($url,$headers = array(),$postfields = ""){
		curl_setopt($this->ch,CURLOPT_POST,1);
		if (!empty($postfields))
			curl_setopt($this->ch,CURLOPT_POSTFIELDS,$postfields);
		return $this->build_and_execute($this->ch,$url,$headers);
	}
	
	public function get($url,$headers = array()){
		curl_setopt($this->ch,CURLOPT_POST,0);
		return $this->build_and_execute($this->ch,$url,$headers);
	}
	
	public function put($url,$headers = array(),$postfields = ""){
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
		if (!empty($postfields))
			curl_setopt($this->ch,CURLOPT_POSTFIELDS,$postfields);
		return $this->build_and_execute($this->ch,$url,$headers);
	}
}

?>