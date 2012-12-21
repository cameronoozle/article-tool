<?php
class Task extends AsanaObject {
    public $id, $assignee, $notes, $name, $projects;
    
    public function __construct($id = 0, $name = "", $assignee = null, $notes = "", $workspaceID = 0){
        $caller = $this->caller();
        if ($caller['class'] !== 'Workspace')
            throw new Exception("Only a Workspace object can create a new Task");

        //If an ID is provided, get task details based on the ID from the Asana API.
        if ($id){
            //Request details from Asana.
            $interface = $this->getAPI();
            $taskRequest = $interface->as_get("/tasks/".$id);
            $q = json_decode($taskRequest['contents']);
            if (isset($q->data)){

                //Assign details to self.
                $d = $q->data;
                $this->id = $d->id;
                $this->name = $d->name;
                if (!empty($d->assignee))
                    $this->assignee = new Assignee($d->assignee->id,$d->assignee->name);
                $this->notes = $d->notes;
                foreach ($d->projects as $proj){
                    array_push($this->projects, new Project($proj->name,$proj->id));
                }

            } else {
                throw new Exception("Asana Task Details request failed");
            }

        //If a name and workspace ID and NO task ID are provided, create a new task via the Asana API.
        } else if ((!empty($name))&&($workspaceID)&&(!$id)){
            
            //Attempt to create a new task through API.
            $interface = $this->getAPI();
            
            //Set up task specifications to send to Asana.
            $params = array("name"=>$name,"notes"=>$notes,"workspace"=>$workspaceID);
            if (isset($assignee->id))
                $params['assignee'] = $assignee['id'];
                
            //Make the request.
            $taskRequest = $interface->as_post("/tasks",json_encode(array("data"=>$params)));
            $q = json_decode($taskRequest['contents']);

            if (isset($q->data)){
                
                //Assign details to self.
                $d = $q->data;
                $this->id = $d->id;
                $this->name = $name;
                $this->assignee = $assignee;
                $this->notes = $notes;
                
                foreach ($d->projects as $proj){
                    array_push($this->projects, new Project($proj->name,$proj->id));
                }
                
            } else {
                throw new Exception("Asana Task Creation request failed");
            }
            //Add functionality to save new task in database.
            
            
            
        //Otherwise, throw an exception.
        } else {
            throw new Exception("Task Constructor must have either an ID or both a workspace ID and name");
        }
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

    public function update($name="428diemcwpqmd",$assignee="639cfe8fmn5830",$notes="320d485ydk920887"){
        //The argument defaults are intentionally obscure so as to ensure that the user doesn't accidentally set
        //Any task attributes to null.

        //Foreach of the arguments that is not its default value, we'll change its value in Asana.
        $params = array();
        if ($name !== "428diemcwpqmd"){
            $params['name'] = $name;
            $this->name = $name;
        }
        if ((get_class($assignee) == 'Assignee')||(is_null($assignee))){
            $params['assignee'] = $assignee->id;
            $this->assignee = $assignee;
        }
        if ($notes !== "320d485ydk920887"){
            $params['notes'] = $notes;
            $this->notes = $notes;
        }
        if (count($params) > 0){
            $updateRequest = $interface->as_put("/tasks/".$this->id,json_encode(array("data"=>$params)));
            $q = json_decode($updateRequest['contents']);
            if (!isset($q->data)){
                throw new Exception("Asana Task Update request failed.");
            }
        }
        
        //Return self to allow chaining.
        return $this;
    }
}
?>