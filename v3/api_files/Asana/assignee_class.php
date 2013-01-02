<?php
namespace Asana {
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
    
    public function __construct($id,$name = "",$refresh=false){
        $this->id = $id;
        $this->name = $name;
        if ($refresh)
            $this->refresh();
    }
    
    public function refresh(){
        $db = $this->getDB();
        $query = "INSERT INTO as-users (id,name) VALUES (".$db->esc($this->id).",'".$db->esc($this->name)."') ON DUPLICATE KEY UPDATE name=VALUES(name)";
        $db->query($query);
    }
    
}
}
?>