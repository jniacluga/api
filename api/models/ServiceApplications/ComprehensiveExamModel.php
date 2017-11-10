<?php

class ComprehensiveExamModel extends CI_Model {
    
    // FOR COMMON
    private function getNumberOfApprovers($campusId, $programType) {
        
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVERS FROM r_sr_wf app
                                        INNER JOIN r_sr_wf_line app_line
                                        ON app.ID = app_line.WORKFLOW
                                        WHERE TYPE = 'COMPRE' AND CAMPUS_ID = ? AND PROGRAM_TYPE = ?", array($campusId, $programType));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVERS;
            }
        }
    }
    
    // FOR COMMON
    private function getCurrentLevel($applicationId) {
            
        $queryresult = $this->db->query("SELECT COUNT(*) NUMBER_OF_APPROVALS FROM t_sr_wf_compre
                                        WHERE SR = ? AND STATUS = 'Approved'", array($applicationId));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->NUMBER_OF_APPROVALS;
            }
        }
    }
    
    private function getUnitsTakenToTake($studentNumber) {
        
        $ersult = array();
        $queryresult = $this->db->query("SELECT 
                                            (COALESCE(CORE_UNITS,0) + COALESCE(MAJOR_UNITS,0) + COALESCE(THESIS_UNITS,0) + COALESCE(COGNATES_UNITS,0) + COALESCE(PRE_UNITS,0) + COALESCE(NON_THESIS_UNITS,0)) + (SELECT COUNT(*) FROM T_PENALTY_LINE WHERE STUDENT_NO = ST.STUDENT_NO) TOTAL_UNITS,
                                            (SELECT SUM(UNITS) FROM r_student_class_list INNER JOIN r_subject ON r_subject.SUBJECT_CODE = r_student_class_list.SUBJECT_CODE WHERE COMPUTED_FINAL_GRADE IS NOT NULL AND STUDENT_NO = ST.STUDENT_NO) SUBJECTS_TAKEN
                                        FROM r_curriculum C
                                        INNER JOIN t_student ST
                                        ON ST.CURRICULUM_ID = C.ID
                                        WHERE ST.STUDENT_NO = ?
                                        LIMIT 1", array($studentNumber));
        
        if ($queryresult -> num_rows() > 0)  {
            foreach($queryresult ->result() as $rows){
                $result = array(
                     'TAKEN_UNITS'  => $rows->SUBJECTS_TAKEN == '' ? 0 : $rows->SUBJECTS_TAKEN,
                     'TOTAL_UNITS'  => $rows->TOTAL_UNITS,
                 );
            }
        }
        
        return $result;
    }
    
    private function getCurrentlyEnrolledSubjects($studentNumber) {
        
        $queryresult = $this->db->query("SELECT SUM(subj.UNITS) CURRENTLY_ENROLLED_UNITS
                                        FROM r_student_class_list classList
                                        INNER JOIN r_subject subj
                                        ON subj.SUBJECT_CODE = classList.SUBJECT_CODE
                                        INNER JOIN r_sy sy
                                        ON sy.ID = classList.SY AND sy.STATUS = 'Active'
                                        INNER JOIN r_semester sem
                                        ON sem.ID = classList.SEM AND sem.STATUS = 'Active'
                                        WHERE STUDENT_NO = ?
                                        AND COMPUTED_FINAL_GRADE IS NULL AND GRADE_STATUS IS NULL
                                        GROUP BY classList.STUDENT_NO", array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
			foreach ( $queryresult->result() as $row ) {
                return $row->CURRENTLY_ENROLLED_UNITS;
            }
        }
    }

    public function loadAllApplications($studentNumber, $campusId, $programType, $status) {
       
        $comprehensiveExamApplications = "";
        
        if ($status == "") {
            $statusQuery = "";
        } else if ($status == "Approved") {
            $statusQuery = "AND (STATUS = 'Approved' OR STATUS = 'Declined')";
        } else {
            $statusQuery = "AND STATUS = '" . $status . "'";
        }
        
        $queryresult = $this->db->query("SELECT LPAD(ID, 5, '0') ID, STATUS, DATE_FORMAT(DATE_REQUEST, '%b %d, %Y') DATE_REQUEST FROM t_sr_compre
                                        WHERE STUDENT_NO = ? " . $statusQuery .
                                        "ORDER BY DATE_REQUEST DESC", array($studentNumber));
        
        if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                $comprehensiveExamApplications[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'currentlyEnrolledUnits' => $this->getCurrentlyEnrolledSubjects($studentNumber),
                    'completedUnits' => $this->getUnitsTakenToTake($studentNumber)["TAKEN_UNITS"],
                    'totalNumberOfUnitsToTake' => $this->getUnitsTakenToTake($studentNumber)["TOTAL_UNITS"],
                    'dateRequested' => $row->DATE_REQUEST,
                    'type' => 'Comprehensive Exam',
                    'approvalLevel' => $this->getCurrentLevel($studentNumber),
                    'numberOfApprovers' => $this->getNumberOfApprovers($campusId, $programType),
                );
            }
        }
        
		return $comprehensiveExamApplications;
        
    }
    
    public function cancelApplication($appId) {
        
        $queryresult = $this->db->query("UPDATE t_sr_compre SET STATUS = 'Cancelled' WHERE ID = ?", 
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
                                wfl.FACULTY = ? AND wf.TYPE = ? ", array($facultyId, "COMPRE"));
        
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
        
		$queryresult = $this->db->query("SELECT LPAD(g.ID, 5, '0') ID, DATE_FORMAT(g.`DATE_REQUEST`, '%b %d, %Y') DATE_REQUEST, g.`STATUS`, g.STUDENT_NO,
                                        s.CAMPUS_ID, s.PROGRAM_TYPE
                                        FROM t_sr_compre g
                                        INNER JOIN t_student s
                                        ON s.STUDENT_NO = g.STUDENT_NO
                                        WHERE g.`STUDENT_NO` = ?", 
                                        array($stud)
        );

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                $result[] = array(
                    'id'     => $row->ID,
                    'status' => $row->STATUS,
                    'currentlyEnrolledUnits' => $this->getCurrentlyEnrolledSubjects($row->STUDENT_NO),
                    'completedUnits' => $this->getUnitsTakenToTake($row->STUDENT_NO)["TAKEN_UNITS"],
                    'totalNumberOfUnitsToTake' => $this->getUnitsTakenToTake($row->STUDENT_NO)["TOTAL_UNITS"],
                    'dateRequested' => $row->DATE_REQUEST,
                    'type' => 'COMPRE',
                    'approvalLevel' => $this->getCurrentLevel($row->STUDENT_NO),
                    'numberOfApprovers' => $this->getNumberOfApprovers($row->CAMPUS_ID, $row->PROGRAM_TYPE),
                    );
			}
		}	
		return $result;
        
    }
    
    public function reviewApplication($appId, $facultyId, $status) {
        
        $this->db->query("INSERT INTO t_sr_wf_compre(SR, FACULTY, STATUS, DATE_REVIEW)
                        VALUES(?, ?, ?, DATE(NOW()))", array($appId, $facultyId, $status));

        $q = $this->db->query("SELECT p.CAMPUS FROM t_sr_compre sra
                                INNER JOIN t_student s 
                                ON sra.STUDENT = s.ID
                                INNER JOIN r_program p 
                                ON s.PROGRAM = p.ID AND sra.ID = ?", array($appId));

        $campus = $q->row()->CAMPUS;

        $q2 = $this->db->query("SELECT 
                            CASE 
                                WHEN wfl.ORDER = (SELECT MAX(ORDER) FROM r_sr_wf_line WHERE WORKFLOW = wf.ID) 
                                THEN 'FINAL' 
                                ELSE 'PASS' 
                            END LINE 
                            FROM r_sr_wf wf
                            INNER JOIN r_sr_wf_line wfl 
                            ON wf.ID = wfl.WORKFLOW AND wf.CAMPUS_ID = ? 
                            AND wf.TYPE = ? AND wfl.FACULTY = ?", array($campus, "COMPRE", $facultyId));

        if($q2->row()->LINE == "FINAL") {
            $this->db->query("UPDATE t_sr_compre SET STATUS = ? WHERE ID = ?", array($status, $appId));
        }

        return "Success";
    }
    
    public function createComprehensiveExamApplication() {
//        INSERT INTO t_sr_compre(STUDENT_NO, STATUS, DATE_REQUEST) 
//            VALUES(?, 'For Approval', DATE(NOW()))
    }
    
}