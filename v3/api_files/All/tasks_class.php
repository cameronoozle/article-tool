<?php
namespace API\All {
    class Tasks extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        public function dupes(){
            $db = $this->get_db();
            $query = "SELECT client,keyword, COUNT(task_id) num_tasks FROM tasks GROUP BY keyword,client HAVING num_tasks > 1 ORDER BY num_tasks DESC";
            $d = $db->query($query);
            print_r($d['rows']);
            foreach ($d['rows'] as $row){
                echo join("     ",$row)."\n";
            }
        }
        
        public function reconcile(){
            set_time_limit(0);
            $db = $this->get_db();
            $query = "INSERT INTO tasks (task,asana_task_id,asana_team_member_id,notes,asana_workspace_id,keyword,client) VALUES ";
            $as = new \Asana_API(Users::asana_api_key());
            $a = $as->as_get("/projects/2136172000758/tasks");
            $q = json_decode($a['contents']);
            $i=0;
            foreach ($q->data as $t){
                print_r($t);
                $z = $as->as_get("/tasks/".$t->id);
                $z = json_decode($z['contents']);
                print_r($z);
                $split = preg_split("/\n/",$z->data->notes);
                foreach ($split as $item){
                    if (preg_match("/^keyword: /",$item)){
                        $keyword = $item;
                    }
                    if (preg_match("/^client: /",$item)){
                        $client = $item;
                    }
                }
                $chumbo = "(".implode(",",array(
                    "'".$db->esc($z->data->name)."'",
                    "'".$db->esc($z->data->id)."'",
                    "'".(is_object($z->data->assignee) ? $db->esc($z->data->assignee->id) : "")."'",
                    "'".$db->esc($z->data->notes)."'",
                    "'".$db->esc($z->data->workspace->id)."'",
                    (isset($keyword) ? "'".$db->esc(str_replace("keyword: ","",$keyword))."'" : "null"),
                    (isset($client) ? "'".$db->esc(str_replace("client: ","",$client))."'" : "null")
                ))."),";
                echo $chumbo."\n";
                $query .= $chumbo;
                
                unset($keyword,$client);
                usleep(250000);
                $i++;
            }
            $query = substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE client=VALUES(client), keyword=VALUES(keyword)";
            echo $query;
        }
        
        
        
        
        public function fix2(){
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM articles INNER JOIN tasks USING (task_id) WHERE article_status_id = 5");
            $as = new \Asana_API(Users::asana_api_key());
            $query = "UPDATE articles SET article_status_id = 6 WHERE article_status_id = 5 AND (";
            $wheres = array();
            for ($i=0;$i<count($d['rows']);$i++){
                $a = $as->as_get("/tasks/".$d['rows'][$i]['asana_task_id']);
                $q = json_decode($a['contents']);
                if ($q->data->completed == 1){
                    array_push($wheres,"task_id = ".$d['rows'][$i]['task_id']);
                }
            }
            $query .= implode(" OR ",$wheres).")";
            echo $query."\n\n";
            $d = $db->query($query);
        }
        public function fix (){
            set_time_limit(0);
            $db = $this->get_db();
            $query = "SELECT * FROM tasks WHERE asana_team_member_id = 0 OR asana_team_member_id IS NULL";
            $d = $db->query($query);
            print_r($d);
            $as = new \Asana_API(Users::asana_api_key());
            $query = "UPDATE tasks SET asana_team_member_id = CASE (asana_task_id) ";
            for ($i=0;$i<count($d['rows']);$i++){
                $a = $as->as_get("/tasks/".$d['rows'][$i]['asana_task_id']);
                $q = json_decode($a['contents']);
                print_r($q);
                $d['rows'][$i]['status'] = ($q->data->completed == 1 ? 6 : "");
                if (is_object($q->data->assignee)){
                    $d['rows'][$i]['blah'] = $q->data;                
                    $d['rows'][$i]['stuff'] = $q->data->assignee->id;
                    $query .= "WHEN '".$d['rows'][$i]['asana_task_id']."' THEN '".$d['rows'][$i]['stuff']."' ";
                }
                sleep(1);
            }
            $query .= " ELSE asana_team_member_id END WHERE asana_team_member_id = 0 OR asana_team_member_id IS NULL";
            echo $query."\n\n";
            $d = $db->query($query);
            return $this->success(array($d));
        }
        
