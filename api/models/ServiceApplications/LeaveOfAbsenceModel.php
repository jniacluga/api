<?php

class LeaveOfAbsenceModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'LOA' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_sr_wf_loa
                                        WHERE SR = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }

    public function loadAllApplications($studentNumber, $campusId, $programType, $status) {
       
        $applications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (sr.STATUS = 'Approved' OR sr.STATUS = 'Declined')";
        } else {
            $statusQuery = "AND sr.STATUS = '" . $status . "'";
        }
        
        $queryresult = $this->db->query(
			"SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, sr.`REASON`, 
			DATE_FORMAT(sr.`DATE_EFFECTIVE`, '%b %d, %Y') DATE_EFFECTIVE
			FROM t_sr_loa sr
			WHERE sr.`STUDENT_NO` = ? " . $statusQuery . "
			 ORDER BY sr.`DATE_REQUEST` DESC, sr.`STATUS` DESC", 
			array($studentNumber)
		);

        
        if ( $queryresult->num_rows() > 0 ) {
            
            foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'dateOfEffectivity' => $row->DATE_EFFECTIVE,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'type' => 'Leave of Absence',
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $applications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_sr_loa SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                wfl.FACULTY = ? AND wf.TYPE = 'LOA' ", array($facultyId));
        
		if($q1->num_rows() > 0) {
			foreach($q1->result() as $rows) {
				$q2 = $this->db->query("SELECT sr.`ID` FROM t_sr_loa sr 
                                        INNER JOIN t_student s ON sr.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE s.`CAMPUS_ID` = ? AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, $status));
                
				foreach($q2->result() as $row){
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
        
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST,
                                        sr.`REASON`, DATE_FORMAT(sr.`DATE_EFFECTIVE`, '%b %d, %Y') DATE_EFFECTIVE,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_loa sr
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_personal_data pd ON pd.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE sr.ID IN (".$ids.")
                                        ORDER BY sr.DATE_REQUEST DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                
				$result[] = array(
                    'id' => $row->ID,
                    'status' => $row->STATUS,
                    'dateOfEffectivity' => $row->DATE_EFFECTIVE,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'type' => 'LOA',
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
				);
			}
		}	
		return $result;
    }
    
    public function reviewApplication($appId, $facultyId, $status){
		
        $this->db->query("INSERT INTO t_sr_wf_loa(SR, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));

        $q = $this->db->query("SELECT s.`CAMPUS_ID`, sra.`STUDENT_NO` FROM t_sr_loa sra
            INNER JOIN t_student s ON sra.`STUDENT_NO` = s.`STUDENT_NO` AND sra.`ID` = ?", array($appId));

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
                    AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "LOA", $facultyId));

        if($q2->row()->LINE == "FINAL") {	
            $this->db->query("UPDATE t_sr_loa SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            if($status == 'Approved'){
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for leave of absence has been approved.'), 'fa fa-smile-o', ?)",array($stud));*/

                $this->db->query("UPDATE t_student SET STATUS = 'ON LEAVE' WHERE STUDENT_NO = ?", array($stud));
            }

            /*if($status == 'Declined') {
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for leave of absence has been declined.'), 'fa fa-frown-o', ?)",array($stud));
            }*/
        }

        return "Success";
    }
    
    public function submitApplication($input, $studentNumber){
		$this->db->query("INSERT INTO t_sr_loa(STUDENT_NO, REASON, STATUS, DATE_REQUEST, DATE_EFFECTIVE) 
			VALUES(?, ?, 'For Approval', DATE(NOW()), ?)",
		 array($studentNumber, $input['reason'], $input['date']));
		
//		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
//			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for leave of absence. Please wait for approval'), 'fa fa-info', ?)",array($studentNumber));

		return 1;
	}
    
}