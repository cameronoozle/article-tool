<?php
namespace UI {
    class Web_Development extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }

        public function permissions(){
            $this->permissions_callback(2,"Web Development");
        }
    }
}
?>