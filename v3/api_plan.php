<?php
namespace API {

}
namespace API\All {
    class Users {}
    class Modules {}
    class Departments {}
    class Team_members {}
}
namespace API\Content {
    class Articles {}
}
namespace API\SEO {
    class Clients {}
    class Keywords {}
}
namespace UI {
    class Model {}
    class View {}
    class Controller {
        public function __construct(){
            if ($this->model->is_logged()){
                if ($this->model->is_authorized()){
                    
                } else {
                    $this->view->forbidden();
                }
            } else {
                $this->view->auth();
            }
        }
    }
}
namespace UI\All {
    class Navbar {
        public function generate(){}
    }
}
namespace UI\Content {
    class Articles {}
    class Permissions {}
}
namespace UI\SEO {
    class Keywords {}
    class Clients {}
    class Permissions {}
}
namespace UI\PPC {
    class Permissions {}
}
namespace UI\Web_Development {
    class Permissions {}
}
?>