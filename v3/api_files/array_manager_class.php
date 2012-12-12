<?php
class Array_Manager {
	public $column_order;
	
	public function __construct($column_order = null){
		if ($column_order !== null)
			$this->column_order = $column_order;
	}
	
	public static function array_slice_assoc ($array, $key, $length, $preserve_keys = true){
	   $offset = array_search($key, array_keys($array));
	   if (is_string($length))
	      $length = array_search($length, array_keys($array)) - $offset;	
	   return array_slice($array, $offset, $length, $preserve_keys);
	}

	public static function multi_in_array($key,$needle,$haystack){
		$output_array = array();
		for ($i=0;$i<count($haystack);$i++){
			if (!isset($haystack[$i])) continue;
			if ($haystack[$i][$key] == $needle){
				array_push($output_array,$haystack[$i]);
			}
		}
		return $output_array;
	}
	
	public static function merge_fields($array,$fields_to_merge,$new_field_name){
		$merged = array();
		foreach ($fields_to_merge as $name=>$value){
			$merged[$value] = $array[$value];
			unset($array[$value]);
		}
		$array[$new_field_name] = $merged;
		return $array;
	}
	
	private static function merge_arrays($arraystomerge,$merge_on){
		$to_output = array();
		//Copy the first array into the output.
		$to_output = $arraystomerge[0];
		foreach ($merge_on as $name=>$val){
			if (isset($to_output[$val])){
				$storage = $to_output[$val];
				$to_output[$val] = array();
				array_push($to_output[$val],$storage);
			}
		}
		for ($i=1;$i<count($arraystomerge);$i++){
			//Loop through each of the provided arrays checking for like keys with different values.
			foreach ($arraystomerge[$i] as $key=>$val){
				//If the output array's value for a certain key doesn't match the given input array's value for a certain key,
				//turn the output array's value into an array and push the two values onto that value array.
				if ($to_output[$key] !== $val){
					if ($i==1){
						array_push($to_output[$key],$val);
					} else {
						array_push($to_output[$key],$val);
					}
				}
			}
		}
		return $to_output;
	}
	
	public static function multidimensionalize($array,$key_field,$merge_on = array()){
		$to_output = array();
		//We need to establish the end of the array beforehand, because we'll be deleting rows in the array as we go along.
		$deadend = count($array);
		foreach ($array as $entry){
			if (count(Array_Manager::multi_in_array($key_field,$entry[$key_field],$to_output)) == 0){
				$duplicate_arrays = Array_Manager::multi_in_array($key_field,$entry[$key_field],$array);
				array_push($to_output,Array_Manager::merge_arrays($duplicate_arrays,$merge_on));
			}
		}
		return $to_output;
	}
	
	private function sorter($a,$b){
		return (array_search($a,$this->column_order) < array_search($b,$this->column_order) ? -1 : 1);
	}
	
	public static function order_columns($data,$column_order,$additional_rows){
		$this->column_order = $column_order;
		$to_output = array();
		foreach ($data as $entry){
			foreach ($additional_rows as $name=>$value){
				if (!isset($entry[$name]))
					$entry[$name] = $value;
			}
			uksort($entry,array(new Array_Manager($column_order),"sorter"));
			array_push($to_output,$entry);
		}
		return $to_output;
	}
	public static function is_multidimensional($array){
		$converted = \Handy::objectify($array);
		return ((is_array($converted))&&(!empty($converted)));
/*		if ((!is_array($array))&&(!is_object($array)))
			return false;
		$array = \Handy::objectify($array);
		foreach ($array as $entry){
			if ((is_array($entry))||(is_object($array))){
				return true;
			}
		}
		return false;*/
	}
}
?>