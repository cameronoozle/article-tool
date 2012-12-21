<?php
$arr = array("page","content","ppc","seo","web_development","users","all");
foreach ($arr as $file){
    include($file."_class.php");
}
?>