<?php
class Assignee extends AsanaObject {
    public $id, $name;
    public function __construct($id,$name = ""){
        $this->id = $id;
        //If no name is provided, get a new team member object from the Asana interface.
        if (empty($name)){
            $interface = $this->getAPI();
            $assigneeRequest = $interface->as_get("/users/".$id);
            $q = json_decode($assigneeRequest);
            if (isset($q->data))
                $this->name = $q->data->name;
            else
                throw new Exception("Asana User Details request failed")


        //Otherwise, simply assign the ID and the name to the object and summon it into being.
        } else {
            $this->name = $name;
        }
        
        
    }
}
?>