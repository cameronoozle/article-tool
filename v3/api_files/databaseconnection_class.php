<?php
class DatabaseConnection {
	//Singleton pattern:
	//To allocate and initialize a DatabaseConnection object, use DatabaseConnection::get()->link()
	
	public static function get(){
		static $db = null;
		if ( $db == null )
			$db = new DatabaseConnection();
		return $db;
	}

	private $_handle = null;

	private function __construct(){
		$this->link = mysqli_connect(SERVER,USER,PW,DB);
	}
  
	public function link(){
		return $this->link;
	}
}
?>