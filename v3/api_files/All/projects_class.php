<?php
namespace API\All {
    class Projects extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function search(){
            $reqs = new \Required_Parameters(array(),array("department_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            $query = "SELECT project_id,project,asana_project_id FROM permissions ".
            "LEFT JOIN departments USING (department_id) ".
            "LEFT JOIN projects USING (department_id) ".
            "WHERE user_id = ".$db->esc(Users::sess_user_id())." AND department_id = ".$db->esc($this->parameters['department_id']);
            echo $query."\n";
            $d = $db->query($query);
            return $this->success($d['rows']);
        }
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(3,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"));
        }
        public function refresh_callback(){
            $as = new \Asana_API(Users::asana_api_key());
            $a = $as->as_get("/projects");
            $db = $this->get_db();
            $d = $db->query("SELECT department_id,asana_workspace_id FROM departments");
            $query = "INSERT IGNORE INTO projects (project,asana_project_id,department_id) VALUES ";
            $arr = array();
            foreach ($d['rows'] as $row){
                $a = $as->as_get("/workspaces/".$row['asana_workspace_id']."/projects");
                $q = json_decode($a['contents']);
                foreach ($q->data as $asrow){
                    array_push($arr,"('".$db->esc($asrow->name)."','".$db->esc($asrow->id)."',".$db->esc($row['department_id']).")");
                }
            }
            $db->query($query.implode(",",$arr));
            return $this->success(array("Projects successfully refreshed"));
            print_r(json_decode($a['contents']));
        }
    }
}
?>