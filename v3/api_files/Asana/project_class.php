<?php
class Project extends AsanaObject {
    public $id,$name;
    public function __construct($name = "",$id = 0,$workspaceID = 0){
        $caller = $this->caller();
        if ($caller['class'] !== 'Workspace')
            throw new Exception("Only a Workspace object can create a new Project");

        //If the name is "" but an ID is provided, get the Project's information from the Asana API and assign it to the self.
        if ((empty($name))&&($id)&&(!$workspaceID)){
            $interface = $this->getAPI();
            $projRequest = $interface->as_get("/projects/".$id);
            $q = json_decode($projRequest['contents']);
            if (isset($q->data)){
                $this->name = $q->data->name;
                $this->id = $q->data->id;
            } else {
                throw new Exception("Asana Project Details request failed");
            }
        //If the name and workspace are given but no ID is provided, create a new Project via the Asana API and assign its attributes to the self.
        } else if ((!empty($name))&&(!$id)&&($workspaceID)){
            $interface = $this->getAPI();
            $projRequest = $interface->as_post("/projects",json_encode(array(
                "data"=>array(
                    "workspace"=>$workspaceID,
                    "name"=>$name
                )
            )));
            $q = json_decode($projRequest['contents']);
            if (isset($q->data)){
                $this->name = $q->data->name;
                $this->id = $q->data->id;
            } else {
                throw new Exception("Asana Project Creation request failed");
            }
            //Add functionality to save new project in tool database.
            
            
            
        //If both a name and ID are given, assign them both to self.
        } else if ((!empty($name))&&($id)){
            $this->id = $id;
            $this->name = $name;
        //If neither an ID nor a name are given, throw an exception: "Either a name or an ID must be provided to the Project constructor."
        } else {
            throw new Exception("The Project constructor accepts only the following combinations: name & id, name & workspace, or id");
        }
    }
}
?>