<?php
class Asana extends AsanaObject {
    private $workspaces,$target_id;
    public function __construct(){
        $interface = $this->getAPI();
        //Get a list of all workspaces from the Asana API, make each one a new Workspace and add it to the $workspaces collection.
        $workspaces_request = $interface->as_get("/workspaces");
        $this->workspaces = json_decode($workspaces_request['contents'])->data;
    }
    private function filter($obj){
        return ($obj->id == $this->target_id);
    }
    public function getWorkspace($workspaceID){
        //Simply filter $this->workspaces and return the one with a workspace ID that matches.
        $this->target_id = $workspaceID;
        $filtered = array_filter($this->workspaces,array($this,"filter"));
        //If filter finds a workspace matching the supplied ID, create a new Workspace object from it and return it.
        //Otherwise, return null.
        if (count($filtered) > 0)
            foreach ($filtered as $workspace)
                return new Workspace($workspace->name,$workspace->id);
        else
            return null;
    }
}
?>