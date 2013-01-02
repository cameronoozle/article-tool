<?php
namespace Asana {
class Task extends AsanaObject {
    public $id, $name, $assignee, $notes, $projects, $dueDate;
    
    public static function get($id,$refresh = false){
        $caller = AsanaObject::caller();
        if ($caller['class'] !== 'Workspace')
            throw new Exception("Only a Workspace object can create a new Task");

        //Request details from Asana.
        $interface = new Asana_API(\API\All\Users::asana_api_key());
        $taskRequest = $interface->as_get("/tasks/".$id);
        $q = json_decode($taskRequest['contents']);
        
        //Create and return a new Task.
        if (isset($q->data)){
            $d = $q->data;
            $task = new Task($d->id,$d->name,$d->assignee,$d->notes,$d->due_on,$refresh);
            
            //Add all projects to the created task.
            foreach ($d->projects as $proj){
                array_push($task->projects, new Project($proj->name,$proj->id));
            }
            return $task;
        } else {
            throw new Exception("Asana Task Details request failed");
        }
    }
    
    public static function create($name,$workspaceID,$assignee = null,$notes = "",$dueDate = ""){

        //Default date for articles is the 25th day of the month - not handled here.
        $caller = AsanaObject::caller();
        if ($caller['class'] !== 'Workspace')
            throw new Exception("Only a Workspace object can create a new Task");
        
        //Set up the Asana interface.
        $interface = new Asana_API(\API\All\Users::asana_api_key());

        //Set up task specifications to send to Asana.
        $params = array("name"=>$name,"notes"=>$notes,"workspace"=>$workspaceID,"due_on"=>substr_replace($dueDate,"",-9,9));
        if (isset($assignee->id))
            $params['assignee'] = $assignee->id;

        //Make the request.
        $taskRequest = $interface->as_post("/tasks",json_encode(array("data"=>$params)));
        $q = json_decode($taskRequest['contents']);
        
        //Create and return a new Task.
        if (isset($q->data)){
            $d = $q->data;
            $task = new Task($d->id,$d->name,$d->assignee,$d->notes,$d->due_on,true);
            
            //Add all projects to the new task.
            foreach ($d->projects as $proj){
                array_push($task->projects, new Project($proj->name,$proj->id));
            }
            return $task;
        } else {
            throw new Exception("Task Creation Request failed");
        }
    }
    
    private function __construct($id, $name, $assignee, $notes, $dueDate, $refresh = false){
        $this->projects = array();
        $this->id = $id;
        $this->name = $name;
        $this->assignee = $assignee;
        $this->notes = $notes;
        $this->dueDate = $dueDate;
        if ($refresh)
            $this->refresh();
    }

    private function refresh(){
        $db = $this->getDB();
        $query = "INSERT INTO TASKS (asana_task_id,task,asana_team_member_id,notes,due_on) VALUES ".
        "(".$db->esc($this->id).",'".$db->esc($this->name)."',".
            (isset($this->assignee->id) ? $db->esc($this->assignee->id) : "null")
        .",'".$db->esc($this->notes)."','".$db->esc($this->dueDate)."')";
        $db->query($query);
    }

    public function addProject(Project $project){
        //Use Asana API to add a project to self.
        $interface = $this->getAPI();
        $addRequest = $interface->as_post("/tasks/".$this->id."/addProject",json_encode(array("data"=>array("project"=>$project))));
        //Add project to $this->projects.
        array_push($this->projects,$project);
        //Return self, to allow chaining, I guess. This isn't really super necessary.
        return $this;
    }

    public function removeProject($projectID){
        //Use Asana API to remove a project.
        $interface = $this->getAPI();
        $removeRequest = $interface->as_post("/tasks/".$this->id."/removeProject",json_encode(array("data"=>array("project"=>$project))));
        //Remove project from $this->projects.
        foreach ($this->projects as $index=>$proj){
            if ($proj->id == $projectID)
                array_splice($this->projects,$index,1);
        }
        //Return self.
    }

    public function update($name="428diemcwpqmd",$assignee="639cfe8fmn5830",$notes="320d485ydk920887",$dueDate="5dk49dkA0A94kdi"){
        //The argument defaults are intentionally obscure so as to ensure that the user doesn't accidentally set
        //Any task attributes to null.

        //Foreach of the arguments that is not its default value, we'll change its value in Asana.
        $params = array();
        if ($name !== "428diemcwpqmd"){
            $params['name'] = $name;
            $this->name = $name;
        }
        if ((!is_string($assignee))||(is_null($assignee))){
            $params['assignee'] = $assignee->id;
            $this->assignee = $assignee;
        }
        if ($notes !== "320d485ydk920887"){
            $params['notes'] = $notes;
            $this->notes = $notes;
        }
        if ($dueDate !== "5dk49dkA0A94kdi"){
            $params['due_on'] = $dueDate;
            $this->dueDate = $dueDate;
        }

        if (count($params) > 0){
            $interface = $this->getAPI();
            $updateRequest = $interface->as_put("/tasks/".$this->id,array("data"=>$params));
            $q = json_decode($updateRequest['contents']);
            if (!isset($q->data)){
                throw new Exception("Asana Task Update request failed.");
            }
        }
        
        //Return self to allow chaining.
        return $this;
    }
}
}
?>