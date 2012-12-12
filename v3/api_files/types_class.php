<?php
abstract class Types {
	const Int = 0;
	const String = 1;
	const Float = 2;
	const URL = 3;
	const Datetime = 4;
	const Bool = 5;
	const Email = 6;
	
	public static function matches_type($value,$type){
		switch ($type){
			case 0:
				if (!is_numeric($value))
					return false;
				break;
			case 1:
				if (!is_string($value))
					return false;
				break;
			case 2:
				if (!is_numeric($value))
					return false;
				break;
			case 3:
				if(filter_var($value, FILTER_VALIDATE_URL) === FALSE)
					return false;
				break;
			case 4:
				if (!preg_match("/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/",$value))
					return false;
				break;
			case 5:
				if ((!is_numeric($value))||((intval($value) !== 0)&&(intval($value) !== 1)))
					return false;
				break;
			case 6:
				if (!preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/",trim($value)))
					return false;
				break;
			default:
				return false;
				break;
		}
	return true;
}
}

?>