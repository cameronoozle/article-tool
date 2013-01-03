<?php
namespace API\Asana {
class Project extends AsanaObject {
    public $id,$name,$tasks;
    
    public static function get($id,$refresh){
        $interface = new Asana_API(\API\All\Users::asana_api_key());
        $projRequest = $interface->as_get("/projects/".$id);
        $q = json_decode($projRequest['contents']);
        if (isset($q->data)){
            return new Project($this->name,$this->id);
        } else {
            throw new Exception("Asana Project Details request failed");
        }
    }
    public static function create($name,$workspaceID){
        //See to it that only workspaces can create new projects.
        $caller = AsanaObject::caller();
        if (($caller['class'] !== 'Workspace')&&($caller['class'] !== 'Project'))
            throw new Exception("Only a Workspace object can create a new Project");

        //Set up and execute the Asana request.
        $interface = new Asana_API(\API\All\Users::asana_api_key());
        $projRequest = $interface->as_post("/projects",json_encode(array(
            "data"=>array(
                "workspace"=>$workspaceID,
                "name"=>$name
            )
        )));
        $q = json_decode($projRequest['contents']);

        //Save it in the database and return the new project.
        if (isset($q->data)){
            $query = "INSERT IGNORE INTO projects (project,asana_project_id,department_id,workspace_id) ".
            "SELECT '".$project->name."',".$db->esc($project->id).",department_id,".$workspaceID." FROM departments ".
            "LEFT JOIN department_workspace_pairings USING (department_id) WHERE workspace_id = ".$workspaceID;
            echo $query;
            return new Project($q->data->name,$q->data->id,true);
        } else {
            throw new Exception("Asana Project Creation request failed");
        }
    }
    
    public function __construct($name,$id,$refresh = false){
        $this->name = $name;
        $this->id = $id;
        if ($refresh)
            $this->refresh();
    }
    public function refresh(){
        $db = $this->getDB();
        $query = "INSERT INTO projects (asana_project_id,project) VALUES (".$db->esc($this->id).",'".$db->esc($this->name)."') ".
        "ON DUPLICATE KEY UPDATE project = VALUES (project)";
        $db->query($query);
    }
    
    public function getTasks(){
        $interface = $this->getAPI();
        $taskRequest = $interface->as_get("/projects/".$this->id."/tasks");
        $d = json_decode($taskRequest['contents']);
        if (isset($d->data)){
            $this->tasks = $d->data;
            return $this->tasks;
        } else {
            throw new Exception("The task search request failed");
        }
    }
}
}
?>