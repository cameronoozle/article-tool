<?php
class DatabaseConnection {
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