<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Grades extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        
		$this->load->model("GradesModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>grades</b>/index";
    }
    
    public function loadGrades() {
    
//        $username = s$_POST['username'];
//        $password = sha1($_POST['password']);
        
        // testing purposses
        $studentNumber = "2013-00028-CM-0";
        // end testing purposes
        
        print(json_encode($this->m->loadGrades($studentNumber)));
        
    }
    
}