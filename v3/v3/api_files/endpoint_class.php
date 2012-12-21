<?php
class Endpoint {
	var $db;
	protected $handy;
	protected $max_results;
	protected $parameters;
	
	public function __construct($parameters,$max_results = 25){
		$this->parameters = $parameters;
		$this->max_results = $max_results;
	}
	
	protected function get_db(){
		if (!isset($this->db)) $this->db = new DB(SERVER,USER,PW,DB);
		$this->db->addListener($this);
		return $this->db;
	}
	public function onSQLError(){
		echo json_encode($this->error($this->db->errors));
		exit;
	}
	protected function handy(){
		if (!isset($this->handy)) $this->handy = new Handy($this->parameters);
		return $this->handy;
	}
	
	function validate_callback($required_parameters,$parameters,$errors){
		foreach ($required_parameters->all as $param_name=>$type){
			if (!isset($parameters[$param_name])){
				array_push($errors,"Missing required parameter: ".$param_name);
			} else if (!Types::matches_type($parameters[$param_name],$type)){
				array_push($errors,"Value of ".$param_name." is invalid");
			}
		}
		if (count($required_parameters->one_many_all) > 0){
			foreach ($required_parameters->one_many_all as $param_name=>$type){
				if (isset($parameters[$param_name]))
					$at_least_one_match = true;
			}
		} else {
			$at_least_one_match = true;
		}
		if (!isset($at_least_one_match)){
			array_push($errors,"Query must contain at least one of the following parameters: ".Handy::implode_keys(",",$required_parameters->one_many_all));
		}
		return $errors;
	}
	
	protected function validate($required_parameters,$allow_multidimensional = false,$permission_symbol,$mustlog = true){
		if (($mustlog)&&(!isset($_SESSION['oozledash'])))
			return $this->error(array("You must be logged in to access this method."));
		if (($mustlog)&&(!$permission_symbol->has_permission()))
			return $this->error(array("You do not have permission to access this method."));
		$errors = array();
		$i=0;
		if ((!$allow_multidimensional)&&(Array_Manager::is_multidimensional($this->parameters))){
			array_push($errors,"This method does not support multidimensional inputs");
		}
		if (($allow_multidimensional)&&(Array_Manager::is_multidimensional($this->parameters))){
			foreach (\Handy::objectToArray($this->parameters) as $level){
				$errors = $this->validate_callback($required_parameters,$level,$errors);
			}
		} else {
			$errors = $this->validate_callback($required_parameters,$this->parameters,$errors);
		}
		return (count($errors) == 0 ? true : $errors);
	}
	
	protected function pagify(){
		if ((isset($this->parameters['limit']))&&($this->parameters['limit'] <= $this->max_results))
			$limit = $this->parameters['limit'];
		else
			$limit = $this->max_results;
		if ((isset($this->parameters['page']))&&($this->parameters['page'] > 0)){
			if ((isset($this->parameters['limit']))){
				$page = $this->parameters['limit'] * ($this->parameters['page'] - 1);
			} else {
				$page = $this->max_results * ($this->parameters['page'] - 1);
			}
		} else {
			$page = 0;
		}
		return "LIMIT ".$page.", ".$limit;
	}
	
	protected function get_id($field,$parameters = null,$alias = null){
		if ($alias === null) $alias = $field;
		if (($parameters === null)&&(isset($this->parameters))) $parameters = $this->parameters;
		if (isset($parameters[$field])){
			$db = $this->get_db();
			$query = "SELECT ".$field."_id FROM ".$field."s WHERE ".$field." = ".Handy::str_encase($db->esc($parameters[$alias]))." LIMIT 1";
			$data = $db->query($query);
			if (count($data['rows']) > 0){
				return $data['rows'][0][$field."_id"];
			} else {
				$query = "INSERT INTO ".$field."s (".$field.") VALUES (".Handy::str_encase($db->esc($parameters[$field])).")";
				$data = $db->query($query);
				return $data['insert_id'];
			}
		} else {
			return "null";
		}
	}
	
	private function parameters($parameters){
		return ((($parameters === null)&&(isset($this->parameters))) ? $this->parameters : $parameters);
	}
	
	protected function wrtm_id($parameters = null){
		$parameters = $this->parameters($parameters);
		if ((isset($parameters['writer']))){
			$wr = new Writing_teams();
			$teams = $wr->search(array("writing_team"=>$parameters['writer'],"limit"=>1,"lite"=>"true"));
			return (count($teams) > 0 ? $teams[0]['writing_team_id'] : "null");
		} else if (isset($parameters['writing_team'])){
			$wr = new Writing_teams();
			$teams = $wr->search(array("writing_team"=>$parameters['writing_team'],"limit"=>1,"lite"=>"true"));
			return (count($teams) > 0 ? $teams[0]['writing_team_id'] : "null");			
		} else {
			return "null";
		}
	}
	
	protected function pjct_id($parameters = null){
		$parameters = $this->parameters($parameters);
		if (isset($parameters['project'])){
			$pr = new Projects();
			$projects = $pr->search(array("project"=>$parameters['project'],"limit"=>1,"lite"=>"true"));
			return (count($projects) > 0 ? $projects[0]['project_id'] : "null");
		} else {
			return "null";
		}
	}
	
	protected function cl_id($parameters = null){
		$parameters = $this->parameters($parameters);
		if (isset($parameters['client'])){
			$cl = new Clients();
			$clients = $cl->search(array("client"=>html_entity_decode($parameters['client']),"limit"=>1,"lite"=>"true"));
			return (count($clients) > 0 ? $clients[0]['client_id'] : "null");
		} else {
			return "null";
		}
	}
	
	public static function success($arr){
		return Handy::objectify(array("status"=>"success","data"=>$arr));
	}
	public static function error($arr){
		return Handy::objectify(array("status"=>"error","data"=>$arr));
	}
	public function validate_output($rp,$am,$permsymbol,$cb,$mustlog = true){
		$valid = $this->validate($rp,$am,$permsymbol,$mustlog);
		if ($valid === true){
			return call_user_func($cb);
		} else {
			return $this->error(array("errors"=>$valid,"parameters"=>$this->parameters,"x"=>file_get_contents("php://input")));
		}
	}
	
}

?>