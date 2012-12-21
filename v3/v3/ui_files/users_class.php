<?php
namespace UI {
    class Users extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }

        public function logout(){
            $api = new \API\All\Users($this->parameters);
            $model = $api->logout();
            $data = $model->data;
            include('templates/authenticate.php');
        }
        
        public function old_login(){
            if ($this->submitted()){
                $api = new \API\All\Users($this->parameters);
                $model = $api->old_login();
                $data = $model->data;
                if ($model->status == 'success'){
                    include('templates/intermediate_log.php');
                } else {
                    include('templates/error.php');
                }
            } else {
                include('templates/old_authenticate.php');
            }
        }
        
        public function login(){
            if ($this->submitted()){
                $api = new \API\All\Users($this->parameters);
                $model = $api->login();
                $data = $model->data;
                if ($model->status == 'success'){
                    include('templates/pages/home.php');
                } else {
                    include('templates/authenticate.php');
                }
            } else {
                $data = array();
                include('templates/authenticate.php');
            }
        }
        
        public function register(){
            if ($this->submitted()){
                $api = new \API\All\Users($this->parameters);
                $model = $api->register();
                $data = $model->data;
                if ($model->status == 'success'){
                    include('templates/register_success.php');
                } else {
                    include('templates/authenticate.php');
                }
            } else {
                $data = array();
                include('templates/authenticate.php');
            }
        }

        public function verify(){
            $api = new \API\All\Users($this->parameters);
            $model = $api->verify();
            $data = $model->data;
            include('templates/authenticate.php');
        }
        
        public function request_new_password(){
            if ($this->submitted()){
                $api = new \API\All\Users($this->parameters);
                $model = $api->request_new_password();
                $data = $model->data;
                if ($model->status == 'success'){
                    include('templates/request_password_success.php');
                } else {
                    include('templates/request_password_input.php');
                }
            } else {
                include('templates/request_password_input.php');
            }
        }
        public function create_new_password(){
            if ($this->submitted()){
                $api = new \API\All\Users($this->parameters);
                $model = $api->create_new_password();
                $data = $model->data;
                if ($model->status == 'success'){
                    include('templates/create_password_success.php');
                } else {
                    include('templates/create_password_input.php');
                }
            } else {
                include('templates/create_password_input.php');
            }
        }
    }
}
?>