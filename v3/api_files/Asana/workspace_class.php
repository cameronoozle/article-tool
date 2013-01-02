<?php
namespace Asana {
class Workspace extends AsanaObject {
    public $name,$id,$users,$projects;
    private $targ_proj_id, $tasks, $targ_task_id;

    public function __construct($name,$id,$refresh = false){
        //Assign my name and ID.
        $this->name = $name;
        $this->id = $id;
        
        //Set up Asana Interface.
        $interface = $this->getAPI();
        //We can't get a list of tasks for the workspace, because task searches must be limited by assignee in the
        //API, and a list of tasks for a single assignee doesn't really help us at all.        
        
        //Get a list of users and assign it to $this->users;
        $userRequest = $interface->as_get("/workspaces/".$this->id."/users");
        $this->users = array_map(array($this,"assigneeMap"),json_decode($userRequest['contents'])->data);
        
        //Get a list of projects and assign it to $this->projects;
        $projRequest = $interface->as_get("/workspaces/".$this->id."/projects");
        $this->projects = array_map(array($this,"projMap"),json_decode($projRequest['contents'])->data);
        $this->tasks = array();
        if ($refresh){
            $this->refreshTeamMembers();
            $this->refreshProjects();
        }
    }

    private function refreshTeamMembers(){
        foreach ($this->users as $user)
            $user->refresh();
        //This is what a less appropriately object-oriented method would look like. This particular method would require a single query on
        //the workspace level rather than a ton of small queries from the individual user objects.
/*        $db = $this->getDB();
        $query = "INSERT INTO team_members (asana_team_member_id,team_member) VALUES ";
        foreach ($this->users as $user){
            $query .= "(".$user->id.",'".$user->name."'),";
        }
        $db->query(substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE team_member = VALUES (team_member)");*/
    }
    
    private function refreshProjects(){
        foreach ($this->projects as $project)
            $project->refresh();
        //This is what a less appropriately object-oriented method would look like. This particular method would require a single query on
        //the workspace level rather than a ton of small queries from the individual project objects.
/*        $db = $this->getDB();
        $query = "INSERT INTO projects (asana_project_id,project,workspace_id) ";
        $selects = array();
        foreach ($this->projects as $project){
            array_push($selects,"SELECT ".$project->id.",'".$project->name."',workspace_id FROM workspaces WHERE asana_workspace_id = ".$this->id." LIMIT 1");
        }
        $db->query($query.implode(" UNION ",$selects)." ON DUPLICATE KEY UPDATE project = VALUES (project), workspace_id = VALUES (workspace_id)");*/
    }
    
    private function projMap($obj){
        return new Project($obj->name,$obj->id);
    }

    private function assigneeMap($obj){
        return new Assignee($obj->id,$obj->name);
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
                return $project;
        else
            return null;
    }

    public function createProject($projectName){
        //Use the Asana interface to create a new project, turn it into a project object and return it.
        $project = Project::create($projectName,$this->id);
        array_push($this->projects,$project);
        return $project;
    }

    private function filter($obj){
        return $obj->id == $this->targ_task_id;
    }
    
    public function getTask($taskID){
        //If the task is in $this->tasks, return it.
        $this->targ_task_id = $taskID;
        $filtered = array_filter($this->tasks,array($this,"filter"));
        if (count($filtered) > 0)
            foreach ($filtered as $task)
                return $task;

        //Otherwise, create a new Task object using the task ID,
        $task = Task::get($taskID);
        //add it to $this->tasks
        array_push($this->tasks,$task);
        //return it.
        return $task;
    }

    public function addTask(Task $task){
        //Create a new task that is a perfect copy of the one provided.
        $copy = Task::create($task->name,$this->id,$task->assignee,$task->notes);
        //Add the new task to $this->tasks.
        array_push($this->tasks,$copy);
        //Delete the task from the database.
        //Update the task object to make it clear that it has been deleted.
        $task->update("Deleted",null,"");
    }

    public function deleteTask($taskID){
        $this->targ_task_id = $taskID;
        //Delete the task from the database.
        //Remove it from $this->tasks.
        $filtered = array_filter($this->tasks,array($this,"filter"));
        if (count($filtered) > 0){
            foreach ($filtered as $index=>$task){
                //Update the task object to make it clear it has been deleted.
                $task->update("Deleted",null,"");
                array_splice($this->tasks,$index,1);
            }
        }
    }

    public function createTask($name,Assignee $assignee,$notes,$dueDate){
        //Create a new task object assigned to self.
        $task = Task::create($name,$this->id,$assignee,$notes,$dueDate);

        //Add the new task object to $this->tasks.
        array_push($this->tasks,$task);

        //Return the new task.
        return $task;
    }

    private function userFilter($obj){
        return $obj->id == $this->targ_user_id;
    }

    public function getAssignee($assigneeID){
        //Filter $this->users and return the user with the matching ID.
        $this->targ_user_id = $assigneeID;
        $filtered = array_filter($this->users,array($this,"userFilter"));
        if (count($filtered) > 0){
            foreach ($filtered as $user){
                return $user;
            }
        } else {
            return null;
        }
    }
}
}
?>