<?php
namespace API\All {
    class Users extends \Endpoint {
        private $asana;
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function logout(){
            unset($_SESSION['oozledash']);
            return $this->success(array("Successfully logged out."));
        }
        public function old_login(){
            $reqs = new \Required_Parameters(array(),array("user"=>\Types::String,"password"=>\Types::String));
            return $this->validate_output($reqs,false,null,array($this,"old_login_callback"),false);
        }
        public function old_login_callback(){
            $db = $this->get_db();
            $query = "SELECT user_id,user username,asana_api_key,user_is_admin FROM old_users WHERE user='".$db->esc($this->parameters['user'])."'". (!isset($_COOKIE['user']) ? " AND password='".$db->esc(sha1($this->parameters['password']))."'" : "");
            $data = $db->query($query);
            if (count($data['rows']) > 0){
                foreach ($data['rows'][0] as $field=>$value){
                    $_SESSION[$field] = $value;
                }
                return $this->success(array("Successfully logged in. Now, please provide an email address and new password for the new system. These can be the same as your old login."));
            } else {
                return $this->error(array("Incorrect username or password."));
            }
        }
        
        
        public function login(){
            $reqs = new \Required_Parameters(array(),array("user_email_address"=>\Types::String,"user_password"=>\Types::String));
            return $this->validate_output($reqs,true,null,array($this,"login_callback"),false);
        }
        public function login_callback(){
            $db = $this->get_db();
            $query = "SELECT user_id,user_email_address,user_full_name,asana_api_key,asana_team_member_id,department,module_id,module,permissions.pay_grade_id FROM users ".
            "LEFT JOIN permissions USING (user_id) ".
            "LEFT JOIN departments USING (department_id) ".
            "INNER JOIN modules ON modules.department_id = departments.department_id AND permissions.pay_grade_id >= modules.pay_grade_id ".
            "WHERE user_email_address = '".$db->esc($this->parameters['user_email_address'])."' ".
            "AND user_password = SHA1(CONCAT('".$db->esc($this->parameters['user_password'])."',salt)) AND verified=1 ORDER BY department";
            $d = $db->query($query);
            if (count($d['rows']) > 0){
                $user_core = \Array_Manager::array_slice_assoc($d['rows'][0],'user_id','department');
                $user_core['departments'] = array();
                $departments = array();
                foreach ($d['rows'] as $row){
                    if (!isset($departments[$row['department']])){
                        $departments[$row['department']] = array("pay_grade_id"=>$row['pay_grade_id'],"modules"=>array());
                    }
                    array_push($departments[$row['department']]['modules'],$row['module']);
                }
                foreach ($departments as $name=>$val){
                    $val['department_name'] = $name;
                    array_push($user_core['departments'],$val);
                }
                $_SESSION['oozledash'] = \Handy::objectify($user_core);
                return $this->success($user_core);
            } else {
                return $this->error(array("Incorrect email or password."));
            }
        }
        public function register(){
            $reqs = new \Required_Parameters(array(),array("user_email_address"=>\Types::Email,"user_password"=>\Types::String,"user_full_name"=>\Types::String,"confirm_password"=>\Types::String,"asana_api_key"=>\Types::String));
            return $this->validate_output($reqs,false,null,array($this,"register_callback"),false);
        }
        private function rand_string($max_length){
            $str = "";
            for ($i=0;$i<$max_length;$i++){
                $fork = rand(1,3);
                switch($fork){
                    case 1:
                        $str .= chr(rand(48,57));
                        break;
                    case 2:
                        $str .= chr(rand(65,90));
                        break;
                    case 3:
                        $str .= chr(rand(97,122));
                        break;
                }
            }
            return $str;
        }
        private function bump_array_entry($row){
            return $row['asana_workspace_id'];
        }
        
        private function get_departments($api_key){
            $db = $this->get_db();
            $d = $db->query("SELECT asana_workspace_id,department_id FROM departments");
            $this->asana = new \Asana_API($api_key);
            $data = $this->asana->as_get("/workspaces");
            $data = json_decode($data['contents']);
            if (isset($data->errors)) return array();
            $my_valid_workspaces = array();
            for ($i=0;$i<count($data->data);$i++){
                foreach ($d['rows'] as $row){
                    if ($row['asana_workspace_id'] == $data->data[$i]->id)
                        array_push($my_valid_workspaces,$row['department_id']);
                }
            }
            return $my_valid_workspaces;
        }
        
