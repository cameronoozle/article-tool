<pre><?php
if (!isset($_SESSION)) session_start();
//header("Content-type: text/plain");
include('../all_api.php');
$arr = array(
    "asanaobject",
    "asana",
    "workspace",
    "task",
    "project",
    "assignee"
);

foreach ($arr as $file)
    include($file."_class.php");

$asana = new Asana();
$workspace = $asana->getWorkspace(314316658137);

print_r($workspace->createTask("The World's Greatest",$workspace->getAssignee(273287852136),"Knowing you were not prepared, knowing you would likely die - Mommy was very, very bad.","2015-06-16"));
//->getTask(2360067917651)
?></pre>