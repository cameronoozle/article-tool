<?php
namespace UI {
    class SEO extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }

        public function clients(){
            if (!isset($this->parameters['month'])) $this->parameters['month'] = date("Y-m-01 00:00:00");
            $api = new \API\All\Clients(array("department_id"=>3,"month"=>$this->parameters['month']));
            $model = $api->search();
            $data = $model->data;
            if ($model->status == 'success')
                include('templates/seo/clients.php');
            else
                include('templates/error.php');
        }
        public function permissions(){
            $perm = new \Permission(3,"Content");
            if ($perm->has_permission()){
                $api = new \API\Content\Users(array("department_id"=>3));
                $model = $api->search();
                $data = $model->data;
                if ($model->status == 'success')
                    include('templates/permissions.php');
                else
                    include('templates/error.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
        public function keywords(){
            $perm = new \Permission(2,"SEO");
            if ($perm->has_permission()){
                include('templates/seo/keywords.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }

        public function checklist(){
            $perm = new \Permission(1,"SEO");
            if ($perm->has_permission()){
                include('templates/seo/checklist.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
        public function checklist_admin(){
            $perm = new \Permission(2,"SEO");
            if ($perm->has_permission()){
                include('templates/seo/checklist_admin.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
    }
}
?>