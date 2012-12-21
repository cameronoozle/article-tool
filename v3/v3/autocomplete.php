<?php
if (!isset($_SESSION)) session_start();
header("Content-type: text/plain");
include('api_files/all_api.php');
include('parse_request.php');
$parameters = parse_request();

function callback($query,$db,$field){
	$d = $db->query($query);
	$output = array();
	foreach ($d['rows'] as $row){
		if (!isset($output[$row['client_id']])){
			$output[$row['client_id']] = array();
		}
		array_push($output[$row['client_id']],$row[$field]);
	}
	return $output;
}

foreach (array("mh","search_term") as $item){
	if (!isset($parameters[$item])){
		echo json_encode(\Endpoint::error(array("Missing required parameter[s]: ".$item)));
		exit;
	}
}
$method = trim($parameters['mh']);
if (preg_match("/^target_urls|post_urls|keywords$/",$method)){
	if (!isset($_SESSION['autocomplete'])) $_SESSION['autocomplete'] = array();
	if (!isset($_SESSION[$method])){
		$db = new DB(SERVER,USER,PW,DB);
		switch ($method){
			case "target_urls":
				$query = "SELECT DISTINCT target_url,client_id FROM articles ORDER BY client_id,target_url";
				$_SESSION['autocomplete']['target_urls'] = callback($query,$db,"target_url");
				break;
			case "post_urls":
				$query = "SELECT DISTINCT post_url,client_id FROM articles ORDER BY client_id,post_url";
				$_SESSION['autocomplete']['post_urls'] = callback($query,$db,"post_url");
				break;
			case "keywords":
				$query = "SELECT DISTINCT keyword,client_id FROM keywords INNER JOIN articles USING (keyword_id) ORDER BY client_id,keyword";
				$_SESSION['autocomplete']['keywords'] = callback($query,$db,"keyword");
				break;
		}
	}
	if (isset($parameters['client_id'])){
		$output = array();
		$client_id = $parameters['client_id'];
		$i=0;
		if (!isset($_SESSION['autocomplete'][$method][$client_id])){
			echo json_encode(\Endpoint::error(array("No such client.")));
			exit;
		}
		foreach ($_SESSION['autocomplete'][$method][$client_id] as $item){
			if (preg_match("/^".preg_quote($parameters['search_term'])."/i",$item)){
				array_push($output,$item);
			}
			$i++;
			if ($i>10) break;
		}
	}
	echo json_encode(\Endpoint::success($output));
	exit;
} else {
	echo json_encode(\Endpoint::error(array("Invalid value for method.")));
	exit;
}

?>