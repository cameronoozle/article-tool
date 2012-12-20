<?php
if (!isset($_SESSION)) session_start();
include('api_files/all_api.php');
include('ui_files/all_ui.php');
include('parse_request.php');
$parameters = parse_request();

function error($str){
	echo json_encode(Endpoint::error(array($str)));
	exit;
}

if (!isset($parameters['cl'])) error("You must specify a module");

$cl = "UI\\".$parameters['cl'];

if (!class_exists($cl)) error($cl." is not a module");
if (!isset($parameters['mh'])) error("You must specify an method");

$mh = $parameters['mh'];
unset($parameters['cl'],$parameters['mh']);
if (($cl == "UI\Users")||(\API\All\Users::is_logged())){
	$obj = new $cl($parameters);
	if (!method_exists($obj,$mh)) error($mh." is not a method in ".$cl);
	$obj->$mh();
} else {
	$obj = new \UI\Users($parameters);
	$obj->login();
}
?>