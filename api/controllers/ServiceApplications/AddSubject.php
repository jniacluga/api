<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AddSubject extends CI_Controller {
    
    function __construct() {
        parent::__construct();
		$this->load->model("ServiceApplications/AddSubjectModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>serviceapplications</b>/<b>addsubject</b>/index";
    }
    
    public function getSubjectsOfferedButNotTakenOrEnrolled() {
        
//        $studentNumber = $_POST['studentNumber'];
        
        $studentNumber = "2017-00056-OUCM-0";
        
        print(json_encode($this->m->getSubjectsOfferedButNotTakenOrEnrolled($studentNumber)));
        
    }
    
    public function submitApplication() {
        
//        $studentNumber = $_POST['studentNumber'];
//        $input = $_POST['input'];
        
        $studentNumber = "2017-00056-OUCM-0";
        $input['reason'] = "Sample Add Subject";
        $input['subject'] = array(6);
        
        print(json_encode($this->m->submitApplication($input, $studentNumber)));
    }
    
    public function loadAllApplications() {
        
//        $studentNumber = $_POST['studentNumber'];
//        $campusId = $_POST['campusId'];
//        $programType = $_POST['programType'];
//        $status = $_POST['status'] != "All Applications" ? $_POST['status'] : "";
        
        $studentNumber = "2017-00056-OUCM-0";
        $campusId = 1;
        $programType = "OU";
        $status = "For Approval";
            
        print(json_encode($this->m->loadAllApplications($studentNumber, $campusId, $programType, $status)));
    }
    
    public function loadAllApplicationsApprover() {
        
//        $facultyId = $_POST['facultyId'];
//        $status = $_POST['status'];
        
        $facultyId = "FA0087MN2009";
        $status = "For Approval";
        
        print(json_encode($this->m->loadAllApplicationsApprover($facultyId, $status)));
    }

    public function cancelApplication() {
        
//        $appId = $_POST['appId'];
        
        $appId = 7;
        
        print(json_encode($this->m->cancelApplication($appId)));
    }
    
    public function reviewApplication() {
        
//        $appId = $_POST['appId'];
//        $facultyId = $_POST['sourceId'];
//        $status = $_POST['status'];
        
        $appId = 6;
        $facultyId = "FA0087MN2009";
        $status = "Approved";
        
        print(json_encode($this->m->reviewApplication($appId, $facultyId, $status)));
    }
    
    public function loadSingleApplication() {
//        $appId = $_POST['appId'];
        
        $appId = 7;
        
        print(json_encode($this->m->loadSingleApplication($appId)));
    }
}