<?php
class Permission {
    public $pay_grade;
    public $departments;
    public function __construct($pay_grade,$departments){
	$this->pay_grade = $pay_grade;
	$this->departments = $departments;
    }
    
    private function matches($session_department_object,$requested_to_check_department_name){
	//If the current department in our iteration through the session list is the department in question
	//and has a pay grade over the requested pay grade, return true.
	return (
	    ($session_department_object->department_name == $requested_to_check_department_name)&&
	    ($session_department_object->pay_grade_id >= $this->pay_grade)
	);
    }

    public function has_permission(){
        foreach ($_SESSION['oozledash']->departments as $dept){
	    //If the user gave you a string for a department match, check the login session to see if the user has access
	    //to the department and has a pay grade of a certain level within that department.
            if (is_string($this->departments)){
		if ($this->matches($dept,$this->departments))
		    return true;
            } else if (is_array($this->departments)) {
		//If the user gave you an array, check all of departments in the array to see if they match
		//the current iteration in our loop through the login sessiond departments.
                for ($i=0;$i<count($this->departments);$i++){
		    if ($this->matches($dept,$this->departments[$i]))
			return true;
                }
            }
        }
        return false;
    }
}
?>