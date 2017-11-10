<?php

class AddSubjectModel extends CI_Model {
    
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'CHANGESUB' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_sr_wf_subject
                                        WHERE SR = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }
    
    /*
     * Get subjects offered but not taken or enrolled
     *  for dropdown/spinner
     */
    public function getSubjectsOfferedButNotTakenOrEnrolled($studentNumber) {
        
        $q1 = $this->db->query("SELECT sched.ID SCHEDULE_ID, sub.ID,
                    sub.SUBJECT_CODE, sub.DESCRIPTION, sub.UNITS, sy.SY_ABBR, sem.SEMESTER, UPPER(CONCAT(f.LAST_NAME, ', ', f.FIRST_NAME, ' ', COALESCE(f.MIDDLE_NAME, ''))) FACULTY,
				    sched.DAY_OF_WEEK, TIME_FORMAT(sched.START_TIME, '%h:%i%p') START_TIME, TIME_FORMAT(sched.END_TIME, '%h:%i%p') END_TIME
					FROM r_schedule sched
					INNER JOIN r_subject sub ON sub.SUBJECT_CODE = sched.SUBJECT_CODE
					INNER JOIN r_sy sy ON sy.ID = sched.SY
					INNER JOIN r_semester sem ON sem.ID = sched.SEM
					LEFT OUTER JOIN r_faculty_profile f ON f.FACULTY_CODE = sched.FACULTY_CODE
					INNER JOIN r_room room ON room.ROOM_NO = sched.ROOM_NO
					INNER JOIN r_campus campus ON campus.CODE = room.CAMPUS_CODE
					INNER JOIN t_student st ON st.CAMPUS_ID = campus.ID AND st.STUDENT_NO = ?
					WHERE sub.SUBJECT_CODE NOT IN (SELECT subject_code FROM r_student_class_list 
					WHERE student_no = ?)
					AND sub.SUBJECT_CODE IN (SELECT ss.subject_code FROM r_curriculum_line cl
					INNER JOIN r_curriculum c ON c.ID = cl.CURRICULUM_ID
					INNER JOIN t_student s ON s.STUDENT_NO = ? AND cl.CURRICULUM_ID = s.CURRICULUM_ID
					INNER JOIN r_subject ss ON ss.ID = cl.SUBJECT_ID)
					AND sy.STATUS = 'active' AND sem.STATUS = 'active'", array($studentNumber, $studentNumber, $studentNumber));

		if($q1->num_rows() > 0) {
			foreach($q1->result() as $row) {
				$result[] = array(
					'scheduleId' => $row->SCHEDULE_ID,
                    'subjectId' => $row->ID,
                    'subjectCode' => $row->SUBJECT_CODE,
                    'subjectDesc' => $row->DESCRIPTION,
                    'units' => $row->UNITS,
                    'sy' => $row->SY_ABBR,
                    'sem' => $row->SEMESTER,
                    'faculty' => $row->FACULTY,
                    'dayofWeek' => $row->DAY_OF_WEEK,
                    'startTime' => $row->START_TIME,
                    'endTime' => $row->END_TIME,
				);
			}
		}	
        
		return $result;
    }

    public function submitApplication($input, $studentNumber){
        
        $success = "";
                
		$this->db->query("INSERT INTO t_sr_subject(STUDENT_NO, REASON, STATUS, DATE_REQUEST, TYPE) 
                         VALUES(?, ?, 'For Approval', DATE(NOW()), 'ADDSUB')", 
                         array($studentNumber, $input['reason']));
        
        $success .= $this->db->affected_rows();

		$q1 = $this->db->query("SELECT ID 
                                FROM t_sr_subject 
                                WHERE STUDENT_NO = ? 
                                AND STATUS = 'For Approval'
                                AND TYPE = 'ADDSUB'
		                        ORDER BY ID DESC LIMIT 1",
                                array($studentNumber));
        
		$appId = $q1->row()->ID;

		foreach ($input['subject'] as $value) {
            
			$SUBJECT = $value;

			$this->db->query("INSERT INTO t_sr_subject_line(SR, SUBJECT_CODE, SUBJECT_CODE_CHANGE, TYPE) 
			                 VALUES(?, ?, NULL, 'ADDSUB')", 
                             array($appId, $SUBJECT));
            
            $success .= $this->db->affected_rows();
		}
		
        if(strpos($success, '0') == "") {
            return "Success";
        } else {
            return "Error";
        }
	}
    
    /*
     * Load all applications for use of students
     */
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
                                        WHERE sr.`TYPE` = 'ADDSUB' AND sr.`STUDENT_NO` = ? " . $statusQuery .
                                        " GROUP BY sr.`ID`
                                        ORDER BY DATE_REQUEST DESC", 
                                        array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $applications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'type'   => /*$row->TYPE*/ "Add Subject",
                    'subjects' => $row->SUBJECTS,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $applications;
        
    }
    
    /*
     * Load all applications for approval
     *  for use of approvers
     */
    public function loadAllApplicationsApprover($facultyId, $status) {
        
        $ids = "0";

		$q1 = $this->db->query("SELECT DISTINCT wf.ID, wf.CAMPUS_ID FROM r_sr_wf wf
                                INNER JOIN r_sr_wf_line wfl ON wf.ID = wfl.WORKFLOW AND
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "ADDSUB"));
        
		if($q1->num_rows() > 0) {
            
			foreach($q1->result() as $rows) {
				
                $q2 = $this->db->query("SELECT GROUP_CONCAT(sr.ID SEPARATOR ',') IDS FROM t_sr_subject sr 
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        WHERE s.CAMPUS_ID = ? AND (sr.TYPE = ?) AND sr.STATUS = ?",
                                        array($rows->CAMPUS_ID, 'ADDSUB', $status));
				
                foreach($q2->result() as $row) {
					$ids = $row->IDS;
				}
			}
		}
        
		$result = array();
        
		$queryresult = $this->db->query("SELECT LPAD(sr.ID, 5, '0') ID, sr.STATUS, DATE_FORMAT(sr.DATE_REQUEST, '%b %d, %Y') DATE_REQUEST, sr.REASON, 
                                        GROUP_CONCAT(CONCAT(UPPER(sub.SUBJECT_CODE), ' - ', UPPER(sub.DESCRIPTION), ' (', sub.UNITS, ' Units) / ', sy.SY_ABBR, ' - ', sem.SEMESTER, ' / ', 
                                        CONCAT(UPPER(f.LAST_NAME), ', ', UPPER(f.FIRST_NAME), ' ', UPPER(IFNULL(f.MIDDLE_NAME, ''))), ' / ',
                                        sched.DAY_OF_WEEK, ' ', TIME_FORMAT(sched.START_TIME, '%h:%i%p'), ' - ', TIME_FORMAT(sched.END_TIME, '%h:%i%p')) SEPARATOR ',<br>') 
                                        SUBJECTS, COUNT(*) SUBJECT_COUNT,
                                        CONCAT(pd.LAST_NAME, ', ', pd.FIRST_NAME, ' ', IFNULL(pd.MIDDLE_NAME, '')) STUDENT, sr.TYPE,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_subject sr
                                        INNER JOIN t_student s ON sr.STUDENT_NO = s.STUDENT_NO
                                        INNER JOIN r_student_personal_data pd ON pd.STUDENT_NO = s.STUDENT_NO
                                        LEFT OUTER JOIN t_sr_subject_line sl ON sl.SR = sr.ID AND sr.TYPE = sl.TYPE
                                        LEFT OUTER JOIN r_schedule sched ON sched.ID = sl.SUBJECT_CODE
                                        LEFT OUTER JOIN r_subject sub ON sub.SUBJECT_CODE = sched.SUBJECT_CODE
                                        LEFT OUTER JOIN r_sy sy ON sy.ID = sched.SY
                                        LEFT OUTER JOIN r_semester sem ON sem.ID = sched.SEM
                                        LEFT OUTER JOIN r_faculty_profile f ON f.FACULTY_CODE = sched.FACULTY_CODE
                                        LEFT OUTER JOIN r_student_class_list cl ON cl.ID = sl.SUBJECT_CODE_CHANGE
                                        LEFT OUTER JOIN r_subject sub2 ON cl.SUBJECT_CODE = sub2.SUBJECT_CODE
                                        LEFT OUTER JOIN r_sy sy2 ON sy2.ID = cl.SY
                                        LEFT OUTER JOIN r_semester ss ON ss.ID = cl.SEM
                                        LEFT OUTER JOIN r_schedule sc2 ON sc2.SECTION_CODE = cl.SECTION_CODE AND sc2.SY = cl.SY AND sc2.SEM = cl.SEM
                                        LEFT OUTER JOIN r_faculty_profile f2 ON f2.FACULTY_CODE = sc2.FACULTY_CODE
                                        WHERE sr.ID IN (".$ids.")
                                        GROUP BY sr.ID
                                        ORDER BY sr.status DESC"
		);

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result_array() as $row)
			{
                $result[] = array(
                    'id'     => $row['ID'],
                    'status' => $row['STATUS'],
                    'type'   => "Add Subject",
                    'subjectCount' => $row['SUBJECT_COUNT'],
                    'subjects' => $row['SUBJECTS'],
                    'approvalLevel' => $this->getCurrentLevel($row['ID']),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row['CAMPUS_ID'], $row['PROGRAM_TYPE']),
                    'reason' => $row['REASON'],
                    'dateRequested' => $row['DATE_REQUEST'],
                    
                    /*'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'type'   => "Add Subject",
                    'subjectCount' => $row->SUBJECT_COUNT,
                    'subjects' => $row->SUBJECTS    ,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,*/
                );
			}
		}	
		return $result;
        
    }
    
    /*
     * Cancel application for use of students
     */
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_sr_subject SET STATUS = 'Cancelled' WHERE ID = ?", 
                                        array($appId));
        
        if($this->db->affected_rows() > 0) {
            return "Success";
        } else {
            return "Error";
        }
    }
    
    /*
     * Approving/Declining applications 
     *  for use of approvers
     */
    public function reviewApplication($appId, $facultyId, $status) {
        
        $success = "";
        
        $this->db->query("INSERT INTO t_sr_wf_subject
                        (SR, FACULTY_CODE, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", 
                         array($appId, $facultyId, $status));
        
        $success .= $this->db->affected_rows();
        
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
                                    WHEN wfl.ORDER = (SELECT MAX(r_sr_wf_line.ORDER) FROM r_sr_wf_line WHERE WORKFLOW = wf.ID) 
                                    THEN 'FINAL' 
                                    ELSE 'PASS' 
                                END LINE 
                                FROM r_sr_wf wf
                                INNER JOIN r_sr_wf_line wfl 
                                ON wf.ID = wfl.WORKFLOW AND wf.CAMPUS_ID = ? 
                                AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "ADDSUB", $facultyId));
        
        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_sr_subject SET STATUS = ?, DATE_REVIEW = DATE(NOW()) WHERE ID = ?", array($status, $appId));

            $success .= $this->db->affected_rows();

            if($status == 'Approved'){

                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for adding of subject/ss has been approved.'), 'fa fa-smile-o', ?)",array($stud));*/

                if($studentType == 'NON-SIS') {
                    $q3 = $this->db->query("SELECT sl.ID, sl.SR, sl.SUBJECT_CODE, sl.SUBJECT_CODE_CHANGE, sl.TYPE
                                            FROM t_sr_subject_line sl
                                            WHERE sl.SR = ?", array($appId));
                    
                    
                    foreach($q3->result() as $rows) {
                        $sub = $rows->SUBJECT_CODE;
                        $change = $rows->SUBJECT_CODE_CHANGE;

                        $q4 = $this->db->query("SELECT sched.SY, sched.SEM, sched.SECTION_CODE, sched.SUBJECT_CODE, sched.COURSE_CODE
                                                FROM r_schedule sc
                                                WHERE sched.ID = ?", array($sub));
                        

                        $sub_sy = $q4->row()->SY;
                        $sub_sem = $q4->row()->SEM;
                        $sub_section_code = $q4->row()->SECTION_CODE;
                        $sub_subject_code = $q4->row()->SUBJECT_CODE;
                        $sub_course_code = $q4->row()->COURSE_CODE;

                        $this->db->query("INSERT INTO r_student_class_list(SY, SEM, SECTION_CODE, SUBJECT_CODE, STUDENT_NO, COURSE_CODE, DATE_ENROLLED, STUDENT_STATUS) 
                                        VALUES(?, ?, ?, ? , ?, ?, DATE(NOW()), 'ENROLLED')",
                                        array($sub_sy, $sub_sem, $sub_section_code, $sub_subject_code, $stud, $sub_course_code));
                        
                        $success .= $this->db->affected_rows();
                    }
                }
            }
            
            if($status == 'Declined') {
                /*$this->db->query("INSERT INTO t_notification(CONTEXT, ICON, SOURCE_ID) 
                VALUES(CONCAT(DATE_FORMAT(DATE(NOW()), '%b %d, %Y'),  ' Your application for adding of subject/ss has been declined.'), 'fa fa-frown-o', ?)",array($stud));*/
            }
        }
        
        if(strpos($success, '0') == "") {
            return "Success";
        } else {
            return "Error";
        }
    }
    
    /*
     * Load single application for viewing
     */
    public function loadSingleApplication($appId) {
        
         $queryresult = $this->db->query("SELECT app.ID, app.STATUS, app.TYPE, COUNT(*) SUBJECT_COUNT, 
                                            GROUP_CONCAT(subj.DESCRIPTION SEPARATOR ', ') SUBJECTS, app.REASON, 
                                            DATE_FORMAT(app.DATE_REQUEST, '%d %b %Y') DATE_REQUEST,
                                            s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_subject app
                                        INNER JOIN t_sr_subject_line app_line
                                        ON app.ID = app_line.SR
                                        INNER JOIN r_subject subj
                                        ON subj.ID = app_line.SUBJECT_CODE
                                        INNER JOIN t_student s
                                        ON s.STUDENT_NO = app.STUDENT_NO
                                        WHERE app.ID = ?
                                        GROUP BY app.ID
                                        ORDER BY DATE_REQUEST DESC", 
                                        array($appId));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $application = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'type'   => "Add Subject",
                    'subjectCount' => $row->SUBJECT_COUNT,
                    'subjects' => $row->SUBJECTS,
                    'approvalLevel' => $this->getCurrentLevel($row->ID),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    'reason' => $row->REASON,
                    'dateRequested' => $row->DATE_REQUEST,
                );
            }
        }
        
		return $application;
    }
    
}