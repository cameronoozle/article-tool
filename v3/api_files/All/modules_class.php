<?php
namespace API\All {
    class Modules extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function search(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT departments.department_id,departments.department,module_id,module FROM users ".
                            "LEFT JOIN permissions USING (user_id) ".
                            "LEFT JOIN departments USING (department_id) ".
                            "INNER JOIN modules ON departments.department_id = modules.department_id AND permissions.pay_grade_id >= modules.pay_grade_id ".
                            "WHERE user_id = ".Users::sess_user_id()." ORDER BY department,module");
            return $this->success($d['rows']);
            //Show only modules to which the user has access.
        }
    }
}
?>