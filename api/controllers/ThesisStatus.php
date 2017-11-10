<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ThesisStatus extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        
		$this->load->model("ThesisStatusModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>thesisstatus</b>/index";
    }
    
    public function getPaperStatus() {
        
//        $studentNumber = $_POST['studentNumber'];
        
        // testing purposses
        $studentNumber = "2013-00008-CM-0";
        // end testing purposes
        
        print(json_encode($this->m->getPaperStatus($studentNumber)));
    }
    
    public function getStudentThesisEligibility() {
        
//        $studentNumber = $_POST['studentNumber'];
        
        // testing purposses
        $studentNumber = "2013-00008-CM-0";
        // end testing purposes
        
        print(json_encode($this->m->getStudentThesisEligibility($studentNumber)));
    }
    
    public function loadThesisStatus() {
     
//        $studentNumber = $_POST['studentNumber'];
        
        // testing purposses
        $studentNumber = "2013-00008-CM-0";
        // end testing purposes
        
        print(json_encode($this->m->loadThesisStatus($studentNumber)));
        
    }
    
}