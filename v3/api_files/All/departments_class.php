<?php
namespace API\All {
    class Departments extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function search(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));            
        }
        public function search_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT department_id,department FROM users LEFT JOIN permissions USING (user_id) LEFT JOIN departments USING (department_id)");
            return $this->success($d['rows']);
        }
        
    }
}
?>