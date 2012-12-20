<?php
namespace API\All {
    class Team_members extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function search(){
            $reqs = new \Required_Parameters(array(),array("department_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));            
        }
        public function search_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT department_id FROM permissions WHERE department_id = ".$db->esc($this->parameters['department_id'])." AND user_id = ".$db->esc(Users::sess_user_id()));
            if (count($d['rows']) > 0){
                $query = "SELECT * FROM team_member_department_pairings LEFT JOIN team_members USING (team_member_id) LEFT JOIN departments USING (department_id) WHERE department_id = ".$db->esc($this->parameters['department_id']);
                $d = $db->query($query);
                return $this->success($d['rows']);
            } else {
                return $this->error(array("You do not have permission to search in this module."));
            }
            //User must have access to the module in which they are searching.
        }
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,null,array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $db = $this->get_db();
            $as = new \Asana_API("4OiXYyQ.vLCmvy1MZM21boUyDQxUc8Mh");
            $d = $db->query("SELECT department_id,asana_workspace_id FROM departments");
            $inquery = "INSERT INTO team_members (team_member,asana_team_member_id) VALUES ";
            $inarr = array();
            $pairquery = "INSERT IGNORE INTO team_member_department_pairings (team_member_id,department_id) ";
            $pairarr = array();
            foreach ($d['rows'] as $row){
                $a = $as->as_get("/workspaces/".$row['asana_workspace_id']."/users");
                $q = json_decode($a['contents']);
                foreach ($q->data as $asrow){
                    array_push($inarr,"('".$db->esc($asrow->name)."','".$db->esc($asrow->id)."')");
                    array_push($pairarr,"SELECT team_member_id,'".$db->esc($row['department_id'])."' FROM team_members WHERE asana_team_member_id = '".$db->esc($asrow->id)."'");
                }
            }
            $db->query($inquery.implode(",",$inarr)." ON DUPLICATE KEY UPDATE team_member=VALUES(team_member)");
            $db->query($pairquery.implode(" UNION ",$pairarr));
            return $this->success(array("Team members successfully updated"));
        }
    }
}
?>