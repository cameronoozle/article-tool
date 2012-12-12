<?php
namespace UI {
    class PPC extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }

        public function permissions(){
            $perm = new \Permission(3,"Content");
            if ($perm->has_permission()){
                $api = new \API\Content\Users(array("department_id"=>1));
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
    }
}
?>