        public function view (){
            $reqs = new \Required_Parameters(array(),array("asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"view_callback"));
        }
        public function view_callback(){
            $as = new \Asana_API(Users::asana_api_key());
            $a = $as->as_get("/tasks/".$this->parameters['asana_task_id']);
            return $this->success(json_decode($a['contents']));
        }
        public function create(){
            $reqs = new \Required_Parameters(array(),array("asana_workspace_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"create_callback"));
        }
        public function create_callback(){
            $as = new \Asana_API(Users::asana_api_key());
            $opt_params = array("assignee"=>\Types::Int,"notes"=>\Types::String,"name"=>\Types::String);
            $data = array();
            foreach ($opt_params as $pname=>$pval){
                if ((isset($this->parameters[$pname]))&&(\Types::matches_type($this->parameters[$pname],$pval))){
                    $data[$pname] = $this->parameters[$pname];
                }
            }
            $a = $as->as_post("/workspaces/".$this->parameters['asana_workspace_id']."/tasks",json_encode(array("data"=>$data)));
            $q = json_decode($a['contents']);
            if (isset($q->data)){
                $db = $this->get_db();
                $inquery = "INSERT IGNORE INTO tasks (task,asana_task_id,asana_team_member_id,asana_workspace_id) VALUES ('".$db->esc($q->data->name)."','".$db->esc($q->data->id)."',".(!empty($q->data->assignee) ? "'".$q->data->assignee->id."'" : "null").",'".$db->esc($this->parameters['asana_workspace_id'])."')";
                $d = $db->query($inquery);
                $selquery = "SELECT * FROM tasks WHERE asana_task_id = '".$db->esc($q->data->id)."'";
                $d = $db->query($selquery);
                return $this->success($d['rows'][0]);
            } else if (isset($q->errors)){
                return $this->error($q->errors);
            } else {
                return $this->error(array("Looks like the Asana server is down."));
            }
        }
        public function update(){
            $reqs = new \Required_Parameters(array(),array("asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"update_callback"));
        }
        public function update_callback(){
            $as = new \Asana_API(Users::asana_api_key());
            $opt_params = array("assignee"=>\Types::Int,"notes"=>\Types::String,"name"=>\Types::String);
            $data = array();
            foreach ($opt_params as $pname=>$pval){
                if ((isset($this->parameters[$pname]))&&(\Types::matches_type($this->parameters[$pname],$pval))){
                    $data[$pname] = $this->parameters[$pname];
                }
            }
            if ((isset($this->parameters['assignee']))&&($this->parameters['assignee'] == "null"))
                $data['assignee'] = "null";
            $a = $as->as_put("/tasks/".$this->parameters['asana_task_id'],array("data"=>$data));
            $q = json_decode($a['contents']);
            if (isset($q->data)){
                $q = $q->data;
                $updates = array("asana_team_member_id"=>(is_object($q->assignee) ? $q->assignee->id : "null"),"notes"=>$q->notes,"task"=>$q->name);
                $sets = array();
                foreach ($updates as $iname=>$ival){
                    if (!empty($ival))
                        array_push($sets,$iname." = '".$ival."'");
                }
                if (count($sets) > 0){
                    $db = $this->get_db();
                    $db->query("UPDATE tasks SET ".implode(",",$sets));
                }
                return $this->success(array("Task successfully updated."));
            } else if (isset($q->errors)){
                return $this->error($q->errors);
            } else {
                return $this->error(array("Looks like the Asana server is down."));
            }
        }
        public function search(){
            $reqs = new \Required_Parameters(array("department_id"=>\Types::Int,"month"=>\Types::Datetime,"asana_team_member_id"=>\Types::Int,"team_member_id"=>\Types::Int,"client_id"=>\Types::Int,"asana_project_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            $wheres = array();
            foreach ($this->parameters as $pname=>$pval){
                array_push($wheres,$pname." = '".$db->esc($pval)."'");
            }
            $d = $db->query("SELECT * FROM tasks LEFT JOIN team_members USING (asana_team_member_id) LEFT JOIN departments USING (asana_workspace_id) WHERE ".implode(" OR ",$wheres));
            return $this->success($d['rows']);
        }
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(2,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"));
        }
        public function refresh_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM tasks");
            $as = new \Asana_API(Users::asana_api_key());
            foreach ($d['rows'] as $row){
                $a = $as->as_get("/tasks/".$row['asana_task_id']);
                $q = json_decode($a['contents']);
                if (isset($q->data)){
                    $query = "UPDATE tasks SET asana_team_member_id = '".$db->esc($q->data->assignee->id)."', notes = '".$db->esc($q->data->notes)."', task = '".$db->esc($q->data->name)."' WHERE asana_task_id = ".$db->esc($q->data->id);
                    $db->query($query);
                } else if (!isset($q->errors)){
                    return $this->error(array("Looks like the Asana server is down."));
                }
                sleep(1);
            }
            return $this->success(array("Tasks successfully updated."));
        }
        public function add_project($asana_task_id,$asana_project_id){
            $as = new \Asana_API(Users::asana_api_key());
            $data = json_encode(array("data"=>array("project"=>$asana_project_id)));
            $a = $as->as_post("/tasks/".$asana_task_id."/addProject",$data);
            $q = json_decode($a['contents']);
            if (isset($q->data)){
                return \Endpoint::success(array($q));
            } else {
                return \Endpoint::error($q);
            }
        }
    }
}
?>