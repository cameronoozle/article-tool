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
            $this->permissions_callback(3,"SEO");
        }
        public function keywords(){
            $perm = new \Permission(2,"SEO");
            if ($perm->has_permission()){
                $api = new \API\SEO\Keywords($this->parameters);
                $model = $api->search();
                $data = $model->data;
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