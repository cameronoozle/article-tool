<?php if (!isset($_SESSION)) session_start(); ?>
<pre><?php
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
print_r(get_declared_classes());
$asana = new Asana(true);

print_r($asana->getWorkspace(626921128718,true));
//->getTask(2360067917651)
?></pre>