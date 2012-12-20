<?php
namespace UI {
    class PPC extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }

        public function permissions(){
            $this->permissions_callback(1,"PPC");
        }
    }
}
?>