<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Schedule extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        
		$this->load->model("ScheduleModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>schedule</b>/index";
    }
    
    public function getSchoolYears() {
        print(json_encode($this->m->getSchoolYears()));
    }
    
    public function getSemesters() {
        print(json_encode($this->m->getSemesters()));    
    }
    
    public function loadSchedule() {
    
//        $username = s$_POST['username'];
//        $password = sha1($_POST['password']);
        
        $studentNumber = "2017-00056-OUCM-0";
//        $sy = $_POST['sy'];
//        $sem = $_POST['sem'];
        
        // testing purposses
        $studentNumber = "2017-00056-OUCM-0";
        $sy = 1;
        $sem = 3;
        // end testing purposes
        
        print(json_encode($this->m->loadSchedule($studentNumber, $sy, $sem)));
        
    }
    
}