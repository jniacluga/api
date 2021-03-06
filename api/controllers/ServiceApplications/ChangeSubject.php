<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ChangeSubject extends CI_Controller {
    
    function __construct() {
        parent::__construct();
		$this->load->model("ServiceApplications/ChangeSubjectModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>serviceapplications</b>/<b>changesubject</b>/index";
    }
    
    public function getCurrentlyEnrolledSubjects() {
        
//        $studentNumber = $_POST['studentNumber'];
        
        $studentNumber = "2017-00056-OUCM-0";
        
        print(json_encode($this->m->getCurrentlyEnrolledSubjects($studentNumber)));
    }
    
    public function getSubjectsOfferedButNotTakenOrEnrolled() {

//        $studentNumber = $_POST['studentNumber'];
        
        $studentNumber = "2017-00056-OUCM-0";
        
        print(json_encode($this->m->getSubjectsOfferedButNotTakenOrEnrolled($studentNumber)));
    }
    
    public function submitApplication() {
        
        $studentNumber = "2017-00056-OUCM-0";
        $input['reason'] = "Sample for Change Subject";
        $input['subjectChange'] = array(120);
        $input['subject'] = array();
        
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
    
    public function cancelApplication() {
        
        $appId = $_POST['appId'];
        
//        $appId = $this->uri->segment(5);
        
        print(json_encode($this->m->cancelApplication($appId)));
    }
    
    public function loadAllApplicationsApprover() {
        
        $facultyId = $_POST['facultyId'];
        $status = $_POST['status'];
        
//        $facultyId = $this->uri->segment(5);
//        $status = $this->uri->segment(6);
        
        print(json_encode($this->m->loadAllApplicationsApprover($facultyId), $status));
    }
    
    public function reviewApplication() {
        
        $appId = $_POST['appId'];
        $facultyId = $_POST['sourceId'];
        $status = $_POST['status'];
        
//        $appId = $this->uri->segment(5);
//        $facultyId = $this->uri->segment(6);
//        $status = $this->uri->segment(7);
        
        print(json_encode($this->m->reviewApplication($appId, $facultyId, $status)));
    }
    
}