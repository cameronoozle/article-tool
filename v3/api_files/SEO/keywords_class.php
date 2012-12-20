<?php
namespace API\SEO {
    class Keywords extends \Endpoint {
	private $updatequery;
        public function __construct($parameters){
            parent::__construct($parameters);
        }
	
	//The save method allows you to update existing keywords as they are associated with a client, but does not allow you to add new ones.
	//If you want to add a new keyword, you have to add a new article and associate it with the desired keyword.
	//There's just not much utility for an add-keywords method right now.
        public function save(){
            $reqs = new \Required_Parameters(array(),array("client_id"=>\Types::Int,"keyword_id"=>\Types::Int,"keyword"=>\Types::String));
            return $this->validate_output($reqs,true,new \Permission(2,"SEO"),array($this,"save_callback"));
        }
        public function save_callback(){
	    $db = $this->get_db();

	    //This query updates the keyword from one to another for a given client with a given keyword. For example, I can change the keyword
	    //for all articles with the client "Brillare" and the keyword "beauty schools in mesa az" to "cosmetology in mesa".
	    $this->updatequery = "UPDATE articles SET keyword_id = CASE ";

	    if (\Array_Manager::is_multidimensional($this->parameters)){
		foreach ($this->parameters as $parameters){
		    $this->save_callback2($parameters);
		}
	    } else {
		$this->save_callback2($this->parameters);
	    }

	    $db->query($this->updatequery." END");
        }
        
	public function save_callback2($parameters){
	    $db = $this->get_db();

	    //Attempt to insert a new keyword into the keywords table:
	    $query2 = "INSERT IGNORE INTO keywords (keyword) VALUES ('".$db->esc($parameters['keyword'])."')";
	    $d = $db->query($query2);

	    //If the keyword was inserted, save its ID. If it wasn't inserted, it must already be in there, so select its ID from the table.
	    if ($d['affected_rows'] > 0){
		$id = $d['insert_id'];
	    } else {
		$query2 = "SELECT keyword_id FROM keywords WHERE keyword = '".$db->esc($parameters['keyword'])."'";
		$id = $d['rows'][0]['keyword_id'];
	    }

	    //Append to our ultimate query: Where the keyword is x and the client is y, set the keyword to z.
	    $this->updatequery .= "WHEN keyword_id = ".$db->esc($parameters['keyword_id'])." AND client_id = ".$db->esc($parameters['client_id'])." THEN ".$db->esc($id)." ";
	}
        
        public function search(){
            $reqs = new \Required_Parameters(array(),array("client_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"SEO"),array($this,"search_callback"));
        }
        public function search_callback(){
	    $db = $this->get_db();
	    $query = "SELECT COUNT(article_id) num_articles, client,client_id,keyword,keyword_id FROM articles LEFT JOIN keywords USING (keyword_id) LEFT JOIN clients USING (client_id) WHERE client_id = ".$db->esc($this->parameters['client_id'])." GROUP BY keyword_id ORDER BY keyword";
	    $d = $db->query($query);
	    return $this->success($d['rows']);
	    
	    
	    
	    
	    
	    
	    
/*            $db = $this->get_db();
            $opts = array("tag_id","keyword_id","client_id");
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
	    echo $query."\n\n";
            $d = $db->query($query);
            $obj = array("num_results"=>$db->found_rows(),"rows"=>$d['rows']);
            return $this->success($obj);*/
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