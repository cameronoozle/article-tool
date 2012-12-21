<?php
namespace UI {
    class All extends Page {
        public function home(){
            if (\API\All\Users::is_logged()){
                include('templates/pages/home.php');
            } else {
                include('templates/authenticate.php');
            }
        }
    }
}
?>