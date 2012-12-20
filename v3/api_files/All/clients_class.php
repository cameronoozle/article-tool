<?php
namespace API\All {
    class Clients extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        //The save function changes the SEO percentage for one or multiple clients.
        public function save(){
            $reqs = new \Required_Parameters(array(),array("client_budget_id"=>\Types::Int,"seo_percentage"=>\Types::Float));
            return $this->validate_output($reqs,true,new \Permission(1,"SEO"),array($this,"save_callback"));
        }
        public function save_callback(){
            $db = $this->get_db();
            
            //Prepare the query. Update the SEO percentage on a client-by-client basis. If more than one client is specified,
            //prepare to update the SEO percentage for each one.
            $query = "UPDATE client_budgets SET seo_percentage = CASE (client_budget_id) ";
            if (\Array_Manager::is_multidimensional($this->parameters)){
                foreach ($this->parameters as $parameters){
                    $query .= "WHEN ".$parameters['client_budget_id']." THEN ".$parameters['seo_percentage']." ";
                }
            } else {
                $query .= "WHEN ".$this->parameters['client_budget_id']." THEN ".$this->parameters['seo_percentage']." ";
            }

            //Ensure that it doesn't set any unspecified SEO percentages to null and end the query.
            $query .= "ELSE seo_percentage END";
            
            //Execute and return success.
            $db->query($query);
            return $this->success(array("Budgets successfully updated."));
        }
        
        //Get the budgets and names for each client within a given department.
        public function search(){
            $reqs = new \Required_Parameters(array("department_id"=>\Types::Int,"department"=>\Types::String),array());
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"search_callback"));
        }
        public function search_callback(){
            $db = $this->get_db();
            
            //This first part has mostly to do with making sure the user has permissions within the department within which they want to search.
            //We'll use another Permission object to make sure that this is the case.
            if (!isset($this->parameters['department'])){

                //The Permission object requires a string-type department name in order to verify the user's permission level, so first thing we'll have
                //to get the department name if they've only provided a department ID.
                $query = "SELECT department FROM departments WHERE department_id = ".$db->esc($this->parameters['department_id']);
                $d = $db->query($query);

                if (count($d['rows']) > 0){
                    
                    //Set up the Permission object.
                    $this->parameters['department'] = $d['rows'][0]['department'];
                    $perm = new \Permission(1,$d['rows'][0]['department']);
                } else {
                    return $this->error(array("This department does not exist."));
                }
            } else {

                //We'll be executing a query later that requires the department ID, so, if the user hasn't provided said ID, we'll need to go get it.
                if (!isset($this->parameters['department_id'])) {
                    $query = "SELECT department_id FROM departments WHERE department = '".$db->esc($this->parameters['department'])."'";
                    $d = $db->query($query);

                    if (count($d['rows']) > 0){
                        $this->parameters['department_id'] = $d['rows'][0]['department_id'];
                    } else {
                        return $this->error(array("This department does not exist."));
                    }
                }

                //Set up the permission object.
                $perm = new \Permission(1,$this->parameters['department']);
            }

            //If the user has the appropriate permissions, get the client data they've been asking for.
            if ($perm->has_permission()){
                $query = "SELECT * FROM clients ".
                "LEFT JOIN client_service_pairings USING (client_id) ".
                "LEFT JOIN service_department_pairings USING (service_id) ".
                "LEFT JOIN client_budgets USING (client_id) ".
                "WHERE department_id = ".$db->esc($this->parameters['department_id']).(isset($this->parameters['month']) ? " AND month='".$this->parameters['month']."'" : "").
                " GROUP BY client_id ORDER BY client";
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
        
        //The refresh method interfaces with Sugar to modify the tool's database, specifically to:
        //import client budgets,
        //delete outdated client-service pairings,
        //and add new clients and client service pairings
        public function refresh(){
            //We run this function using a cron job, so you don't have to log in to use it.
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $db = $this->get_db();
            
            //First, get a list of all current clients and client service pairings in the tool's database.
            //Call this list "db_client_services".
            $d = $db->query("SELECT sugar_client_id,client,service FROM client_service_pairings LEFT JOIN clients USING (client_id) LEFT JOIN services USING (service_id)");
            $this->db_client_services = $d['rows'];
            
            //Set up the Sugar request. Right now, the request uses Cameron's credentials, which we may want to change in the future.
            //The request gets an unfiltered list consisting of all information for all clients from Sugar.
            $select_fields = array();
            $sugar = new \Sugar_API("cameron","Monk2ey");
            $params = array('module_name'=>'Accounts','query'=>'','order_by'=>'','offset'=>'0','select_fields'=>array(),'link_name_to_fields_array'=>array(array('name'=>'name')),'max_results'=>'900','Favorites'=>'false');
            $data = $sugar->sug_post("get_entry_list",$params);            
            $list = json_decode($data['contents']);

            //Prepare our queries. One adds new clients, one adds new clients budgets, and one adds new services for clients.
            $query = "INSERT INTO clients (client,sugar_client_id) VALUES ";
            $query2 = "INSERT INTO client_budgets (client_id,budget,month)";
            $query3 = "INSERT IGNORE INTO client_service_pairings (client_id,service_id) ";

            //We're using "INSERT... SELECT... UNION" syntax for two of these queries, so we'll simplify our lives by creating arrays full of select queries
            //which we'll implode into unioned selects at the end.
            $selects = array();
            $selects2 = array();
            
            //For each client pulled from Sugar...
            foreach ($list->entry_list as $entry){
                    $nvl = $entry->name_value_list; //The field in the Sugar list with all of the specific data for the client.
                    $query .= "('".$db->esc($nvl->name->value)."','".$db->esc($entry->id)."'),"; //Add the client to our database.
                    $seo_amount = $nvl->seo_amount_c->value;
                    
                    //Add/Update the client's budget in the tool's database.
                    array_push($selects, "SELECT client_id,'".number_format(floatval($seo_amount),2,'.','')."','".date("Y-m-d H:i:s",mktime(0,0,0,date("n"),1,date("Y")))."' ".
                            "FROM clients WHERE sugar_client_id='".$db->esc($entry->id)."'");
                    
                    //Add each of the clients services to the tool's database.
                    $services = explode(",",$nvl->services_c->value);
                    foreach ($services as $service){
                        $stripped = str_replace("^","",$service);
                        if (!empty($stripped)){
                            array_push($selects2,"SELECT client_id,(SELECT service_id FROM services WHERE service = '".$db->esc($stripped)."') FROM clients WHERE client='".$db->esc($nvl->name->value)."'");
                            $current_index = $this->refresh_filter($entry->id,$stripped);
                            
                            //Remove the current client-service pairing from "db_client_services".
                            array_splice($this->db_client_services,$current_index,1);
                        }
                    }
            }
            //Insert all clients. If we already have the client for a given Sugar ID, change the client's name to reflect what's in Sugar.
            $query = substr_replace($query,"",-1,1)." ON DUPLICATE KEY UPDATE client = VALUES (client)";
            
            //Insert/Update all client budgets in the tool's database.
            $query2 = $query2.implode(" UNION ",$selects)." ON DUPLICATE KEY UPDATE budget=VALUES(budget),seo_percentage=VALUES(seo_percentage)";
            
            //Insert all new service-pairings in the tool's database.
            $query3 = $query3.implode(" UNION ",$selects2);

            //Execute the queries.
            $data = $db->query($query); $data2 = $db->query($query2);   $data3 = $db->query($query3);

            //If there are still any client-service pairings left in the "db_client_services" list, they must have been deleted from Sugar
            //since our last refresh. This section deletes them from the database.
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