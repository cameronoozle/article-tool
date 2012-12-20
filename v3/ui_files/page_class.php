<?php
namespace UI {
    class Page {
        protected $parameters;
        protected $status_options;
        protected $projects;
        protected $content_networks;
        public function __construct($parameters){
            $this->projects = array();
            $this->content_networks = array();
            $this->parameters = $parameters;
        }
        protected function status_options($target){
            ob_start();
            $api = new \API\Content\Articles(new \stdClass());
            $model = $api->search_statuses();
            ?><option></option><?php
            foreach ($model->data as $status){
                include('templates/pages/status_option.php');
            }
            return ob_get_clean();
        }
        protected function submitted(){
            return ((isset($this->parameters['submit'])) && ($this->parameters['submit'] == 'submit'));
        }
        
        public function header($title,$addscript = ""){
            ob_start();
            include('templates/pages/header.php');
            return ob_get_clean();
        }
        public function navbar(){
            $data = $_SESSION['oozledash']->departments;
            ob_start();
            include('templates/pages/navbar.php');
            return ob_get_clean();
        }
        public function footer(){
            ob_start();
            include('templates/pages/footer.php');
            return ob_get_clean();
        }
        public function logo(){
            ob_start();
            include('templates/pages/logo.php');
            return ob_get_clean();
        }
        public function month_options($target = null){
            if ($target == null)
                $target = date("F");
            $options = "";
            ob_start();
            foreach (array("January","February","March","April","May","June","July","August","September","October","November","December") as $integer=>$month){
                include('templates/pages/month_option.php');
            }
            return ob_get_clean();
        }
        public function year_options($target = null){
            $options = "";
            if ($target == null)
                $target = date("Y");
            ob_start();
            for ($year = 2000; $year < 2014; $year++){
                include('templates/pages/year_option.php');
            }
            return ob_get_clean();
        }
        public function client_options($dept,$target = 0,$month){
            ob_start();
            $api = new \API\All\Clients(array("department"=>$dept,"month"=>$month));
            $model = $api->search();
            if ($model->status == "success"){
                foreach ($model->data as $client){
                    include('templates/pages/client_option.php');
                }
            } else {
                echo "Error";
            }
            return ob_get_clean();
        }
        public function content_network_options($target = 0){
            ob_start();
            echo "<option></option>";
            if (count($this->content_networks) == 0){
                $api = new \API\Content\Articles(array());
                $model = $api->search_content_networks();
                if ($model->status == 'success'){
                    $this->content_networks = $model->data;
                    foreach ($model->data as $network){
                        include('templates/pages/content_network_option.php');
                    }
                }
            } else {
                foreach ($this->content_networks as $network){
                    include('templates/pages/content_network_option.php');
                }
            }
            return ob_get_clean();
        }
        public function keyword_pseudo_options($client_id = 105){
            ob_start();
            $api = new \API\SEO\Keywords(array("client_id"=>$client_id));
            $model = $api->search();
            $data = $model->data;
            echo "<ul>";
            foreach ($data->rows as $keyword){
                ?><li class='filled'><input type='hidden' name='keyword_id' value='<?= $keyword->keyword_id; ?>'/>&nbsp;<?= $keyword->keyword; ?></li><?php
            }
            echo "<li class='new'><input type='text' name='new_keyword' size='2'/> <span class='plus'>+</span> </li></ul>";

            return ob_get_clean();
        }
        
        public function project_options($target = 0,$dept_id){
            ob_start();
            echo "<option></option>";
            if (!isset($this->projects[$dept_id])){
                $api = new \API\All\Projects(array("department_id"=>$dept_id));
                $model = $api->search();
                $data = $model->data;
                if ($model->status == "success"){
                    $this->projects[$dept_id] = $data;
                    foreach ($model->data as $project){
                        include('templates/pages/project_option.php');
                    }
                }
            } else {
                foreach ($this->projects[$dept_id] as $project){
                    include('templates/pages/project_option.php');
                }
            }
            return ob_get_clean();
        }
        public function team_member_options($target = 0,$dept_id){
            ob_start();
            echo "<option></option>";
            if (!isset($this->team_members[$dept_id])){
                $api = new \API\All\Team_members(array("department_id"=>$dept_id));
                $model = $api->search();
                $data = $model->data;
                if ($model->status == "success"){
                    foreach ($data as $team_member){
                        include('templates/pages/team_member_option.php');
                    }
                }
            } else {
                foreach ($this->team_members[$dept_id] as $team_member){
                    include('templates/pages/team_member_option.php');
                }
            }
            return ob_get_clean();
        }
        
        protected function permissions_callback($dept_id,$dept_str){
            $perm = new \Permission(3,$dept_str);
            if ($perm->has_permission()){
                $api = new \API\All\Users(array("department_id"=>$dept_id));
                $pg_model = $api->search_pay_grades();
                $model = $api->search();
                $data = (object) array();
                $data->users = $model->data;
                $data->pay_grades = $pg_model->data;
                if ($model->status == 'success')
                    include('templates/pages/permissions.php');
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