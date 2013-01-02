<?php
namespace Sugar {
    class SugarObject {
        private $db;
        protected function getAPI(){
            //Retrieve an interface object. I'm not sure if this really works the way the Sugar API is set up.
        }
        protected function getDB(){
            if (!isset($this->db))
                $this->db = new DB(SERVER,USER,PW,DB);
            return $this->db;
        }
    }
    class Sugar extends SugarObject {
        private $accounts;
        public function __construct($api_key){
            
        }
        public function getAccount($id){
            foreach ($this->accounts as $account){
                
            }
        }
    }
    
    class Account extends SugarObject {
        private $id, $name, $services;
        public static function get($id){
            //Retrieve account from the Sugar API.
            //Return a new Account object from your retrieved data.
        }
        private function __construct($id,$name){
            $this->id = $id;
            $this->name = $name;
        }
        public function addService($service){
            array_push($this->services,$service);
        }
    }
    class Service extends SugarObject {
        private $id, $name;
    }
    class Budget extends SugarObject {
        private $id, $month, $budget, $seo_amount;
    }
}
?>