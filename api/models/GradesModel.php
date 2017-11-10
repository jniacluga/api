<?php

class GradesModel extends CI_Model {
    
    public function loadGrades($studentNumber) {
        
        $result = array();
        
        $queryresult = $this->db->query("SELECT cl.STUDENT_NO, sj.SUBJECT_CODE, 
                                                sj.DESCRIPTION, cl.SECTION_CODE, 
                                                CONCAT(f.LAST_NAME, ', ', f.FIRST_NAME) FACULTY, 
                                                sj.UNITS, cl.COMPUTED_FINAL_GRADE, cl.GRADE_STATUS, 
                                                sy.SY_ABBR, sy.SY_START, sy.SY_END, sem.SEMESTER 
                                            FROM (`r_student_class_list` cl) 
                                            JOIN `r_schedule` sc ON `cl`.`SECTION_CODE` = `sc`.`SECTION_CODE` 
                                            JOIN `r_subject` sj ON `cl`.`SUBJECT_CODE` = `sj`.`SUBJECT_CODE` 
                                            JOIN `r_sy` sy ON `sy`.`ID` = `cl`.`SY` 
                                            JOIN `r_semester` sem ON `sem`.`ID` = `cl`.`SEM` 
                                            JOIN `r_faculty_profile` f ON `sc`.`FACULTY_CODE` = `f`.`FACULTY_CODE`
                                            WHERE `cl`.`STUDENT_NO` = '2013-00028-CM-0' 
                                            ORDER BY `sy`.`SY_ABBR`, `sem`.`SEMESTER`", array($studentNumber));
        
        if($queryresult->num_rows() > 0) {
            foreach($queryresult->result() as $row)
                $result[] = array(
                    'subjectCode' => $row->SUBJECT_CODE,
                    'description' => $row->DESCRIPTION,
                    'faculty' => $row->FACULTY,
                    'units' => $row->UNITS,
                    'sectionCode' => $row->SECTION_CODE,
                    'finalGrade' => $row->COMPUTED_FINAL_GRADE,
                    'status' => $row->GRADE_STATUS,
                    'sy' => $row->SY_ABBR,
                    'sem' => $row->SEMESTER,
                    'sySem' => $row->SY_ABBR . ", " . $row->SEMESTER, 
                );
        }

        return $result;
    }
}