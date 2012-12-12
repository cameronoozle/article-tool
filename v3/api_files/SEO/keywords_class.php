<?php
namespace API\SEO {
    class Keywords extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function floggingmolly(){
            $d = $this->get_db()->query("SELECT DISTINCT keyword FROM keywords");
            return $this->success($d['rows']);
        }
        
/*        public function snag(){
            $r = new \Requester();
            $d = $r->get("http://oozlemedia.net/seo/v2/api/keywords/floggingmolly");
            $data = json_decode($d['contents']);
            $db = $this->get_db();
            $query = "INSERT IGNORE INTO keywords (keyword) VALUES ";
            $inserts = array();
            foreach ($data->data as $row){
                array_push($inserts,"('".$db->esc($row->keyword)."')");
            }
            $query = $query.implode(",",$inserts);
            $db->query($query);
            return $this->success(array("All of this."));
	}*/

        
        public function save(){
            $reqs = new \Required_Parameters(array(),array("keyword"=>\Types::String));
            return $this->validate_output($reqs,true,new \Permission(2,"SEO"),array($this,"save_callback"));
        }
        public function save_callback(){
            $db = $this->get_db();
            $query = "INSERT INTO keywords (keyword_id,keyword) VALUES ";
            $inarray = array();
            if (\Array_Manager::is_multidimensional($this->parameters)){
                foreach ($this->parameters as $parameters){
                    array_push($inarray,$this->save_callback2($parameters));
                }
            } else {
                array_push($inarray,$this->save_callback2($this->parameters));
            }
            if (count($inarray) > 0){
                $d = $db->query($query.implode(",",$inarray)." ON DUPLICATE KEY UPDATE keyword = VALUES (keyword)");
            }
        }
        
        public function save_callback2($parameters){
            $db = $this->get_db();
            $handy = $this->handy();
            return "(".$this->handy()->val_or_null('keyword_id',$parameters).",".$db->esc($parameters['keyword']).")";
        }
        
        public function search(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(2,"SEO"),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            $opts = array("tag_id","keyword_id");
            $wheres = array();
            foreach ($opts as $opt){
                if ((isset($this->parameters[$opt]))&&(is_numeric($this->parameters[$opt]))){
                    array_push($wheres,$opt." = '".$db->esc($this->parameters[$opt])."'");
                }
            }
            if (isset($this->parameters['client_id'])){
                $query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS keyword_id,keyword_tag_pairing_id,keyword,tag,tag_id,client_id,client FROM articles ".
                "LEFT JOIN keywords USING (keyword_id) ".
                "LEFT JOIN keyword_tag_pairings USING (keyword_id) ".
                "LEFT JOIN tags USING (tag_id)".
                "LEFT JOIN clients USING (client_id) WHERE client_id = ".$db->esc($this->parameters['client_id']);
            } else {
                $query = "SELECT SQL_CALC_FOUND_ROWS * FROM keywords ".
                                "LEFT JOIN keyword_tag_pairings USING (keyword_id) ".
                                "LEFT JOIN tags USING (tag_id) ".
                                "LEFT JOIN clients USING (client_id) ".
                                (count($wheres) > 0 ? "WHERE ".implode(" AND ",$wheres) : "").
                                " ORDER BY client,keyword,tag ".$this->pagify();
                }
            $d = $db->query($query);
            $obj = array("num_results"=>$db->found_rows(),"rows"=>$d['rows']);
            return $this->success($obj);
        }
        
        public function add_tag(){
            $reqs = new \Required_Parameters(array("keyword_id"=>\Types::Int,"tag_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"SEO"),array($this,"add_tag_callback"));
        }
        public function add_tag_callback(){
            $db = $this->get_db();
            $d = $db->query("INSERT IGNORE INTO keyword_tag_pairings (keyword_id,tag_id) VALUES (".$db->esc($this->parameters['keyword_id']).",".$db->esc($this->parameters['tag_id']).")");
            if ($d['affected_rows'] > 0){
                return $this->success(array("keyword_tag_pairing_id"=>$d['insert_id']));
            } else {
                $d = $db->query("SELECT keyword_tag_pairing_id FROM keyword_tag_pairings WHERE keyword_id = ".$db->esc($this->parameters['keyword_id'])." AND tag_id = ".$db->esc($this->parameters['tag_id']));
                if (count($d['rows']) > 0){
                    return $this->success(array("keyword_tag_pairing_id"=>$d['rows'][0]['keyword_tag_pairing_id']));
                }
                return $this->error(array("Something went wrong."));
            }
        }
        public function delete_tag(){
            $reqs = new \Required_Parameters(array("keyword_tag_pairing_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"SEO"),array($this,"delete_tag_callback"));
        }
        private function delete_tag_callback(){
            $db = $this->get_db();
            $d = $db->query("DELETE FROM keyword_tag_pairings WHERE keyword_tag_pairing_id = ".$db->esc($this->parameters['keyword_tag_pairing_id']));
            return $this->success(array("Tag successfully deleted."));
        }
    }
}
?>