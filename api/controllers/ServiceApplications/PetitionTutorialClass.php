<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PetitionTutorialClass extends CI_Controller {
    
    function __construct() {
        parent::__construct();
		$this->load->model("ServiceApplications/PetitionTutorialClassModel", "m");
    }
    
    
    public function index() {
        echo "You are at: api/<b>serviceapplications</b>/<b>petitiontutorialclass</b>/index";
    }
    
    public function getSubjectsNotOfferedAndNotTakenOrEnrolled() {
        
    }
    
    public function getStudentsWhoRequireTheSubject() {
        
    }
    
    public function getPetitionTutorialClassMatrix() {
        
    }
    
    public function loadOpenPetitions() {
        
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
    
    public function loadAllInvites() {
        
//        $studentNumber = $_POST['studentNumber'];
//        $campusId = $_POST['campusId'];
//        $programType = $_POST['programType'];
//        $status = $_POST['status'] != "All Applications" ? $_POST['status'] : "";
        
        $studentNumber = "2017-00056-OUCM-0";
        $campusId = 1;
        $programType = "OU";
        $status = "For Approval";
            
        print(json_encode($this->m->loadAllInvites($studentNumber, $campusId, $programType, $status)));
    }
    
    public function cancelApplication() {
        
        $appId = $_POST['appId'];
        $studentNumber = $_POST['sourceId'];
        
//        $appId = $this->uri->segment(5);
//        $studentNumber = $this->uri->segment(6);
        
        print(json_encode($this->m->cancelApplication($appId, $studentNumber)));
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