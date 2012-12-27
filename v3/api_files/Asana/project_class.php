<?php
class Project extends AsanaObject {
    public $id,$name,$tasks;
    
    public static function get($id){
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
        $caller = AsanaObject::caller();
        if (($caller['class'] !== 'Workspace')&&($caller['class'] !== 'Project'))
            throw new Exception("Only a Workspace object can create a new Project");

        $interface = new Asana_API(\API\All\Users::asana_api_key());
        $projRequest = $interface->as_post("/projects",json_encode(array(
            "data"=>array(
                "workspace"=>$workspaceID,
                "name"=>$name
            )
        )));
        $q = json_decode($projRequest['contents']);
        if (isset($q->data)){
            return new Project($q->data->name,$q->data->id);
        } else {
            throw new Exception("Asana Project Creation request failed");
        }
    }
    
    public function __construct($name,$id){
        $this->name = $name;
        $this->id = $id;
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
?>