<?php
function parse_request(){
	$parameters = array();
	if (isset($_SERVER['QUERY_STRING'])) {
		parse_str($_SERVER['QUERY_STRING'], $parameters);
	}
	$body = file_get_contents("php://input");
    $content_type = false;
	if(isset($_SERVER['CONTENT_TYPE'])){
		$content_type = $_SERVER['CONTENT_TYPE'];
	}
	switch($content_type) {
		case "application/json; charset=UTF-8":
		case "application/json":
			$body_params = json_decode($body);
			if($body_params) {
				foreach($body_params as $param_name => $param_value) {
					$parameters[$param_name] = $param_value;
				}
			}
			break;
		case "application/x-www-form-urlencoded":
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {
				$parameters[$field] = $value;
			}
			break;
		default:
			// we could parse other supported formats here
			break;
	}
	return $parameters;
}
?>