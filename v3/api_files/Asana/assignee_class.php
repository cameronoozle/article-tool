<?php
class Assignee extends AsanaObject {
    public $id, $name, $tasks;
    
    public static function get($id){
        $interface = new Asana_API(\API\All\Users::asana_api_key());
        $assigneeRequest = $interface->as_get("/users/".$id);
        $q = json_decode($assigneeRequest['contents']);
        if (isset($q->data))
            return new Assignee($id,$q->data->name);
        else
            throw new Exception("Asana User Details request failed.");
    }
    
    public function getTasks($workspaceID){
        $interface = $this->getAPI(\API\All\Users::asana_api_key());
        $taskRequest = $interface->as_get("/workspaces/".$workspaceID."/tasks",array("assignee"=>$this->id));
        $d = json_decode($taskRequest['contents']);
        if (isset($d->data)){
            $this->tasks = $d->data;
            return $this->tasks;
        } else {
            throw new Exception("The task search request failed");
        }
    }
    
    public function __construct($id,$name = ""){
        $this->id = $id;
        $this->name = $name;        
    }
}
?>