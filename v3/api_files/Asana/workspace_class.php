<?php
class Workspace extends AsanaObject {
    public $name,$id,$users,$projects;
    private $targ_proj_id;
    public function __construct($name,$id){
        //Assign my name and ID.
        $this->name = $name;
        $this->id = $id;
        
        //Set up Asana Interface.
        $interface = $this->getAPI();
        //We can't get a list of tasks for the workspace, because task searches must be limited by assignee in the
        //API, and a list of tasks for a single assignee doesn't really help us at all.        
        
        //Get a list of users and assign it to $this->users;
        $userRequest = $interface->as_get("/workspaces/".$this->id."/users");
        $this->users = json_decode($userRequest['contents'])->data;
        //Get a list of projects and assign it to $this->projects;
        $projRequest = $interface->as_get("/workspaces/".$this->id."/projects");
        $this->projects = json_decode($projRequest['contents'])->data;
    }
    private function projFilter($obj){
        return ($obj->id == $this->targ_proj_id);
    }
    
    public function getProject($projectID){
        //Check to make sure that the project specified by the ID is in $this->projects.
        $this->targ_proj_id = $projectID;
        $filtered = array_filter($this->projects,array($this,"projFilter"));
        //If it is, the Asana API isn't going to give us much more information about than we already have,
        //construct a project from the item in the list and return it.
        if (count($filtered) > 0)
            foreach ($filtered as $project)
                return new Project($project->name,$project->id);
        else
            return null;
    }
    public function createProject($projectName){
        //Use the Asana interface to create a new project, turn it into a project object and return it.
        $project = new Project($projectName,0,$this->id);
        array_push($this->projects,$project);
        return $project;
    }
    public function getTask($taskID){
        //Check to make sure that the task specified by the ID is in $this->tasks.
        //If it is, retrieve the task from the API, turn it into a task object and return it.
    }
    public function addTask(Task $task){
        //Create a new task that is a perfect copy of the one provided.
        //Add the new task to $this->tasks.
        //Delete the task from the database.
        //Update the task object to make it clear that it has been deleted.
    }
    public function deleteTask($taskID){
        //Delete the task from the database.
        //Remove it from $this->tasks.
        //Update the task object to make it clear it has been deleted.
    }
    public function createTask($name,Assignee $assignee,$notes){
        //Create a new task object assigned to self.
        //Add the new task object to $this->tasks.
        //Return the new task.
        //$newTask = new Task($name,$assignee,$notes,$this);
        //array_push($this->tasks,$newTask);
        //return $newTask;
    }
    public function getAssignee($assigneeID){
        //Filter $this->users and return the user with the matching ID.
    }
}
?>