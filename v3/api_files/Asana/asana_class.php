<?php
namespace API\Asana {
    class Asana extends AsanaObject {
        private $workspaces,$target_id;
        public function __construct($refresh = false){
            $interface = $this->getAPI();
            //Get a list of all workspaces from the Asana API, make each one a new Workspace and add it to the $workspaces collection.
            $workspaces_request = $interface->as_get("/workspaces");
            $this->workspaces = json_decode($workspaces_request['contents'])->data;
            if ($refresh){
                $db = $this->getDB();
                $query = "INSERT INTO workspaces (asana_workspace_id,workspace) VALUES ";
                foreach ($this->workspaces as $workspace){
                    $query .= "(".$db->esc($workspace->id).",'".$db->esc($workspace->name)."'),";
                }
                if (count($this->workspaces) > 0)
                    $db->query(substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE workspace = VALUES (workspace)");
            }
        }
        private function filter($obj){
            return ($obj->id == $this->target_id);
        }
        public function getWorkspace($workspaceID,$refresh = false){
            //Simply filter $this->workspaces and return the one with a workspace ID that matches.
            $this->target_id = $workspaceID;
            $filtered = array_filter($this->workspaces,array($this,"filter"));
            //If filter finds a workspace matching the supplied ID, create a new Workspace object from it and return it.
            //Otherwise, return null.
            if (count($filtered) > 0)
                foreach ($filtered as $workspace)
                    return new Workspace($workspace->name,$workspace->id,$refresh);
            else
                return null;
        }
        public function refresh(){
            foreach ($this->workspaces as $workspace)
                $this->getWorkspace($workspace->id,true);
            return;
        }
    }
}
?>