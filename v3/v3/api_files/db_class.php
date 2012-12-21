<?php
interface IObservable {
	public function addListener($listener);
	public function fireEvent($event);
}

class DB implements IObservable {
	protected $server;
	protected $user;
	protected $pw;
	protected $db;
	protected $link;
	private $listeners;
	public $errors;

	public function addListener($listener){
		array_push($this->listeners,$listener);
	}
	
	public function fireEvent($event){
		foreach ($this->listeners as $listener){
			if (method_exists($listener,$event)){
				call_user_func(array($listener,$event));
			}
		}
	}
	
	public function __construct($server,$user,$pw,$db){
		$this->server = $server;
		$this->user = $user;
		$this->pw = $pw;
		$this->db = $db;
		$this->listeners = array();
		$this->errors = array();
	}
	
	public function link(){
		if (!isset($this->link))
			$this->link = DatabaseConnection::get()->link();//mysqli_connect($this->server,$this->user,$this->pw,$this->db);
		return $this->link;
	}
	
	public function query($query){
		$this->link();
		$result = mysqli_query($this->link,$query);
		$jerome = mysqli_error($this->link);
		if ($jerome == ""){
			return $this->info($result);
		} else if (count($this->listeners) > 0){
			array_push($this->errors,mysqli_error($this->link));
			$this->fireEvent("onSQLError");
			return false;
		} else {
			die($jerome);
		}
	}
	
	public function found_rows(){
		$result = mysqli_query($this->link(),"SELECT FOUND_ROWS() as fr");
		$a = mysqli_fetch_assoc($result);
		return $a['fr'];
	}
	
	private function info($result){
		$array = array();
		if (!is_bool($result))
			$array['result_object'] = $result;
		$array['info'] = mysqli_info($this->link);
		$array['insert_id'] = mysqli_insert_id($this->link);
		if (!is_bool($result)){
			$array['rows'] = array();
			while ($row = mysqli_fetch_assoc($result)){
				array_push($array['rows'],$row);
			}
		}
		$array['affected_rows'] = mysqli_affected_rows($this->link);
		return $array;
	}
	
	public function esc($string){
		$this->link();
		return strip_tags(trim(mysqli_real_escape_string($this->link,$string)));
	}
	
	public function close(){
		if (isset($this->link))
			mysqli_close($this->link);
	}
}
?>