<?php

class CompletionModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'COMPLETION' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_app_wf_completion
                                        WHERE APP_COMPLETION = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }

    public function loadAllApplications($studentNumber, $campusId, $programType, $status) {
       
        $completionApplications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (app.STATUS = 'Approved' OR app.STATUS = 'Declined')";
        } else {
            $statusQuery = "AND app.STATUS = '" . $status . "'";
        }
        
        $queryresult = $this->db->query(
			"SELECT LPAD(app.ID, 5, '0') ID, app.`APPLICATION_FOR`, 
				CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ') / ', 
				sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ',
				CONCAT(f.`LAST_NAME`, ', ', f.`FIRST_NAME`, ' ', IFNULL(f.`MIDDLE_NAME`, '')), ' / ',
				sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SUBJECT,
				app.APPLICATION_FOR, app.STATUS, app.REPORTED_AS, app.CREDITED_AS, app.CREDITED_DETAILS, app.REASON,
                DATE_FORMAT(app.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, 
				app.`STUDENT_NO`
				FROM t_app_completion app
				INNER JOIN r_student_class_list cl ON cl.`ID` = app.`SUBJECT`
				INNER JOIN r_subject sub ON sub.`SUBJECT_CODE` = cl.`SUBJECT_CODE`
				INNER JOIN r_sy sy ON sy.`ID` = cl.`SY`
				INNER JOIN r_semester sem ON sem.`ID` = cl.`SEM`
				INNER JOIN r_schedule sc ON sc.`COURSE_CODE` = cl.`COURSE_CODE` AND sc.`SECTION_CODE` = cl.`SECTION_CODE`
				AND sc.`SEM` = cl.`SEM` AND sc.`SY` = cl.`SY` AND cl.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
				INNER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`	
				WHERE app.`STUDENT_NO` = ? " . $statusQuery .
                " ORDER BY DATE_REQUEST DESC", 
                array($studentNumber)
        );
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $completionApplications[] = array(
                    'id'     => $row->ID,
                    'completionType' => $row->APPLICATION_FOR,
                    'subject' => $row->SUBJECT,
                    'status' => $row->STATUS,
                    'issue' => $row->REPORTED_AS,
                    'creditedAs' => $row->CREDITED_AS,
                    'details' => $row->CREDITED_DETAILS,
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                    'type' => 'COMPLETION',
                    'approvalLevel' => $this->getNumberOfApprovers($campusId, $programType),
                    'numberOfApprovers' => $this->getCurrentLevel($row->ID),
                );
            }
        }
        
		return $completionApplications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_app_completion SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "COMPLETION"));
        
		if($q1->num_rows() > 0) {
            
			foreach($q1->result() as $rows) {
				
                $q2 = $this->db->query("SELECT sr.`ID` FROM t_sr_subject sr 
                                        INNER JOIN t_student s ON sr.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE s.`CAMPUS_ID` = ? AND (sr.TYPE = ?) AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, 'COMPLETION', $status));
				
                foreach($q2->result() as $row) {
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(app.ID, 5, '0') ID, app.`APPLICATION_FOR`, 
                                        CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ') / ', 
                                        sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ',
                                        CONCAT(f.`LAST_NAME`, ', ', f.`FIRST_NAME`, ' ', IFNULL(f.`MIDDLE_NAME`, '')), ' / ',
                                        sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SUBJECT,
                                        app.`REPORTED_AS`, app.`CREDITED_AS`, app.`CREDITED_DETAILS`, DATE_FORMAT(app.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, 
                                        app.`STATUS`, app.`STUDENT_NO`,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_app_completion app
                                        INNER JOIN t_student s ON app.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_class_list cl ON cl.`ID` = app.`SUBJECT`
                                        INNER JOIN r_subject sub ON sub.`SUBJECT_CODE` = cl.`SUBJECT_CODE`
                                        INNER JOIN r_sy sy ON sy.`ID` = cl.`SY`
                                        INNER JOIN r_semester sem ON sem.`ID` = cl.`SEM`
                                        INNER JOIN r_schedule sc ON sc.`COURSE_CODE` = cl.`COURSE_CODE` AND sc.`SECTION_CODE` = cl.`SECTION_CODE`
                                        AND sc.`SEM` = cl.`SEM` AND sc.`SY` = cl.`SY` AND cl.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
                                        INNER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`	
                                        INNER JOIN r_student_personal_data pd ON pd.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE app.ID IN (".$ids.")
                                        GROUP BY app.ID
                                        ORDER BY app.DATE_REQUEST DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                $result[] = array(
                    'id'     => $row->ID,
                    'completionType' => $row->APPLICATION_FOR,
                    'subject' => $row->DESCRIPTION,
                    'status' => $row->STATUS,
                    'issue' => $row->REPORTED_AS,
                    'creditedAs' => $row->CREDITED_AS,
                    'details' => $row->CREDITED_DETAILS,
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                    'type' => 'COMPLETION',
                    'approvalLevel' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'numberOfApprovers' => $this->getCurrentLevel($row->ID),
                );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status){
        $this->db->query("INSERT INTO t_app_wf_completion(APP_COMPLETION, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));
        
        $q = $this->db->query("SELECT s.CAMPUS_ID, sra.STUDENT_NO, s.STUDENT_TYPE FROM t_app_completion sra
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
        $studentType = $q->row()->STUDENT_TYPE;
        
        $q2 = $this->db->query("SELECT 
                                CASE 
                                    WHEN wfl.ORDER = (SELECT MAX(ORDER) FROM r_sr_wf_line WHERE WORKFLOW = wf.ID) 
                                    THEN 'FINAL' 
                                    ELSE 'PASS' 
                                END LINE 
                                FROM r_sr_wf wf
                                INNER JOIN r_sr_wf_line wfl 
                                ON wf.ID = wfl.WORKFLOW AND wf.CAMPUS_ID = ? 
                                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "COMPLETION", $facultyId));
        
        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_app_completion SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            if($status == 'Approved') {
                
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for completion has been approved.'), 'fa fa-smile-o', ?)",array($stud));
*/
                if($studentType == 'NON-SIS') {

                    $q3 = $this->db->query("SELECT sl.ID, sl.SUBJECT, sl.FINAL_GRADE
                                            FROM t_app_completion sl
                                            WHERE sl.ID = ?", array($appId));

                    $finalGrade = $q3->row()->FINAL_GRADE;
                    $subject = $q3->row()->SUBJECT;

                        if($finalGrade != ''){
                            $this->db->query("UPDATE r_student_class_list SET COMPUTED_FINAL_GRADE = ? WHERE ID = ?", array($finalGrade, $subject));
                        }

                }

            }
            
            if($status == 'Declined') {
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for completion has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
            }
        }
        return "Success";
    }
    
    public function submitApplication($input, $studentNumber) {
        
		if ($input['finalGrade'] != ''){
			$credited_details = 'With a final Grade of '.$input['finalGrade'].'';
		}elseif ($input['finalGrade'] == ''){
			$credited_details = 'From: '.$input['fromName'].' To: '.$input['toName'].'';
		}

		if($input['reported'] == 'Others'){
			$others = $input['otherReported'];
		}else{
			$other = $input['reported'];
		}

		$this->db->query("INSERT INTO t_app_completion (STUDENT_NO, APPLICATION_FOR, SUBJECT, REPORTED_AS,  REASON,  CREDITED_AS, CREDITED_DETAILS, STATUS, DATE_REQUEST, FINAL_GRADE) 
			VALUES(?, ?, ?, ?, ?, ?, ?, 'For Approval', DATE(NOW()), ?)",
		 array($stud, $input['applicationFor'], $input['subject'], $other, $input['reason'], $input['credited'], $credited_details, $input['finalGrade']));
		
//		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
//			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for completion. Please wait for approval'), 'fa fa-info', ?)",array($studentNumber));

		return 1;
	}
    
}