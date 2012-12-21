<?php
namespace API\All {
    class System extends \Endpoint {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        //This method simply emails a bug report to Cameron.
        public function submit_bug_report(){
            $reqs = new \Required_Parameters(array(),array(
                "user_id"=>\Types::Int,
                "browser"=>\Types::String,
                "operating_system"=>\Types::String,
                "user_email_address"=>\Types::Email,
                "bug_description"=>\Types::String
            ));
            return $this->validate_output($reqs,false,new \Permission(1,array("Content","SEO","PPC","Web Development")),array($this,"submit_bug_report_callback"));
        }
        public function submit_bug_report_callback(){
            //Write the submitted parameters into the body of the email.
            $body = "User ID: ".$this->parameters['user_id']."\n";
            $body .= "Browser: ".$this->parameters['browser']."\n";
            $body .= "Operating System: ".$this->parameters['operating_system']."\n";
            $body .= "Email Address: ".$this->parameters['user_email_address']."\n\n";
            $body .= $this->parameters['bug_description'];
            
            //Send the email.
            mail("cameron.oozle@gmail.com","Bug Report",$body,"From: cameron@oozlemedia.com");
            return $this->success(array("Thanks for the feedback! We'll look into your problem right away."));
        }
    }

}
?>