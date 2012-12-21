<?php
class Required_Parameters {
	var $one_many_all;
	var $all;
	
	public function __construct($one_many_all = array(),$all = array()){
		$this->one_many_all = $one_many_all;
		$this->all = $all;
	}
}
?>