        public function register_callback(){
            if ($this->parameters['user_password'] !== $this->parameters['confirm_password'])
                return $this->error(array("Password does not match confirmation."));
            $db = $this->get_db();
            $departments = $this->get_departments($this->parameters['asana_api_key']);
            if (count($departments) == 0)
                return $this->error(array("You need to have access to Oozle Asana workspaces to use this application."));
            $salt = $this->rand_string(rand(10,15));
            $verification_code = $this->rand_string(rand(40,50));
            $pw_hash = sha1($this->parameters['user_password'].$salt);
            $me = $this->asana->as_get("/users/me");
            $as_info = (json_decode($me['contents']));
            $asana_team_member_id = $as_info->data->id;
            $query = "INSERT IGNORE INTO users ".
                "(user_full_name,user_email_address,user_password,salt,verification_code,asana_api_key,asana_team_member_id) VALUES ".
                "('".$db->esc($this->parameters['user_full_name'])."','".$db->esc($this->parameters['user_email_address'])."','".$db->esc($pw_hash)."','".$db->esc($salt)."','".$db->esc($verification_code)."','".$db->esc($this->parameters['asana_api_key'])."','".$db->esc($asana_team_member_id)."')";
            $d = $db->query($query);
            if ($d['affected_rows'] > 0){
                $perm_array = array();
                foreach ($departments as $dept_id)
                    array_push($perm_array,"(".$db->esc($d['insert_id']).",".$db->esc($dept_id).",1)");
                $perm_query = "INSERT IGNORE INTO permissions (user_id,department_id,pay_grade_id) VALUES ".implode(",",$perm_array);
                $db->query($perm_query);
                $email = $this->parameters['user_email_address'];
                $subject = "Confirm Your OozleDash Registration";
                $body = "To confirm your OozleDash registration, visit the following URL: ".HTTP_ROOT."/Users/verify?user_id=".$d['insert_id']."&verification_code=".urlencode($verification_code);
                mail($email,$subject,$body,"From: cameron@oozlemedia.com");
                return $this->success(array("You have been successfully registered. Check your inbox for a confirmation email to get started."));
            } else {
                return $this->error(array("Looks like someone is already using that email or API key."));
            }
        }
        public function search_pay_grades(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(3,array("Content","SEO","PPC","Web Development")),array($this,"search_pay_grades_callback"));
        }
        public function search_pay_grades_callback(){
            $db = $this->get_db();
            $query = "SELECT * FROM pay_grades";
            $d = $db->query($query);
            return $this->success($d['rows']);
        }
        public function set_permissions(){
            $reqs = new \Required_Parameters(array(),array("permission_id"=>\Types::Int,"pay_grade_id"=>\Types::Int,"department_id"=>\Types::Int));
            return $this->validate_output($reqs,true,new \Permission(3,array("Content","SEO","PPC","Web Development")),array($this,"set_permissions_callback"));
        }
        public function set_permissions_callback(){
            $db = $this->get_db();
            if (\Array_Manager::is_multidimensional($this->parameters))
                $dept_id = $this->parameters[0]['department_id'];
            else
                $dept_id = $this->parameters['department_id'];
            $query1 = "SELECT department FROM departments WHERE department_id = ".$db->esc($dept_id);
            $d = $db->query($query1);
            if (count($d['rows']) > 0){
                $perm = new \Permission(3,$d['rows'][0]['department']);
                if ($perm->has_permission()){
                    $query = "UPDATE permissions SET pay_grade_id = CASE (permission_id) ";
                    if (\Array_Manager::is_multidimensional($this->parameters)){
                        $department_id = $parameters['department_id'];
                        foreach ($this->parameters as $parameters){
                            $query .= "WHEN ".$db->esc($parameters['permission_id'])." THEN ".$db->esc($parameters['pay_grade_id'])." ";
                        }
                    } else {
                        $department_id = $this->parameters['department_id'];
                        $query .= "WHEN ".$db->esc($this->parameters['permission_id'])." THEN ".$db->esc($this->parameters['pay_grade_id'])." ";
                    }
                    $query .= "END WHERE department_id = ".$db->esc($dept_id);
                    $d = $db->query($query);
                    return $this->success(array("Permissions successfully updated."));
                } else {
                    return $this->error(array("You do not have permission to set permissions within this department."));
                }
            } else {
                return $this->error(array("This department does not exist."));
            }
            //Check to see if the person setting the permissions has a pay grade of three in the department in which he's trying to change permissions.
        }
        public function verify(){
            $reqs = new \Required_Parameters(array(),array("verification_code"=>\Types::String,"user_id"=>\Types::Int));
            return $this->validate_output($reqs,false,null,array($this,"verify_callback"),false);
        }
        public function verify_callback(){
            $db = $this->get_db();
            $d = $db->query("UPDATE users SET verified=1 WHERE user_id=".$db->esc($this->parameters['user_id'])." AND verification_code = '".$db->esc($this->parameters['verification_code'])."'");
            if ($d['affected_rows'] > 0){
                return $this->success(array("Account successfully verified. You may now log in."));
            } else {
                return $this->error(array("Looks like you've got the wrong info there."));
            }
        }
        public function search(){
            $reqs = new \Required_Parameters(array(),array("department_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(3,array("Content","SEO","PPC","Web_development")),array($this,"search_callback"));            
        }
        public function search_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT department FROM departments WHERE department_id = ".$db->esc($this->parameters['department_id']));
            if (count($d['rows']) > 0){
                $perm = new \Permission(3,$d['rows'][0]['department']);
                if ($perm->has_permission()){
                    $d = $db->query("SELECT user_id,user_full_name,department_id,permission_id,pay_grade_id FROM users LEFT JOIN permissions USING (user_id) LEFT JOIN departments USING (department_id) WHERE department_id = ".$db->esc($this->parameters['department_id']));
                    return $this->success($d['rows']);
                } else {
                    return $this->error(array("You do not have permission to search in this department."));
                }
            } else {
                return $this->error(array("This department does not exist."));
            }
        }
        public function request_new_password(){
            $reqs = new \Required_Parameters(array(),array("user_email_address"));
            return $this->validate_output($reqs,false,null,array($this,"request_new_password_callback"),false);
        }
        public function request_new_password_callback(){
            $db = $this->get_db();
            $reset_pw_code = $this->rand_string(rand(30,40));
            $d = $db->query("SELECT user_id FROM users WHERE user_email_address = '".$db->esc($this->parameters['user_id']));
            if (count($d['rows']) > 0){
                $d2 = $db->query("UPDATE users SET reset_pw_code = '".$db->esc($reset_pw_code)."' WHERE user_id = ".$db->esc($d['rows'][0]['user_id']));
                $email = $this->parameters['user_email_address'];
                $subject = "Reset Your OozleDash Password";
                $body = "To reset your OozleDash password, visit the following URL: ".HTTP_ROOT."/api/All/Users/create_new_password?user_id=".$d['rows'][0]['user_id']."&reset_pw_code=".$reset_pw_code;
                mail($email,$subject,$body,"From: cameron.oozle@gmail.com");
                return $this->success(array("Check your inbox to reset your password."));
            } else {
                return $this->error(array("The email address you entered doesn't belong to any account."));
            }
        }
        public function create_new_password(){
            $reqs = new \Required_Parameters(array(),array("user_id"=>\Types::Int,"reset_pw_code"=>\Types::String,"user_password"=>\Types::String,"confirm_password"=>\Types::String));
            return $this->validate_output($reqs,false,null,array($this,"create_new_password_callback"),false);
        }
        public function create_new_password_callback(){
            $db = $this->get_db();
            if ($this->parameters['user_password'] !== $this->parameters['confirm_password'])
                return $this->error(array("Password does not match confirmation."));
            $d = $db->query("UPDATE users SET user_password = '".$db->esc($this->parameters['user_password'])."' WHERE user_id = ".$db->esc($this->parameters['user_id'])." AND reset_pw_code = '".$db->esc($this->parameters['reset_pw_code']));
            if ($d['affected_rows'] > 0){
                return $this->success(array("Password successfully updated."));
            } else {
                return $this->error(array("Looks like you got some information wrong."));
            }
        }
        public static function sess_user_id(){
            return (isset($_SESSION['oozledash']->user_id) ? $_SESSION['oozledash']->user_id : "null");
        }
        public static function asana_api_key(){
            return (isset($_SESSION['oozledash']->asana_api_key) ? $_SESSION['oozledash']->asana_api_key : "null");
        }
        public static function asana_team_member_id(){
            return (isset($_SESSION['oozledash']->asana_team_member_id) ? $_SESSION['oozledash']->asana_team_member_id : "null");
        }
        public static function is_logged(){
            return (isset($_SESSION['oozledash']));
        }
    }
}
?>