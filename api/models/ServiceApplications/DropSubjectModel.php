<?php

class DropSubjectModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'DROPSUB' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_sr_wf_subject
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
        
        $queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, sr.`REASON`, 
                                        GROUP_CONCAT(CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units) / ', sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ', 
                                        CONCAT(UPPER(f.`LAST_NAME`), ', ', UPPER(f.`FIRST_NAME`), ' ', UPPER(IFNULL(f.`MIDDLE_NAME`, ''))), ' / ',
                                        sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SEPARATOR '\r\n') 
                                        SUBJECTS, 
                                        GROUP_CONCAT(CONCAT(UPPER(sub2.`SUBJECT_CODE`), ' - ', UPPER(sub2.`DESCRIPTION`), ' (', sub2.`UNITS`, ' Units) / ', sy2.`SY_ABBR`, ' - ', ss.`SEMESTER`, ' / ', 
                                        CONCAT(UPPER(f2.`LAST_NAME`), ', ', UPPER(f2.`FIRST_NAME`), ' ', UPPER(IFNULL(f2.`MIDDLE_NAME`, ''))), ' / ',
                                        sc2.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc2.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc2.`END_TIME`, '%h:%i%p')) SEPARATOR '\r\n')  `CHANGE`,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_subject sr
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        LEFT OUTER JOIN t_sr_subject_line sl ON sl.`SR` = sr.`ID` AND sr.`TYPE` = sl.`TYPE`
                                        LEFT OUTER JOIN r_schedule sc ON sc.`ID` = sl.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_subject sub ON sub.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_sy sy ON sy.`ID` = sc.`SY`
                                        LEFT OUTER JOIN r_semester sem ON sem.`ID` = sc.`SEM`
                                        LEFT OUTER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`
                                        LEFT OUTER JOIN r_student_class_list cl ON cl.`ID` = sl.`SUBJECT_CODE_CHANGE`
                                        LEFT OUTER JOIN r_subject sub2 ON cl.`SUBJECT_CODE` = sub2.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_sy sy2 ON sy2.`ID` = cl.`SY`
                                        LEFT OUTER JOIN r_semester ss ON ss.`ID` = cl.`SEM`
                                        LEFT OUTER JOIN r_schedule sc2 ON sc2.`SECTION_CODE` = cl.`SECTION_CODE` AND sc2.`SY` = cl.`SY` AND sc2.`SEM` = cl.`SEM`
                                        LEFT OUTER JOIN r_faculty_profile f2 ON f2.`FACULTY_CODE` = sc2.`FACULTY_CODE`
                                        WHERE sr.`TYPE` = 'DROPSUB' AND sr.`STUDENT_NO` = ? " . $statusQuery .
                                        " GROUP BY sr.`ID`
                                        ORDER BY DATE_REQUEST DESC", 
                                        array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'type'   => /*$row->TYPE*/ "Drop Subject",
                    'subjects' => $row->CHANGE,
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
        
        $queryresult = $this->db->query("UPDATE t_sr_subject SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "DROPSUB"));
        
		if($q1->num_rows() > 0) {
            
			foreach($q1->result() as $rows) {
				
                $q2 = $this->db->query("SELECT sr.`ID` FROM t_sr_subject sr 
                                        INNER JOIN t_student s ON sr.`STUDENT_NO` = s.`STUDENT_NO`
                                        WHERE s.`CAMPUS_ID` = ? AND (sr.TYPE = ?) AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, 'DROPSUB', $status));
				
                foreach($q2->result() as $row) {
					$ids[] = $row->ID;
				}
			}
		}
        
        $ids = ($ids == "") ? "0" : $ids;
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') `ID`, sr.`STATUS`, DATE_FORMAT(sr.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, sr.`REASON`, 
                                        GROUP_CONCAT(CONCAT(UPPER(sub.`SUBJECT_CODE`), ' - ', UPPER(sub.`DESCRIPTION`), ' (', sub.`UNITS`, ' Units) / ', sy.`SY_ABBR`, ' - ', sem.`SEMESTER`, ' / ', 
                                        CONCAT(UPPER(f.`LAST_NAME`), ', ', UPPER(f.`FIRST_NAME`), ' ', UPPER(IFNULL(f.`MIDDLE_NAME`, ''))), ' / ',
                                        sc.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc.`END_TIME`, '%h:%i%p')) SEPARATOR ',<br>') 
                                        SUBJECTS, COUNT(*) SUBJECT_COUNT,
                                        GROUP_CONCAT(CONCAT(UPPER(sub2.`SUBJECT_CODE`), ' - ', UPPER(sub2.`DESCRIPTION`), ' (', sub2.`UNITS`, ' Units) / ', sy2.`SY_ABBR`, ' - ', ss.`SEMESTER`, ' / ', 
                                        CONCAT(UPPER(f2.`LAST_NAME`), ', ', UPPER(f2.`FIRST_NAME`), ' ', UPPER(IFNULL(f2.`MIDDLE_NAME`, ''))), ' / ',
                                        sc2.`DAY_OF_WEEK`, ' ', TIME_FORMAT(sc2.`START_TIME`, '%h:%i%p'), ' - ', TIME_FORMAT(sc2.`END_TIME`, '%h:%i%p')) SEPARATOR ',<br>') `CHANGE`,
                                        CONCAT(pd.`LAST_NAME`, ', ', pd.`FIRST_NAME`, ' ', IFNULL(pd.`MIDDLE_NAME`, '')) STUDENT, sr.`TYPE`
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_subject sr
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_personal_data pd ON pd.`STUDENT_NO` = s.`STUDENT_NO`
                                        LEFT OUTER JOIN t_sr_subject_line sl ON sl.`SR` = sr.`ID` AND sr.`TYPE` = sl.`TYPE`
                                        LEFT OUTER JOIN r_schedule sc ON sc.`ID` = sl.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_subject sub ON sub.`SUBJECT_CODE` = sc.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_sy sy ON sy.`ID` = sc.`SY`
                                        LEFT OUTER JOIN r_semester sem ON sem.`ID` = sc.`SEM`
                                        LEFT OUTER JOIN r_faculty_profile f ON f.`FACULTY_CODE` = sc.`FACULTY_CODE`
                                        LEFT OUTER JOIN r_student_class_list cl ON cl.`ID` = sl.`SUBJECT_CODE_CHANGE`
                                        LEFT OUTER JOIN r_subject sub2 ON cl.`SUBJECT_CODE` = sub2.`SUBJECT_CODE`
                                        LEFT OUTER JOIN r_sy sy2 ON sy2.`ID` = cl.`SY`
                                        LEFT OUTER JOIN r_semester ss ON ss.`ID` = cl.`SEM`
                                        LEFT OUTER JOIN r_schedule sc2 ON sc2.`SECTION_CODE` = cl.`SECTION_CODE` AND sc2.`SY` = cl.`SY` AND sc2.`SEM` = cl.`SEM`
                                        LEFT OUTER JOIN r_faculty_profile f2 ON f2.`FACULTY_CODE` = sc2.`FACULTY_CODE`
                                        WHERE sr.ID IN (".$ids.")
                                        GROUP BY sr.`ID`
                                        ORDER BY sr.status DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $rows)
			{
                $result[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'type'   => "Drop Subject",
                    'subjectCount' => $row->SUBJECT_COUNT,
                    'subjects' => $row->SUBJECTS,
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
        
        $this->db->query("INSERT INTO t_sr_wf_subject
                        (SR, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", 
                         array($appId, $facultyId, $status));
        
        $q = $this->db->query("SELECT s.CAMPUS_ID, sra.STUDENT_NO, s.STUDENT_TYPE 
                            FROM t_sr_subject sra
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
                                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "DROPSUB", $facultyId));

        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_sr_subject SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            if($status == 'Approved') {
                
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for droping of subject/ss has been approved.'), 'fa fa-smile-o', ?)",array($stud));*/

                if($studentType == 'NON-SIS') {

                    $q3 = $this->db->query("SELECT sl.ID, sl.SR, sl.SUBJECT_CODE, sl.SUBJECT_CODE_CHANGE, sl.TYPE
                                            FROM t_sr_subject_line sl
                                            WHERE sl.SR = ?", array($appId));

                    foreach($q3->result() as $rows) {
                        $sub = $rows->SUBJECT_CODE;
                        $change = $rows->SUBJECT_CODE_CHANGE;

                        $this->db->query("UPDATE r_student_class_list SET STUDENT_STATUS = 'DROPPED' WHERE SUBJECT_CODE = ?", array($change));
                    }

                }
            }
            
            if($status == 'Declined') {
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for droping of subject/ss has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
            }
        }
        
        return "Success";
    }
    
    public function submitApplication($input, $studentNumber){
		$this->db->query("INSERT INTO t_sr_subject(STUDENT_NO, REASON, STATUS, DATE_REQUEST, TYPE) 
			VALUES(?, ?, 'For Approval', DATE(NOW()), 'DROPSUB')",
		 array($studentNumber, $input['reason']));

		$q1 = $this->db->query("SELECT ID FROM t_sr_subject WHERE STUDENT_NO = ? AND STATUS = 'For Approval' AND TYPE = 'DROPSUB'
		 ORDER BY ID DESC LIMIT 1", 
			array($stud));
		$appId = $q1->row()->ID;

		foreach ($input['subject'] as $value) {
			$SUBJECT = $value;

			$this->db->query("INSERT INTO t_sr_subject_line(SR, SUBJECT_CODE, SUBJECT_CODE_CHANGE, TYPE) 
			VALUES(?, NULL, ?, 'DROPSUB')",
		 array($appId, $SUBJECT));
		}
		
		/*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, `SOURCE_ID`) 
			VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' You have been applied for drop subject. Please wait for approval'), 'fa fa-info', ?)",array($stud));*/


		return 1;
	}
    
}