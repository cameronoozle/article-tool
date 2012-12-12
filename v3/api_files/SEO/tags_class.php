<?php
namespace API\SEO {
    class Tags extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function save(){
            $reqs = new \Required_Parameters(array("tag"=>\Types::String));
            return $this->validate_output($reqs,false,new \Permission(2,"SEO",array($this,"create_tag_callback")));
        }
        
        public function save_callback(){
            $db = $this->get_db();
            $d = $db->query("INSERT IGNORE INTO tags (tag) VALUES (".$db->esc($this->parameters['tag']).")");
            if ($d['affected_rows'] > 0){
                return $this->success(array("tag_id"=>$d['insert_id']));
            } else {
                $d = $db->query("SELECT tag_id FROM tags WHERE tag = '".$db->esc($this->parameters['tag']));
                if (count($d['rows']) > 0)
                    return $this->success(array("tag_id"=>$d['rows'][0]['tag_id']));
                else
                    return $this->error(array("Something went wrong."));
            }
        }

    }
}
?>