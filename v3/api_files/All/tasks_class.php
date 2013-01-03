<?php
namespace API\All {
    class Tasks extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        //To Cameron's discredit (this is Cameron, by the way. Hi.), we don't know which of these methods were quick hacks for
        //one-time use that are now obsolete, and which ones we use on a regular basis. We'll come back to these.
        
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
            $query = "UPDATE articles SET article_status_id = 6, written=1 WHERE (article_status_id = 5 OR written=0) AND (";
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
            if (!isset($_SESSION['blackmarks']))
                $_SESSION['blackmarks'] = array();
            set_time_limit(0);
            $db = $this->get_db();
            $wheres = array();
            foreach ($_SESSION['blackmarks'] as $blackmark){
                array_push($wheres,"task_id != ".$blackmark);
            }
            $query = "SELECT * FROM tasks WHERE ".(count($wheres) > 0 ? "(".implode(" AND ",$wheres).") AND " : "")." asana_team_member_id = 0 OR asana_team_member_id IS NULL LIMIT 15";
            $d = $db->query($query);
            print_r($d);
            $as = new \Asana_API(Users::asana_api_key());
            $query = "UPDATE tasks SET asana_team_member_id = CASE (asana_task_id) ";
            for ($i=0;$i<(count($d['rows']) < 15 ? count($d['rows']) : 15);$i++){
                $a = $as->as_get("/tasks/".$d['rows'][$i]['asana_task_id']);
                $q = json_decode($a['contents']);
                $d['rows'][$i]['status'] = ($q->data->completed == 1 ? 6 : "");
                if (is_object($q->data->assignee)){
                    $d['rows'][$i]['blah'] = $q->data;                
                    $d['rows'][$i]['stuff'] = $q->data->assignee->id;
                    $query .= "WHEN '".$d['rows'][$i]['asana_task_id']."' THEN '".$d['rows'][$i]['stuff']."' ";
                } else {
                    array_push($_SESSION['blackmarks'],$d['rows'][$i]['task_id']);
                }
                usleep(250000);
            }
            $query .= " ELSE asana_team_member_id END WHERE asana_team_member_id = 0 OR asana_team_member_id IS NULL";
            echo $query."\n\n";
            $d = $db->query($query);
            return $this->success(array($d));
        }
        
        
        //This function simply retrieves and displays details from Asana about a single given task.
        public function view (){
            $reqs = new \Required_Parameters(array(),array("asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"view_callback"));
        }
        public function view_callback(){
            //Set up the Asana interface.
            $as = new \Asana_API(Users::asana_api_key());
            //Retrieve the task details.
            $a = $as->as_get("/tasks/".$this->parameters['asana_task_id']);
            //Return the details.
            return $this->success(json_decode($a['contents']));
        }
        
        //This method creates a new task, both in Asana and in the tool's database.
        public function create(){
            $reqs = new \Required_Parameters(array(),array("asana_workspace_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"create_callback"));
        }
        public function create_callback(){
            //Set up the Asana Interface.
            $as = new \Asana_API(Users::asana_api_key());
            
            //Optional parameters. If the user wants to provide notes, an assignee ID, or a name for the task, those things are allowed.
            //Asana doesn't require these parameters, so we don't either.
            $opt_params = array("assignee"=>\Types::Int,"notes"=>\Types::String,"name"=>\Types::String);
            
            //Data is an object containing all of the parameters that we'll be sending to Asana.
            $data = array();
            foreach ($opt_params as $pname=>$pval){
                if ((isset($this->parameters[$pname]))&&(\Types::matches_type($this->parameters[$pname],$pval))){
                    $data[$pname] = $this->parameters[$pname];
                }
            }
            
            if ((isset($this->parameters['due_on']))&&(\Types::matches_type($this->parameters['due_on'],\Types::Datetime)))
                $data['due_on'] = substr($this->parameters['due_on'],0,10);
            
            //Send the request to Asana to create the task.
            $a = $as->as_post("/workspaces/".$this->parameters['asana_workspace_id']."/tasks",json_encode(array("data"=>$data)));
            $q = json_decode($a['contents']);
            
            //If our request to Asana is successful...
            if (isset($q->data)){
                $db = $this->get_db();

                //Take the name, Asana ID, Asana team member ID, and workspace ID from the object we got back from our Asana request and put them in the database.
                $inquery = "INSERT IGNORE INTO tasks (task,asana_task_id,asana_team_member_id,asana_workspace_id,due_on) ".
                "VALUES ('".$db->esc($q->data->name)."','".$db->esc($q->data->id)."',".(!empty($q->data->assignee) ? "'".$q->data->assignee->id."'" : "null").",'".$db->esc($this->parameters['asana_workspace_id'])."','".$db->esc(date("Y-m-d H:i:s",strtotime($q->data->due_on)))."')";
                $d = $db->query($inquery);
                
                //Retrieve the task details for the just-inserted task from the database and return them so the user can view the task they just created.
                $selquery = "SELECT * FROM tasks WHERE asana_task_id = '".$db->esc($q->data->id)."'";
                $d = $db->query($selquery);
                return $this->success($d['rows'][0]);
            
            //Otherwise, either return the errors Asana has provided,
            } else if (isset($q->errors)){
                return $this->error($q->errors);
            //Or the Asana server isn't working.
            } else {
                return $this->error(array("Looks like the Asana server is down."));
            }
        }
        
        //Nothing special here. Just deleting tasks from the database. Requires a pay grade of 2.
        public function delete(){
            $reqs = new \Required_Parameters(array(),array("asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,array("Content","SEO","PPC","Web Development")),array($this,"delete_callback"));
        }
        public function delete_callback(){
            $db = $this->get_db();
            $query = "DELETE FROM tasks WHERE asana_task_id = ".$db->esc($this->parameters['asana_task_id']);
            $db->query($query);
            return $this->success(array("Task deleted."));
        }
        
        //Updates the name, notes, or assignee for a task.
        public function update(){
            $reqs = new \Required_Parameters(array(),array("asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"update_callback"));
        }
        public function update_callback(){
            //Set up the Asana interface.
            $as = new \Asana_API(Users::asana_api_key());
            
            //The user can update the assignee, notes, or name for the task. Data is the object we'll be sending to Asana.
            $opt_params = array("assignee"=>\Types::Int,"notes"=>\Types::String,"name"=>\Types::String);
            $data = array();
            foreach ($opt_params as $pname=>$pval){
                if ((isset($this->parameters[$pname]))&&(\Types::matches_type($this->parameters[$pname],$pval))){
                    $data[$pname] = $this->parameters[$pname];
                }
            }
            
            //If you're unassigning a task, the assignee's value is null. This isn't compatible with our requirement above that
            //the assignee be of type int, so we have to make a special exception here.
            if ((isset($this->parameters['assignee']))&&($this->parameters['assignee'] == "null"))
                $data['assignee'] = "null";
                
            //Execute the request.
            $a = $as->as_put("/tasks/".$this->parameters['asana_task_id'],array("data"=>$data));
            $q = json_decode($a['contents']);
            
            //If the request was successful...
            if (isset($q->data)){
                $q = $q->data;
                
                //Prepare to update the database. We will update the following values, if specified in the Asana response.
                $updates = array("asana_team_member_id"=>(is_object($q->assignee) ? $q->assignee->id : "null"),"notes"=>$q->notes,"task"=>$q->name);
                $sets = array();
                foreach ($updates as $iname=>$ival){
                    if (!empty($ival))
                        array_push($sets,$iname." = '".$ival."'");
                }

                //If we have more than one thing to update in the database, we will do so.
                if (count($sets) > 0){
                    $db = $this->get_db();
                    $db->query("UPDATE tasks SET ".implode(",",$sets)." WHERE asana_task_id = ".$this->parameters['asana_task_id']);
                }
                return $this->success(array("Task successfully updated."));
            //Otherwise, either return the errors Asana has provided,
            } else if (isset($q->errors)){
                return $this->error($q->errors);
            //or the Asana server isn't working.
            } else {
                return $this->error(array("Looks like the Asana server is down."));
            }
        }

        //Just returns a list of all tasks matching the user's specifications.
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
        
        //The refresh method interfaces with Asana to update the tool's database of tasks.
        //Right now, this task only updates tasks already in the database with changes from Asana.
        //It doesn't add new tasks into the database, because that would require just a little bit too much processor power,
        //since Asana only return details for a maximum of one task at a time.
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(2,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"));
        }
        public function refresh_callback(){
            
            //Retrieve a list of our tasks from the database.
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM tasks");
            $as = new \Asana_API(Users::asana_api_key());
            //For each task in our database...
            foreach ($d['rows'] as $row){
                //Retrieve the details about the task from Asana.
                $a = $as->as_get("/tasks/".$row['asana_task_id']);
                $q = json_decode($a['contents']);
                
                //If we got a response, update the database.
                if (isset($q->data)){
                    $query = "UPDATE tasks SET asana_team_member_id = '".$db->esc($q->data->assignee->id)."', notes = '".$db->esc($q->data->notes)."', task = '".$db->esc($q->data->name)."' WHERE asana_task_id = ".$db->esc($q->data->id);
                    $db->query($query);
                //We're not going to bother with errors. If the Asana server is down, the method will immediately fail.
                } else if (!isset($q->errors)){
                    return $this->error(array("Looks like the Asana server is down."));
                }
                sleep(1);
            }
            return $this->success(array("Tasks successfully updated."));
        }
        
        //Adds a project to an existing task.
        public function add_project($asana_task_id,$asana_project_id){
            
            //Set up the Asana interface.
            $as = new \Asana_API(Users::asana_api_key());
            $data = json_encode(array("data"=>array("project"=>$asana_project_id)));
            
            //Request that Asana add the project.
            $a = $as->as_post("/tasks/".$asana_task_id."/addProject",$data);
            $q = json_decode($a['contents']);
            
            //Simply return whatever information Asana gives us.
            if (isset($q->data)){
                return \Endpoint::success(array($q));
            } else {
                return \Endpoint::error($q);
            }
        }
    }
}
?>