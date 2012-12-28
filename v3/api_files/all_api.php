<?php
$arr = array(
    "permission_class",
    "requester_class",
    "array_manager_class",
    "asana_class",
    "constants",
    "databaseconnection_class",
    "db_class",
    "endpoint_class",
    "handy_class",
    "required_parameters_class",
    "sugar_class",
    "types_class",
    "All/departments_class",
    "All/modules_class",
    "All/tasks_class",
    "All/team_members_class",
    "All/users_class",
    "All/clients_class",
    "All/projects_class",
    "All/system_class",
    "Content/articles_class",
    "SEO/checklists_class",
    "SEO/keywords_class"
);
foreach ($arr as $file){
    include($_SERVER['DOCUMENT_ROOT'].($_SERVER['DOCUMENT_ROOT'] == '/home/oozlemed/public_html' ? '/seo' : '/article-tool')."/v3/api_files/".$file.".php");
}
?>