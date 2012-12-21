<?php
class Sugar_API extends Requester {
	private $session_id;
	private $username;
	private $pw;
	public $url;
	private $column_order;

	function __construct($username,$pw){
		parent::__construct();
		$this->url = "http://www.oozlemedia.com/sugar/service/v2/rest.php";
		$this->username = $username;
		$this->pw = $pw;
	}
	
	public function login(){
		$params = json_encode(array('user_auth'=>array('user_name'=>$this->username,'password'=>md5($this->pw))));
		$postfields = 'method=login&input_type=JSON&response_type=JSON&rest_data=' . $params;
		$info = $this->post($this->url,array(),$postfields);
		$data = json_decode($info['contents']);
		return $data->id;
	}
	
	public function sug_post($method,$params){		
		$request['session'] = $this->login();
		foreach ($params as $name=>$val)
			$request[$name] = $val;
		$params = json_encode($request);
		$postfields = 'method='.$method.'&input_type=JSON&response_type=JSON&rest_data=' . $params;
		$info = $this->post($this->url,array(),$postfields);
		return $info;
	}	
}
?>