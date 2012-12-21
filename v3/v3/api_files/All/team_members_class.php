<?php
namespace API\All {
    class Team_members extends \Endpoint {
        //A team member is different from a user, in that a user actually uses the Oozle Dashboard tool,
        //whereas a team member only has to use one of the Oozle Asana workspaces. In the eyes of the Oozle Dashboard,
        //a team member is an object - not a user with subjectivity or agency.
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function search(){
            $reqs = new \Required_Parameters(array(),array("department_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));            
        }
        public function search_callback(){
            $db = $this->get_db();
            //Check first to see if the user has access to the department in which they're searching.
            //This method was written before the birth of the Permission object, so we could swap out this database interface
            //with a permission object if we wanted to.
            $d = $db->query("SELECT department_id FROM permissions WHERE department_id = ".$db->esc($this->parameters['department_id'])." AND user_id = ".$db->esc(Users::sess_user_id()));
            if (count($d['rows']) > 0){
                
                //Select all the details about all of the team members within the specified department.
                $query = "SELECT * FROM team_member_department_pairings LEFT JOIN team_members USING (team_member_id) LEFT JOIN departments USING (department_id) WHERE department_id = ".$db->esc($this->parameters['department_id']);
                $d = $db->query($query);
                return $this->success($d['rows']);
            } else {
                return $this->error(array("You do not have permission to search in this module."));
            }
            //User must have access to the module in which they are searching.
        }
        //The refresh method interfaces with Asana to add new Asana users to our list of team members and
        //update the names of any team members as they may have changed in Asana.
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,null,array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $db = $this->get_db();
            
            //Set up the Asana interface. This currently uses Cameron's API key, since we run it as a cron job and
            //needs to be access whilst not logged in. We may want to change this in the future.
            $as = new \Asana_API("4OiXYyQ.vLCmvy1MZM21boUyDQxUc8Mh");
            
            //Get a list of all of our department-workspaces from Asana.
            $d = $db->query("SELECT department_id,asana_workspace_id FROM departments");

            //Prepare to add new team members to the database.
            $inquery = "INSERT INTO team_members (team_member,asana_team_member_id) VALUES ";
            $inarr = array();

            //Prepare to bind team members to departments.
            $pairquery = "INSERT IGNORE INTO team_member_department_pairings (team_member_id,department_id) ";
            $pairarr = array();
            
            //For each workspace in the database...
            foreach ($d['rows'] as $row){
                //Retrieve a list of users in the given workspace from Asana.
                $a = $as->as_get("/workspaces/".$row['asana_workspace_id']."/users");
                $q = json_decode($a['contents']);
                //For each Asana user retrieved...
                foreach ($q->data as $asrow){
                    //Add them to the database.
                    array_push($inarr,"('".$db->esc($asrow->name)."','".$db->esc($asrow->id)."')");
                    //Bind them to the current workspace-department.
                    array_push($pairarr,"SELECT team_member_id,'".$db->esc($row['department_id'])."' FROM team_members WHERE asana_team_member_id = '".$db->esc($asrow->id)."'");
                }
            }
            //Update any team member names that may have changed in Asana. Execute queries!
            $db->query($inquery.implode(",",$inarr)." ON DUPLICATE KEY UPDATE team_member=VALUES(team_member)");
            $db->query($pairquery.implode(" UNION ",$pairarr));
            return $this->success(array("Team members successfully updated"));
        }
    }
}
?>