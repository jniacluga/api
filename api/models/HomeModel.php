<?php

class HomeModel extends CI_Model {

    public function loadHome($studentNumber) {
        
        $result = array(
                    'timeLeft' => $this->getTimeLeft($studentNumber),
                    'completedUnits' => $this->getCompletedUnits($studentNumber),
                    'completedUnitsStatus' => $this->getCompletedUnits($studentNumber, true),
                    'comprehensiveExam' => $this->getComprehensiveExam($studentNumber),
                    'thesisDissertation' => $this->getThesisDissertation($studentNumber),
                    'thesisDissertationStatus' => $this->getThesisDissertation($studentNumber, true),
                    'graduation' => $this->getThesisDissertation($studentNumber, true) == "COMPLETED" ? "Apply Now!" : "You cannot apply yet",
                );
        
        return $result;
        
    }
    
    private function getTimeLeft($studentNumber) {
        $query = "SELECT 
                        FLOOR(TIMEFERENCE/365) YEARS,
                        MOD(TIMEFERENCE, 365) DAYS,
                        RESIDENCY,
                        (CASE
                         WHEN 0 >= FLOOR(TIMEFERENCE/365) AND MOD(TIMEFERENCE, 365) <= 0
                         THEN 'RETURNEE'
                         ELSE 'REGULAR'
                        END) STATUS
                    FROM
                    (SELECT DATEDIFF(DATE_ADD(DATE_CREATED, INTERVAL (SELECT NUMBER FROM r_time_limit WHERE TYPE = (CASE (SELECT SUBSTR(P.COURSE_CODE, 1, 1) FROM r_program P WHERE CURRICULUM_ID = (SELECT CURRICULUM_ID FROM t_student WHERE STUDENT_NO = ST.STUDENT_NO) LIMIT 1) WHEN 'M' THEN 'MASTERAL' ELSE 'DOCTORATE' END)) YEAR), CURRENT_TIMESTAMP) TIMEFERENCE, (SELECT NUMBER FROM r_time_limit WHERE TYPE = (CASE (SELECT SUBSTR(P.COURSE_CODE, 1, 1) FROM r_program P WHERE CURRICULUM_ID = (SELECT CURRICULUM_ID FROM t_student WHERE STUDENT_NO = ST.STUDENT_NO) LIMIT 1) WHEN 'M' THEN 'MASTERAL' ELSE 'DOCTORATE' END)) RESIDENCY FROM `t_student` ST WHERE ST.STUDENT_NO = ?) NEWBL";
        
        $result = array();
        $queryresult = $this->db->query($query, array($studentNumber)); 
        
        if ($queryresult -> num_rows() > 0)  {
            foreach($queryresult ->result() as $rows){
                $result = array(
                     'YEARS'  =>$rows->YEARS,
                     'DAYS'  =>$rows->DAYS,
                     'STATUS' => $rows->STATUS,
                 );
            }
        }
        
        if ($result['DAYS'] < 0 && $result['YEARS'] < 0) {
            $timeLeft = "Exceeded!";
        }
        else {
            $timeLeft = $result['YEARS'] == 0 ? $result['DAYS'] . " days" : $result['YEARS'] . " years";
        }
        
        return $timeLeft;
    }
    
