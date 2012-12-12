<?php
if (!isset($_SESSION)) session_start();
header("Content-type: text/plain");
include('api_files/all_api.php');
include('parse_request.php');
$parameters = parse_request();

function error($str){
	echo json_encode(Endpoint::error(array($str,$_SERVER['CONTENT_TYPE'])));
	exit;
}

if (!isset($parameters['ns'])) error("You must specify an department");
if (!isset($parameters['cl'])) error("You must specify a module");

$cl = "API\\".$parameters['ns']."\\".$parameters['cl'];

if (!class_exists($cl)) error($cl." is not a module");
if (!isset($parameters['mh'])) error("You must specify an method");

$mh = $parameters['mh'];
unset($parameters['ns'],$parameters['cl'],$parameters['mh']);
$obj = new $cl(\Handy::objectToArray($parameters));

if (!method_exists($obj,$mh)) error($mh." is not a method in ".$cl);
if ((isset($parameters['format']))&&($parameters['format'] == 'print'))
	print_r($obj->$mh());
else
	echo json_encode($obj->$mh());
?>