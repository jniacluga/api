<?php

class OverloadSubjectModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'OVERLOADSUB' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_app_wf_overload
                                        WHERE APP_OVERLOAD = ? AND STATUS = 'Approved'", array($applicationId));
        
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
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, sr.`REASON`, 
                                GROUP_CONCAT(CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units) / ', sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ', 
                                CONCAT(UPPER(f.`LAST_NAME`), ', ', UPPER(f.`FIRST_NAME`), ' ', UPPER(IFNULL(f.`MIDDLE_NAME`, ''))), ' / ',
                                sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SEPARATOR ',<br>') SUBJECTS,
                                sr.`STUDNO_STATUS`, s.CAMPUS_ID, s.PROGRAM_TYPE
                                FROM t_app_overload sr
                                INNER JOIN t_student s ON s.STUDENT_NO = sr.STUDENT_NO
                                INNER JOIN t_app_overload_line sl ON sl.`app` = sr.`ID`
                                LEFT OUTER JOIN r_schedule sc ON sc.`ID` = sl.`SUBJECT_CODE`
                                LEFT OUTER JOIN r_subject sub ON sub.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
                                LEFT OUTER JOIN r_sy sy ON sy.`ID` = sc.`SY`
                                LEFT OUTER JOIN r_semester sem ON sem.`ID` = sc.`SEM`
                                LEFT OUTER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`
                                WHERE sr.`STUDENT_NO` = ? " . $statusQuery . 
                                " GROUP BY sr.`ID`
                                ORDER BY DATE_REQUEST DESC", array($studentNumber)
		);

        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'studentStatus' => $row->STUDNO_STATUS,
                    'subjects' => $row->SUBJECTS,
                    'type' => 'Overload Subject',
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $applications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_app_overload SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                        array($rows->CAMPUS_ID, 'ACADREC', $status));
				
                foreach($q2->result() as $row) {
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, sr.`REASON`, 
                                        GROUP_CONCAT(CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units) / ', sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ', 
                                        CONCAT(UPPER(f.`LAST_NAME`), ' ', UPPER(f.`FIRST_NAME`), ' ', UPPER(IFNULL(f.`MIDDLE_NAME`, ''))), ' / ',
                                        sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SEPARATOR ',<br>') SUBJECTS, 
                                        (CASE sr.STATUS
                                            WHEN 'Others'
                                                THEN sr.OTHER_STATUS
                                            ELSE sr.STUDNO_STATUS END) STUDNO_STATUS,
                                        COUNT(*) SUBJECT_COUNT, 
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT,
                                        s.PROGRAM_TYPE, s.CAMPUS_ID
                                        FROM t_app_overload sr
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_personal_data pd ON pd.`STUDENT_NO` = s.`STUDENT_NO`
                                        INNER JOIN t_app_overload_line sl ON sl.`app` = sr.`ID`
                                        LEFT OUTER JOIN r_schedule sc ON sc.`ID` = sl.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_subject sub ON sub.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_sy sy ON sy.`ID` = sc.`SY`
                                        LEFT OUTER JOIN r_semester sem ON sem.`ID` = sc.`SEM`
                                        LEFT OUTER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`
                                        WHERE sr.ID IN (".$ids.")
                                        GROUP BY sr.`ID`
                                        ORDER BY sr.DATE_REQUEST DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                $result[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'studentStatus' => $row->STUDNO_STATUS,
                    'subjectCount' => $row->SUBJECT_COUNT,
                    'subjects' => $row->SUBJECTS,
                    'type' => 'OVERLOAD',
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status) {
			
        $this->db->query("INSERT INTO t_app_wf_overload(APP_OVERLOAD, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));

        $q = $this->db->query("SELECT s.CAMPUS_ID, sra.STUDENT_NO, s.STUDENT_TYPE FROM t_app_overload sra
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
                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "OVERLOADSUB", $facultyId));

        if($q2->row()->LINE == "FINAL") {

            $this->db->query("UPDATE t_app_overload SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            if($status == 'Approved') {

                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for overloading of subject has been approved.'), 'fa fa-smile-o', ?)",array($stud));*/


                if($studentType == 'NON-SIS') {
                    $q3 = $this->db->query("SELECT ol.SUBJECT_CODE
                                        FROM t_app_overload_line ol
                                        WHERE ol.APP = ?", array($appId));

                    foreach($q3->result() as $rows) {
                        $sub = $rows->SUBJECT_CODE;

                        $q4 = $this->db->query("SELECT sc.SY, sc.SEM, sc.SECTION_CODE, sc.SUBJECT_CODE, sc.COURSE_CODE
                                                FROM r_schedule sc
                                                WHERE sc.ID = ?", array($sub));

                        $sub_sy = $q4->row()->SY;
                        $sub_sem = $q4->row()->SEM;
                        $sub_section_code = $q4->row()->SECTION_CODE;
                        $sub_subject_code = $q4->row()->SUBJECT_CODE;
                        $sub_course_code = $q4->row()->COURSE_CODE;

                        $this->db->query("INSERT INTO r_student_class_list(SY, SEM, SECTION_CODE, SUBJECT_CODE, STUDENT_NO, COURSE_CODE, DATE_ENROLLED, STUDENT_STATUS) 
                            VALUES(?, ?, ?, ? , ?, ?, DATE(NOW()), 'ENROLLED')",
                            array($sub_sy, $sub_sem, $sub_section_code, $sub_subject_code, $stud, $sub_course_code));
                    }
                }
            }

            if($status == 'Declined') {
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                    VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for overloading of subject has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
            }
        }

        return "Success";
    }
    
    public function submitApplication($input, $studentNumber){
		$status = "";
		if ($input['status'] != 'Others')
		{
			$status = $input['status'];
		}else{
			$status = $input['others'];
		}

		$this->db->query("INSERT INTO t_app_overload(STUDENT_NO, STUDNO_STATUS, REASON, STATUS, DATE_REQUEST) 
			VALUES(?, ?, ?, 'For Approval', DATE(NOW()))",
		 array($studentNumber, $status, $input['reason']));

		$q1 = $this->db->query("SELECT ID FROM t_app_overload WHERE STUDENT_NO = ? AND STATUS = 'For Approval'
		 ORDER BY ID DESC LIMIT 1", 
			array($stud));
		$appId = $q1->row()->ID;

		foreach ($input['subject'] as $value) {
			$SUBJECT = $value;

			$this->db->query("INSERT INTO t_app_overload_line(APP, SUBJECT_CODE) 
			VALUES(?, ?)",
		 array($appId, $SUBJECT));
		}
		
//		$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
//			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for overload subject. Please wait for approval'), 'fa fa-info', ?)",array($studentNumber));


		return 1;
	}	
    
}




















