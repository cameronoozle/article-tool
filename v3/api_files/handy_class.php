<?php
class Handy {
	private $parameters;

	public function __construct($parameters = null){
		if ($parameters !== null)
			$this->parameters = $parameters;
	}

	public static function vals_are_keys($array){
		$output = array();
		foreach ($array as $key=>$val){
			$output[$val] = "";
		}
		return $output;
	}
	
	public static function str_encase($value){
		return (is_string($value) ? "'".$value."'" : $value);
	}
	
	public static function date_or_null($field,$parameters = null){
		if (($parameters === null)&&(isset($this->parameters))) $parameters = $this->parameters;
		return (!empty($parameters[$field]) ? "'".date("Y-m-d H:i:s")."'" : "null");
	}
	public static function implode_keys($glue,$pieces){
		$str = "";
		foreach ($pieces as $name=>$value){ $str .= $name.$glue; }
		$str = substr_replace($str,"",(strlen($glue)*-1),strlen($glue));
		return $str;
	}
	public static function objectify($array){
		return json_decode(json_encode($array));
	}
	public static function objectToArray($d) {
		if (is_object($d)) $d = get_object_vars($d);
		return (is_array($d) ? array_map(array(new Handy(),__FUNCTION__),$d) : $d);
	}
	public function val_or_null($field,$parameters = null){
		if (($parameters === null)&&(isset($this->parameters))) $parameters = $this->parameters;
		return (!empty($parameters[$field]) ? $parameters[$field] : "null");
	}
	public static function wrap_response($output){
		if ((!is_object($output))||(!isset($output->status))){
			$output = Handy::objectify(array("status"=>"success","data"=>$output));
		}
		return $output;
	}
	public static function recursive_empty($array){
		if (is_array($array)){
			foreach ($array as $entry){
				if (is_array($entry)){
					if (!Handy::recursive_empty($entry))
						return false;
				} else if (!empty($entry)){
					return false;
				}
			}
			return true;
		} else {
			return empty($array);
		}
	}
}
?>