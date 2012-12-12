<?php
namespace API\SEO {
    class Checklists extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function save(){
            $reqs = new \Required_Parameters(array("checklist_item_id"=>\Types::Int,"client_id"=>\Types::Int));
            return $this->validate_output($reqs,true,new \Permission(1,"SEO"),array($this,"save_callback"));
        }
        public function save_callback(){
            $db = $this->get_db();
            $query = "INSERT IGNORE INTO checklist_instances (checklist_item_id,client_id,completed,notes) VALUES ";
            if (\Array_Manager::is_multidimensional($this->parameters)){
                foreach ($this->parameters as $parameters){
                    $query .= $this->save_callback2($parameters);
                }
            } else {
                $query .= $this->save_callback2($this->parameters);
            }
            $d = $db->query(substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE date_last_updated=NOW()");
            $query = "SELECT * FROM checklist_instances WHERE date_last_updated = (SELECT MAX(date_last_updated) FROM checklist_instances)";
            $d = $db->query($query);
            return $this->success($d['rows']);
        }
        private function save_callback2($parameters){
            $db = $this->get_db();
            $handy = $this->handy();
            return "(".$db->esc($parameters['checklist_item_id']).",".
            $db->esc($parameters['client_id']).",".$handy->val_or_null('completed',$parameters).",".
            $handy->val_or_null('notes',$parameters)."),";
        }
        public function search(){
            $reqs = new \Required_Parameters(array(),array("client_id"=>\Types::Int,"month"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,"SEO"),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM checklist_items ".
                "LEFT JOIN (SELECT * FROM checklist_instances WHERE client_id = ".$db->esc($this->parameters['client_id']).") checklist_instances USING (checklist_item_id) ".
                "LEFT JOIN clients USING (client_id) ".
                "LEFT JOIN tasks USING (task_id) ".
                "LEFT JOIN (
                    SELECT checklist_item_id parent_item_id,checklist_item parent_item FROM checklists
                ) parent_items ON parent_items.parent_item_id = checklist_items.parent_id ".
                "WHERE month = ".$db->esc($this->parameters['month_id']));
            return $this->success($d['rows']);
        }
        public function add_item(){
            $reqs = new \Required_Parameters(array("checklist_item"=>\Types::String,"month"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"SEO"),array($this,"add_item_callback"));
        }
        public function add_item_callback(){
            $db = $this->get_db();
            $d = $db->query("INSERT IGNORE INTO checklist_items (checklist_item,parent_id,month) VALUES ".
                "(".$db->esc($this->parameters['checklist_item']).",".
                (!empty($this->parameters['parent_id']) ? $db->esc($this->parameters['parent_id']) : "null").",".
                $this->parameters['month'].")");
            return $this->success(array("Checklist item added."));
        }
    }
}
?>