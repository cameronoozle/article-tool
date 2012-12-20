<?php
namespace API\All {
    class Clients extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        public function vampire(){
            $db = $this->get_db();
            $r = new \Requester();
            $d = $r->get("http://oozlemedia.net/seo/v2/api/clients/dump");
            $info = json_decode($d['contents']);
            $query = "INSERT IGNORE INTO client_budgets VALUES ";
            foreach ($info->data as $row){
                $query .= "(";
                foreach ($row as $item){
                    $query .= "'".$db->esc($item)."',";
                }
                $query = substr_replace($query,"),",-1);
            }
            $db->query(substr_replace($query,"",-1,1));
        }
        
        
        public function save(){
            $reqs = new \Required_Parameters(array(),array("client_budget_id"=>\Types::Int,"seo_percentage"=>\Types::Float));
            return $this->validate_output($reqs,true,new \Permission(1,"SEO"),array($this,"save_callback"));
        }
        public function save_callback(){
            $db = $this->get_db();
            $query = "UPDATE client_budgets SET seo_percentage = CASE (client_budget_id) ";
            if (\Array_Manager::is_multidimensional($this->parameters)){
                foreach ($this->parameters as $parameters){
                    $query .= "WHEN ".$parameters['client_budget_id']." THEN ".$parameters['seo_percentage']." ";
                }
            } else {
                $query .= "WHEN ".$this->parameters['client_budget_id']." THEN ".$this->parameters['seo_percentage']." ";
            }
            $query .= "END";
            $db->query($query);
            return $this->success(array("Budgets successfully updated."));
        }
        public function search(){
            $reqs = new \Required_Parameters(array("department_id"=>\Types::Int,"department"=>\Types::String),array());
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            if (!isset($this->parameters['department'])){
                $query = "SELECT department FROM departments WHERE department_id = ".$db->esc($this->parameters['department_id']);
                $d = $db->query($query);
                if (count($d['rows']) > 0){
                    $this->parameters['department'] = $d['rows'][0]['department'];
                    $perm = new \Permission(1,$d['rows'][0]['department']);
                } else {
                    return $this->error(array("This department does not exist."));
                }
            } else {
                if (!isset($this->parameters['department_id'])) {
                    $query = "SELECT department_id FROM departments WHERE department = '".$db->esc($this->parameters['department'])."'";
                    $d = $db->query($query);
                    if (count($d['rows']) > 0){
                        $this->parameters['department_id'] = $d['rows'][0]['department_id'];
                    } else {
                        return $this->error(array("This department does not exist."));
                    }
                }
                $perm = new \Permission(1,$this->parameters['department']);
            }
            if ($perm->has_permission()){ //client_id,client,budget,seo_percentage 
                // AND month='".$db->esc($this->parameters['month'])."'
                $query = "SELECT * FROM clients LEFT JOIN client_service_pairings USING (client_id) LEFT JOIN service_department_pairings USING (service_id) LEFT JOIN client_budgets USING (client_id) WHERE department_id = ".$db->esc($this->parameters['department_id']).(isset($this->parameters['month']) ? " AND month='".$this->parameters['month']."'" : "")." GROUP BY client_id ORDER BY client";
                $d = $db->query($query);
                return $this->success($d['rows']);
            } else {
                return $this->error(array("You do not have permission to access this department."));
            }
            //Show only clients to which the user has access.
        }
        private function refresh_filter($sugar_client_id,$service){
            foreach ($this->db_client_services as $n=>$v){
                if (($v['sugar_client_id'] == $sugar_client_id)&&($v['service'] == $service)){
                    return $n;
                }
            }
            return -1;
        }
        private $db_client_services;
        
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT sugar_client_id,client,service FROM client_service_pairings LEFT JOIN clients USING (client_id) LEFT JOIN services USING (service_id)");
            $this->db_client_services = $d['rows'];
            $select_fields = array();
            $sugar = new \Sugar_API("cameron","Monk2ey");
            $params = array('module_name'=>'Accounts','query'=>'','order_by'=>'','offset'=>'0','select_fields'=>array(),'link_name_to_fields_array'=>array(array('name'=>'name')),'max_results'=>'900','Favorites'=>'false');
            $data = $sugar->sug_post("get_entry_list",$params);
            
            $list = json_decode($data['contents']);
            $query = "INSERT INTO clients (client,sugar_client_id) VALUES ";
            $query2 = "INSERT INTO client_budgets (client_id,budget,month)";
            $query3 = "INSERT IGNORE INTO client_service_pairings (client_id,service_id) ";
            $selects = array();
            $selects2 = array();
            foreach ($list->entry_list as $entry){
                    $nvl = $entry->name_value_list;
                    $query .= "('".$db->esc($nvl->name->value)."','".$db->esc($entry->id)."'),";
                    $seo_amount = $nvl->seo_amount_c->value;
                    array_push($selects, "SELECT client_id,'".number_format(floatval($seo_amount),2,'.','')."','".date("Y-m-d H:i:s",mktime(0,0,0,date("n"),1,date("Y")))."' ".
                            "FROM clients WHERE sugar_client_id='".$db->esc($entry->id)."'");
                    $services = explode(",",$nvl->services_c->value);
                    foreach ($services as $service){
                        $stripped = str_replace("^","",$service);
                        if (!empty($stripped)){
                            array_push($selects2,"SELECT client_id,(SELECT service_id FROM services WHERE service = '".$db->esc($stripped)."') FROM clients WHERE client='".$db->esc($nvl->name->value)."'");
                            $current_index = $this->refresh_filter($entry->id,$stripped);
                            array_splice($this->db_client_services,$current_index,1);
                        }
                    }
            }
            $query = substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE client = VALUES (client)";
            $query2 = $query2.implode(" UNION ",$selects)." ON DUPLICATE KEY UPDATE budget=VALUES(budget),seo_percentage=VALUES(seo_percentage)";
            $query3 = $query3.implode(" UNION ",$selects2);
            $data = $db->query($query);
            $data2 = $db->query($query2);
            $data3 = $db->query($query3);
            $deletewheres = array();
            $deletequery = "DELETE FROM client_service_pairings WHERE ";
            foreach ($this->db_client_services as $service){
                array_push($deletewheres,"(client_id=(SELECT client_id FROM clients WHERE sugar_client_id='".$service['sugar_client_id']."') AND service_id=(SELECT service_id FROM services WHERE service='".$service['service']."'))");
            }
            $deletequery .= implode(" OR ",$deletewheres);
            if (count($deletewheres) > 0) $db->query($deletequery);
            return $this->success(array("Clients successfully refreshed."));
        }
    }
}
?>