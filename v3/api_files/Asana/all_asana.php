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
print_r($asana->getWorkspace(314316658137)->getTask(2848496446411));
//->getTask(2360067917651)
?></pre>