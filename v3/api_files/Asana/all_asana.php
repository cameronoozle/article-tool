<?php if (!isset($_SESSION)) session_start();
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
?>