<?php
namespace API\All {
    class Projects extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        //The search method returns a list of all projects within a given department, to which the user must have access.
        public function search(){
            $reqs = new \Required_Parameters(array(),array("department_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            
            //Selects from permissions, joining through departments and projects, so that it only selects those projects belonging to a department
            //that the user has access to.
            $query = "SELECT project_id,project,asana_project_id FROM permissions ".
            "LEFT JOIN departments USING (department_id) ".
            "LEFT JOIN projects USING (department_id) ".
            "WHERE user_id = ".$db->esc(Users::sess_user_id())." AND department_id = ".$db->esc($this->parameters['department_id']);
            $d = $db->query($query);
            return $this->success($d['rows']);
        }
        
        //The refresh method interfaces with Asana to update the tool's database of projects.
        public function refresh(){
            $reqs = new \Required_Parameters();
            //Because we might in the future want to run this function using a Cron Job, it's not required to log in.
            return $this->validate_output($reqs,false,new \Permission(3,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $api_key = Users::asana_api_key();
            
            //Set up our Asana interface. Because log in is not required, this uses Cameron's API key by default.
            //We may want to change this in the future.
            $as = new \Asana_API($api_key == "null" ? "4OiXYyQ.vLCmvy1MZM21boUyDQxUc8Mh" : $api_key);
            $a = $as->as_get("/projects");
            $db = $this->get_db();
            
            //Get a list of all departments from the tool's database.
            $d = $db->query("SELECT department_id,asana_workspace_id FROM departments");
            
            //Prepare our query. We'll be adding any projects that are in Asana to the database.
            $query = "INSERT IGNORE INTO projects (project,asana_project_id,department_id) VALUES ";
            $arr = array();
            
            //For each department in the database...
            foreach ($d['rows'] as $row){
                
                //Retrieve a list of projects for the workspace associated with the department.
                $a = $as->as_get("/workspaces/".$row['asana_workspace_id']."/projects");
                $q = json_decode($a['contents']);
                
                //Add each project to the database.
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