    public function getCompletedUnits($studentNumber, $isStatus = false) {
        $query = "SELECT 
                        (COALESCE(CORE_UNITS,0) + COALESCE(MAJOR_UNITS,0) + COALESCE(THESIS_UNITS,0) + COALESCE(COGNATES_UNITS,0) + COALESCE(PRE_UNITS,0) + COALESCE(NON_THESIS_UNITS,0)) + (SELECT COUNT(*) FROM T_PENALTY_LINE WHERE STUDENT_NO = ST.STUDENT_NO) TOTAL_UNITS,
                        (SELECT SUM(UNITS) FROM r_student_class_list INNER JOIN r_subject ON r_subject.SUBJECT_CODE = r_student_class_list.SUBJECT_CODE WHERE COMPUTED_FINAL_GRADE IS NOT NULL AND STUDENT_NO = ST.STUDENT_NO) SUBJECTS_TAKEN
                    FROM r_curriculum C
                    INNER JOIN t_student ST
                    ON ST.CURRICULUM_ID = C.ID
                    WHERE ST.STUDENT_NO = ?";
        
        $result = array();
        $queryresult = $this->db->query($query, array($studentNumber)); 
        if ($queryresult -> num_rows() > 0)  {
            foreach($queryresult ->result() as $rows){
                $result = array(
                     'TAKEN_UNITS'  => $rows->SUBJECTS_TAKEN == '' ? 0 : $rows->SUBJECTS_TAKEN,
                     'TOTAL_UNITS'  => $rows->TOTAL_UNITS,
                 );
            }
        }
        
        if (!$isStatus) {
            return $result['TAKEN_UNITS'] . ' of ' . $result['TOTAL_UNITS'] . ' Units Taken';
        } else {
            if ($result['TAKEN_UNITS'] == $result['TOTAL_UNITS']) {
                return "Ongoing";
            } else {
                return "Completed";
            }
        }
    }

    public function getComprehensiveExam($studentNumber) {
        
        $query = "SELECT PASSED_COMPRE FROM t_student WHERE STUDENT_NO = ?";
        
        $result = "";
        $queryresult = $this->db->query($query, array($studentNumber)); 
        if ($queryresult -> num_rows() > 0) {
            foreach($queryresult ->result() as $rows){
                if ($rows->PASSED_COMPRE != 0) {
                    $result = $rows->PASSED_COMPRE;
                } else {
                    $query = "SELECT STATUS FROM t_sr_compre WHERE STUDENT_NO = '".$this->session->userdata('SOURCEID')."' ORDER BY DATE_REQUEST LIMIT 1";
                    $queryresult = $this->db->query($query); 
                    if ($queryresult->num_rows() > 0)  {
                        foreach($queryresult->result() as $rows){
                            $result = $rows->STATUS;
                        }
                    } else {
                        $result = 0; 
                    }
                }
                
            }
        }
        
        if ($result == 1) {
            return "Completed";   
        }
        else if ($result == "For Approval" || $result == "Approved") {
            return $result;   
        }
        else {
            return "Apply Now!";   
        }
    }
    
    public function getThesisDissertation($studentNumber, $isStatus = false) {
        $query = "SELECT
                (CASE
                 WHEN D.DEFENSE_LEVEL_ID = (SELECT ID FROM r_defense_level ORDER BY `ORDER` DESC LIMIT 1) AND D.REMARKS IS NOT NULL
                   THEN 'COMPLETED'
                 WHEN D.DEFENSE_LEVEL_ID IN (SELECT ID FROM r_defense_level)
                   THEN 'ONGOING'
                 WHEN ST.PASSED_COMPRE = 1
                   THEN 'APPLY NOW'
                 ELSE 'NOT YET'
                 END) THESIS_STATUS
                FROM t_student ST
                LEFT OUTER JOIN t_thesis_dissertation TD
                ON ST.STUDENT_NO = TD.STUDENT_NO
                LEFT OUTER JOIN t_defense D
                ON  TD.ID = D.THESIS_DISSERTATION_ID
                WHERE ST.STUDENT_NO = ?
                LIMIT 1";
        
        $result = "";
        $queryresult = $this->db->query($query, array($studentNumber)); 
        
        if ($queryresult -> num_rows() > 0)  {
            foreach($queryresult ->result() as $rows) {
                $result = $rows->THESIS_STATUS;
            }
        } else {
            $result = "NOT YET";
        }
        
        $status = "";
        $description = "";
        
        switch($result) {
            case 'COMPLETED':
                $description = "You may now apply for graduation!";
                $status = "Completed";
                break;
            case 'ONGOING':
                $description = "Monitor your paper's progress in Thesis/Dissertation > Monitoring page.";
                $status = "Ongoing";
                break;
            case 'APPLY NOW':
                $description = "Registration is now open!";
                $status = "Apply Now";
                break;
            case 'NOT YET':
                $description = "Pass the comprehensive examination first.";
                $status = "Not yet";
                break;
            default:
                break;
        }
        
        if (!$isStatus) {
            return $description;
        } else {
            return $status;
        }
        
        
    }
}