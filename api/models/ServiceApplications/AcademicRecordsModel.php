<?php

class AcademicRecordsModel extends CI_Model {
    
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'ACADREC' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_app_wf_records
                                        WHERE APP_RECORDS = ? AND STATUS = 'Approved'", array($applicationId));
        
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
            $statusQuery = "AND (STATUS = 'Approved' OR STATUS = 'Declined')";
        } else {
            $statusQuery = "AND STATUS = '" . $status . "'";
        }
        
        $queryresult = $this->db->query("SELECT LPAD(app.ID, 5, '0') ID, DATE_FORMAT(app.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, app.`STUDENT_NO`,
                                app.`STATUS`, app.`REQUEST_DOCUMENT`, app.`PURPOSE`, (LENGTH(REQUEST_DOCUMENT) - LENGTH(REPLACE(REQUEST_DOCUMENT, ';', '')))/CHAR_LENGTH(';') RECORD_COUNT
                                FROM t_app_records app
                                WHERE app.`STUDENT_NO` = ? " . $statusQuery. " ORDER BY app.DATE_REQUEST DESC", 
			                    array($studentNumber)
		);
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'requestDocuments'     => $row->REQUEST_DOCUMENT,
                    'status' => $row->STATUS,
                    'reason' => $row->PURPOSE,
                    'dateRequested' => $row->DATE_REQUEST,
                    'numberOfRecordsRequested' => substr($row->RECORD_COUNT, 0, strpos($row->RECORD_COUNT, ".")),
                    'type' => 'Academic Records',
                    'approvalLevel' => $this->getCurrentLevel($studentNumber),
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType)
                );
            }
        }
        
		return $applications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_app_records SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "ACADREC"));
        
		if($q1->num_rows() > 0) {
            
			foreach($q1->result() as $rows) {
				
                $q2 = $this->db->query("SELECT sr.`ID` FROM t_sr_subject sr 
                                        INNER JOIN t_student s ON sr.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE s.`CAMPUS_ID` = ? AND (sr.TYPE = ?) AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, 'ACADREC', $status));
				
                foreach($q2->result() as $row) {
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, 
                                        sr.`REQUEST_DOCUMENT`, sr.`PURPOSE`,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT,
                                        (LENGTH(sr.REQUEST_DOCUMENT) - LENGTH(REPLACE(sr.REQUEST_DOCUMENT, ';', '')))/CHAR_LENGTH(';') RECORD_COUNT,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_app_records sr
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
                    'id'     => $row->ID,
                    'requestDocuments'     => $row->REQUEST_DOCUMENT,
                    'status' => $row->STATUS,
                    'reason' => $row->PURPOSE,
                    'dateRequested' => $row->DATE_REQUEST,
                    'numberOfRecordsRequested' => substr($row->RECORD_COUNT, 0, strpos($row->RECORD_COUNT, ".")),
                    'type' => 'ACADREC',
                    'approvalLevel' => $this->getCurrentLevel($studentNumber),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    
                );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status) {
        
        $this->db->query("INSERT INTO t_app_wf_records(APP_RECORDS, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));
        
        $q = $this->db->query("SELECT s.CAMPUS_ID, sra.STUDENT_NO FROM t_app_records sra
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
                                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "ACADREC", $facultyId));
        
        if($q2->row()->LINE == "FINAL") {
            
            $this->db->query("UPDATE t_app_records SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            if($status == 'Approved') {
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for academic requirements has been approved.'), 'fa fa-smile-o', ?)",array($stud));
            }
            
            if($status == 'Declined') {
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for academic requirements has been declined.'), 'fa fa-frown-o', ?)",array($stud));
            }
        }
        
        return "Success";
    }
    
    public function submitApplication($input, $student){
        
		$this->db->query("INSERT INTO t_app_records (STUDENT_NO, REQUEST_DOCUMENT, PURPOSE, STATUS, DATE_REQUEST) 
			VALUES(?, ?, ?, 'For Approval', DATE(NOW()))",
		 array($student, $input['request_docu'], $input['purpose']));
		
//		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
//			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for academic records/ credentials. Please wait for approval'), 'fa fa-info', ?)",array($student));

		return 1;
	}
    
}