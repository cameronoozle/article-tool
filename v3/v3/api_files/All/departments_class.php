<?php
namespace API\All {
    class Departments extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        //The search function returns a list of all departments to which the user has access.
        public function search(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));            
        }
        public function search_callback(){
            $db = $this->get_db();
            
            //We select starting with the users table, then join to permissions and departments.
            //This is so that we only select departments which the user has permission to view.
            $d = $db->query("SELECT department_id,department FROM users LEFT JOIN permissions USING (user_id) LEFT JOIN departments USING (department_id)");
            return $this->success($d['rows']);
        }
        
    }
}
?>