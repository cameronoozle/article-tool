<?php
class AsanaObject {
    protected $api;
    protected function getAPI(){
        if (!isset($this->api))
            $this->api = new Asana_API(\API\All\Users::asana_api_key());
        return $this->api;
    }
    protected function getDB(){
        static $dbObj = null;
        if ($dbObj == null)
            $dbObj = new DB(SERVER,USER,PW,DB);
        return $dbObj;
    }
    protected function caller(){
        $backtrace = debug_backtrace();
        return $backtrace[2];
    }
}
?>