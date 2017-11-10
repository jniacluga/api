<?php

class ScheduleModel extends CI_Model {
    
    public function getSchoolYears(){
        
		$queryresult = $this->db->query("SELECT ID, SY_START, SY_END, SY_ABBR FROM r_sy ORDER BY SY_ABBR");

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $rows)
			{
				$result[] = array(
					"id" => $rows->ID,
					"start" => $rows->SY_START,
					"end" => $rows->SY_END,
					"abbr" => $rows->SY_ABBR,
                );	
			}
		}else
			"Error";
		return $result;
	}

	public function getSemesters(){
		$queryresult = $this->db->query("SELECT ID, SEMESTER
			FROM r_semester");

		if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $rows)
			{
				$result[] = array(
					"id" => $rows->ID,
					"semester" => $rows->SEMESTER
                );	
			}
		}else
			"Error";
		return $result;
	}
    
    public function loadSchedule($studentNumber, $sy, $sem) {
        
        $schedule = array();
        
        $queryresult = $this->db->query("SELECT DISTINCT cl.STUDENT_NO, sy.SY_ABBR SY, sem.SEMESTER, cl.SUBJECT_CODE, sj.DESCRIPTION, cl.SECTION_CODE, CONCAT(CONCAT(UCASE(SUBSTRING(f.FIRST_NAME, 1, 1)), LOWER(SUBSTRING(f.FIRST_NAME, 2))), ' ', CONCAT(UCASE(SUBSTRING(f.LAST_NAME, 1, 1)), LOWER(SUBSTRING(f.LAST_NAME, 2)))) FACULTY, sc.DAY_OF_WEEK, DATE_FORMAT(sc.START_TIME, '%h:%i %p') START_TIME, DATE_FORMAT(sc.END_TIME, '%h:%i %p') END_TIME, sc.ROOM_NO
                                        FROM `r_student_class_list` cl
                                        JOIN `r_subject` sj ON `cl`.`SUBJECT_CODE` = `sj`.`SUBJECT_CODE`
                                        JOIN `r_schedule` sc ON `sj`.`SUBJECT_CODE` = `sc`.`SUBJECT_CODE`
                                        JOIN `r_faculty_profile` f ON `sc`.`FACULTY_CODE` = `f`.`FACULTY_CODE`
                                        JOIN `r_student_personal_data` st ON `cl`.`STUDENT_NO` = `st`.`STUDENT_NO`
                                        JOIN r_sy sy ON sy.ID = cl.SY
                                        JOIN r_semester sem ON sem.ID = cl.SEM
                                        WHERE `cl`.`STUDENT_NO` =  ?
                                        AND `cl`.`SY` =  ?
                                        AND `cl`.`SEM` =  ?
                                        ORDER BY sc.DAY_OF_WEEK", array($studentNumber, $sy, $sem));
            
        if($queryresult->num_rows() > 0)
		{
			foreach($queryresult->result() as $row)
			{
                $schedule[] = array(
                    'sy' => $row->SY,
                    'sem' => $row->SEMESTER,
                    'day' => $row->DAY_OF_WEEK,
                    'startTime' => $row->START_TIME,
                    'endTime' => $row->END_TIME,
                    'subjectCode' => $row->SUBJECT_CODE,
                    'description' => $row->DESCRIPTION,
                    'faculty' => $row->FACULTY,
                    'room' => $row->ROOM_NO,
                    'sectionCode' => $row->SECTION_CODE,
                );
            }
        }
        
        return $schedule;
    }
}