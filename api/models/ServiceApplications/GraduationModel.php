<?php

class GraduationModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'GRAD' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_app_wf_graduation
                                        WHERE APP_GRADUATION = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }
    
    private function getDateOfOralDefense($studentNumber) {
        
        $queryresult = $this->db->query("SELECT defense.SCHEDULE FROM t_defense defense
                                        INNER JOIN t_thesis_dissertation thesis
                                        ON defense.THESIS_DISSERTATION_ID = thesis.ID
                                        WHERE defense.REMARKS = 'Passed' 
                                        AND defense.DEFENSE_LEVEL_ID = 3
                                        AND thesis.STUDENT_NO = ?
                                        ORDER BY defense.CREATED_AT DESC
                                        LIMIT 1", array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->SCHEDULE;
            }
        }
    }
    
    private function getCompreExamDate($studentNumber) {
        
        $queryresult = $this->db->query("SELECT DATE_FORMAT(COMPRE_EXAM_DATE, '%d %b %Y') COMPRE_EXAM_DATE FROM t_student
                                        WHERE STUDENT_NO = ?", array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->COMPRE_EXAM_DATE;
            }
        }
    }
    
    private function getCurrentlyEnrolledSubjects() {
        
    }
    
    private function getAlreadyCompletedSubjects() {
        
    }

    public function loadAllApplications($studentNumber, $campusId, $programType, $status) {
       
        $graduationApplications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (STATUS = 'Approved' OR STATUS = 'Declined')";
        } else {
            $statusQuery = "AND STATUS = '" . $status . "'";
        }
        
        $queryresult = $this->db->query("SELECT ID, STATUS, DATE_FORMAT(DATE_REQUEST, '%d %b %Y') DATE_REQUEST
                                        FROM t_app_graduation
                                        WHERE STUDENT_NO = ? " . $statusQuery .
                                        " ORDER BY DATE_REQUEST DESC", 
                                        array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                // temporarily calls the date thesis was finished
                // and comprehensive examination passed
                $graduationApplications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'dateOfOralDefense' => $this->getDateOfOralDefense($studentNumber),
                    'dateCompreExam' => $this->getCompreExamDate($studentNumber),
                    'type' => 'GRAD',
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType),
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $graduationApplications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_app_graduation SET STATUS = 'Cancelled' WHERE ID = ?", 
                                        array($appId));
        
        if($this->db->affected_rows() > 0) {
            return "Success";
        } else {
            return "Error";
        }
    }
    
    public function loadAllApplicationsApprover($facultyId, $status) {
        
        $ids = array("0");

		$q1 = $this->db->query("SELECT DISTINCT wf.ID, wf.CAMPUS_ID FROM r_sr_wf wf
                                INNER JOIN r_sr_wf_line wfl ON wf.ID = wfl.WORKFLOW AND
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "GRAD"));
        
		if($q1->num_rows() > 0) {
            
			foreach($q1->result() as $rows) {
				
                $q2 = $this->db->query("SELECT sr.`ID` FROM t_sr_subject sr 
                                        INNER JOIN t_student s ON sr.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE s.`CAMPUS_ID` = ? AND (sr.TYPE = ?) AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, 'GRAD', $status));
				
                foreach($q2->result() as $row) {
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(g.ID, 5, '0') ID, g.`STUDENT_NO`, DATE_FORMAT(g.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, g.`STATUS`,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT, g.STUDENT_NO,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_app_graduation g
                                        INNER JOIN t_student s ON g.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_personal_data pd ON pd.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE g.ID IN (".$ids.")
                                        ORDER BY g.DATE_REQUEST DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                 // temporarily calls the date thesis was finished
                // and comprehensive examination passed
                $result[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'dateOfOralDefense' => $this->getDateOfOralDefense($row->STUDENT_NO),
                    'dateCompreExam' => $this->getCompreExamDate($row->STUDENT_NO),
                    'type' => 'GRAD',
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'dateRequested' => $row->DATE_REQUEST,
                );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status) {
        
        $this->db->query("INSERT INTO t_app_wf_graduation(app_graduation, FACULTY_CODE, STATUS, DATE_REVIEW)
                            VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));

        $q = $this->db->query("SELECT s.fCAMPUS_ID, sra.STUDENT_NO FROM t_app_graduation sra
                                INNER JOIN t_student s 
                                ON sra.STUDENT_NO = s.STUDENT_NO
                                INNER JOIN r_curriculum c 
                                ON c.ID = s.CURRICULUM_ID
                                INNER JOIN r_program p 
                                ON c.PROGRAM_ID = p.id 
                                INNER JOIN r_program_campus pc 
                                ON pc.PROGRAM_ID = p.ID AND sra.ID = ?", array($appId));

        $campus = $q->row()->CAMPUS_ID;
        $stud = $q->row()->STUDENT_NO;

        $q2 = $this->db->query("SELECT 
                        CASE 
                            WHEN wfl.ORDER = (SELECT MAX(ORDER) FROM r_sr_wf_line WHERE WORKFLOW = wf.ID) 
                            THEN 'FINAL' 
                            ELSE 'PASS' 
                        END LINE 
                        FROM r_sr_wf wf
                        INNER JOIN r_sr_wf_line wfl 
                        ON wf.ID = wfl.WORKFLOW AND wf.CAMPUS_ID = ? 
                        AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "GRAD", $facultyId));

        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_app_graduation SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            /*if($status == 'Approved')
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for graduation has been approved.'), 'fa fa-smile-o', ?)",array($stud));
            if($status == 'Declined')
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for graduation has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
        }

        return "Success";
    }
    
    public function submitApplication($studentNumber){
		$this->db->query("INSERT INTO t_app_graduation(STUDENT_NO, date_request, STATUS) 
			VALUES(?, DATE(NOW()), 'For Approval')",
		 array($studentNumber));
		
		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for graduation. Please wait for approval'), 'fa fa-info', ?)",array($studentNumber));
		return 1;
	}	
    
}