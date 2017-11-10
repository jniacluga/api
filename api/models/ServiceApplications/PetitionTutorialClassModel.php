<?php

class PetitionTutorialClassModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'PETITION' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_sr_wf_petition
                                        WHERE SR = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }
    
    private function getStudentsInvolved() {
        
    }

    public function loadAllApplications($studentNumber, $campusId, $programType, $status) {
       
        $applications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (app.STATUS = 'Approved' OR app.STATUS = 'Declined')";
        } else if ($status == "For Approval") {
            $statusQuery = "AND (app.STATUS = 'For Approval' OR app.STATUS = 'Open for Petitioners')";
        }
        
        $queryresult = $this->db->query("SELECT LPAD(app.ID, 5, '0') `ID`,
                                    GROUP_CONCAT(CONCAT('(', s.`STUDENT_NO`, ') ' ,s.`LAST_NAME`, ', ', s.`FIRST_NAME`, ' ', IFNULL(s.`MIDDLE_NAME`, '')) SEPARATOR '\r\n') INVITED,
                                    GROUP_CONCAT(CONCAT('(', ap.`STUDENT_NO`, ') ' ,ap.`LAST_NAME`, ', ', ap.`FIRST_NAME`, ' ', IFNULL(ap.`MIDDLE_NAME`, '')) SEPARATOR '\r\n') ACCEPTED,
                                    COUNT(s.STUDENT_NO) STUDENT_INVITED_COUNT,
                                    COUNT(ap.STUDENT_NO) STUDENT_ACCEPTED_COUNT,
                                    CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units)') SUBJECT, app.`STATUS`,
                                    DATE_FORMAT(app.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, c.CODE
                                    FROM T_SR_PETITION app
                                    INNER JOIN t_sr_petition_stud ps
                                    ON ps.`SR` = app.`ID`
                                    INNER JOIN r_student_personal_data s
                                    ON s.`STUDENT_NO` = ps.`STUDENT_NO`
                                    INNER JOIN t_student stud
                                    ON stud.STUDENT_NO = ps.STUDENT_NO
                                    INNER JOIN r_campus c
                                    ON c.ID = stud.CAMPUS_ID
                                    LEFT OUTER JOIN r_student_personal_data ap ON ps.`STUDENT_NO` = ap.`STUDENT_NO` AND ps.`STATUS` = 'Approved'
                                    INNER JOIN r_subject sub
                                    ON sub.`ID` = app.`SUBJECT`
                                    WHERE app.`STUDENT_NO` = ? " . $statusQuery .
                                    " GROUP BY app.`ID`
                                    ORDER BY DATE_REQUEST DESC", array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'invitedStudents'   => $row->INVITED,
                    'approvedStudents'   => $row->ACCEPTED,
                    'campus' => $row->CODE,
                    'type' => 'PETITION',
                    'numberOfStudentsInvited' => $row->STUDENT_INVITED_COUNT,
                    'numberOfStudentsThatApproved' => $row->STUDENT_ACCEPTED_COUNT,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType),
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $applications;
        
    }
    
    public function loadAllInvites($studentNumber, $campusId, $programType, $status) {
        
        $applications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (app.STATUS = 'Approved' OR app.STATUS = 'Declined')";
        } else if ($status == "For Approval") {
            $statusQuery = "AND (app.STATUS = 'For Approval' OR app.STATUS = 'Open for Petitioners')";
        }
        
        $queryresult = $this->db->query("SELECT LPAD(p.ID, 5, '0') `ID`,
				GROUP_CONCAT(CONCAT('(', pd1.`STUDENT_NO`, ') ' ,pd1.`LAST_NAME`, ', ', pd1.`FIRST_NAME`, ' ', IFNULL(pd1.`MIDDLE_NAME`, '')) SEPARATOR ',<br>') invited,
				GROUP_CONCAT(CONCAT('(', ap.`STUDENT_NO`, ') ' ,ap.`LAST_NAME`, ', ', ap.`FIRST_NAME`, ' ', IFNULL(ap.`MIDDLE_NAME`, '')) SEPARATOR ',<br>') accepted,
				CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units)') SUBJECT, p.`STATUS`,
				DATE_FORMAT(p.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, c.CODE, 
                COUNT(s.STUDENT_NO) STUDENT_INVITED_COUNT,
                COUNT(ap.STUDENT_NO) STUDENT_ACCEPTED_COUNT,
				CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) student
				FROM t_sr_petition_stud ps
				INNER JOIN t_sr_petition p ON p.`ID` = ps.`SR`
				INNER JOIN t_student s ON s.`STUDENT_NO` = ps.`STUDENT_NO`
				INNER JOIN r_student_personal_data pd1 ON ps.`STUDENT_NO` = pd1.`STUDENT_NO`
				LEFT OUTER JOIN r_student_personal_data ap ON ps.`STUDENT_NO` = ap.`STUDENT_NO` AND ps.`STATUS` = 'Approved'
				INNER JOIN t_student s1 ON s1.`STUDENT_NO` = p.`STUDENT_NO`	
				INNER JOIN r_student_personal_data pd ON p.`STUDENT_NO` = pd.`STUDENT_NO`
				INNER JOIN r_subject sub ON sub.`ID` = p.`SUBJECT`
                INNER JOIN r_campus c
                ON c.ID = s.CAMPUS_ID
				WHERE p.`ID` IN (SELECT sr FROM t_sr_petition_stud WHERE student_no = ?)
				GROUP BY p.`ID`", 
			array($studentNumber)
		);
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'invitedStudents'   => $row->INVITED,
                    'approvedStudents'   => $row->ACCEPTED,
                    'campus' => $row->CODE,
                    'type' => 'PETITION',
                    'numberOfStudentsInvited' => $row->STUDENT_INVITED_COUNT,
                    'numberOfStudentsThatApproved' => $row->STUDENT_ACCEPTED_COUNT,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType),
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $applications;
    }
    
    public function cancelApplication($appId, $studentNumber) {
        
        $queryresult = $this->db->query("UPDATE t_sr_petition_stud SET STATUS = 'Cancelled' WHERE SR = ? AND STUDENT_NO = ?", 
                                        array($appId, $studentNumber));
        
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
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "PETITION"));
        
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
        
		$queryresult = $this->db->query("SELECT LPAD(app.ID, 5, '0') `ID`,
                                        GROUP_CONCAT(CONCAT('(', s.`STUDENT_NO`, ') ' ,s.`LAST_NAME`, ', ', s.`FIRST_NAME`, ' ', IFNULL(s.`MIDDLE_NAME`, '')) SEPARATOR ',<br>') invited,
                                        CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units)') SUBJECT, sub.DESCRIPTION, app.`STATUS`,
                                        DATE_FORMAT(app.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, COUNT(*) STUDENT_COUNT,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) student,
                                        campus.CODE, stud.CAMPUS_ID, stud.PROGRAM_TYPE
                                        FROM T_SR_PETITION app
                                        INNER JOIN t_sr_petition_stud ps
                                        ON ps.`SR` = app.`ID`
                                        INNER JOIN r_campus campus
                                        ON campus.ID = app.CAMPUS
                                        INNER JOIN r_student_personal_data s
                                        ON s.`STUDENT_NO` = ps.`STUDENT_NO`
                                        INNER JOIN t_student stud
                                        ON stud.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_subject sub
                                        ON sub.`ID` = app.`SUBJECT`
                                        INNER JOIN r_student_personal_data pd
                                        ON pd.`STUDENT_NO` = app.`STUDENT_NO`
                                        WHERE app.`ID` in (".$ids.") and (app.STATUS = 'For Approval' OR app.STATUS = 'Approved' OR app.STATUS = 'Declined')
                                        GROUP BY app.`ID`
                                        ORDER BY app.DATE_REQUEST DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                $result[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'subject'   => $row->DESCRIPTION,
                    'campus' => $row->CODE,
                    'type' => 'PETITION',
                    'numberOfInvolvedStudents' => $row->STUDENT_COUNT,
                    'numberOfStudentsThatApproved' => $this->getNumberOfStudentsThatApproved($row->ID),
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'dateRequested' => $row->DATE_REQUEST,
                );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status) {
			
        $this->db->query("INSERT INTO t_sr_wf_petition(SR, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));

        $q = $this->db->query("SELECT s.CAMPUS_ID, sra.STUDENT_NO FROM t_sr_petition sra
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
                                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "PETITION", $facultyId));

        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_sr_petition SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            /*if($status == 'Approved')
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for petition/ tutorial class has been approved.'), 'fa fa-smile-o', ?)",array($stud));
            if($status == 'Declined')
                $this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for petition/ tutorial class has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
        }

        return "Success";
    }

    
    
    
    
    
    
    public function submitPetition($input, $stud){
		$this->db->query("INSERT INTO t_sr_petition(STUDENT_NO, STATUS, DATE_REQUEST, SUBJECT, CAMPUS) 
			VALUES(?, 'Open for Petitioners', DATE(NOW()), ?, ?)",
			array($stud, $input["subject"], $stud), $input["campusID"]);
		$q = $this->db->query("SELECT ID FROM t_sr_petition WHERE STUDENT_NO = ? ORDER BY ID DESC",array($stud));
		$id = $q->row()->ID;
		foreach($input["petitioner"] as $p_er){
			$this->db->query("INSERT INTO t_sr_petition_stud(SR, STATUS, STUDENT_NO) VALUES(?, 'For Approval', ?)",
				array($id, $p_er));
		}

		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your request has been cancelled.'), 'fa fa-times', ?)",array($stud));
		
		return 1;
	}	

	public function editApplication($input){
		$this->db->query("DELETE FROM t_sr_petition_stud WHERE SR = ? AND STATUS != 'Approved'", array($input["id"]));
		foreach($input["petitioner"] as $p_er){
			$q = $this->db->query("SELECT ID FROM t_sr_petition_stud WHERE SR = ? AND STUDENT = ?", 
				array((int)$input["id"], $p_er));
			if($q->num_rows() == 0)
				$this->db->query("INSERT INTO t_sr_petition_stud(SR, STATUS, STUDENT) VALUES(?, 'For Approval', ?)",
				array((int)$input["id"], $p_er));
		}
		return 1;
	}	

	public function acceptInvitation($sr, $stud, $stat){
		$this->db->query("UPDATE t_sr_petition_stud SET STATUS = ?, DATE_ACCEPT = DATE(NOW()) WHERE STUDENT = ? AND SR = ?",
		 array($stat, $stud, (int)$sr));

		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your request has been cancelled.'), 'fa fa-times', ?)",array($stud));
		
		return 1;
	}	

	public function submitSRPetition($sr){
		$this->db->query("UPDATE t_sr_petition SET STATUS = 'For Approval' WHERE ID = ?",
		 array((int)$sr));
		return 1;
	}	
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}