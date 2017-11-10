<?php

class ThesisStatusModel extends CI_Model {
    
    public function getPaperStatus($studentNumber) {
        
        $queryresult = $this->db->query("SELECT STATUS FROM t_thesis_dissertation WHERE STUDENT_NO = ? ORDER BY CREATED_AT DESC LIMIT 1", array($studentNumber));

		if($queryresult->num_rows() > 0)
			foreach($queryresult->result() as $rows)
                $status = $rows->STATUS;
        else
            $status = 'None';
        
       return $status;
    }
    
    public function getStudentThesisEligibility($studentNumber) {
        $queryresult = $this->db->query("SELECT COALESCE(PASSED_COMPRE, '0') IS_COMPRE_PASSER FROM t_student WHERE STUDENT_NO = ?", array($studentNumber));
        
		if($queryresult->num_rows() > 0)
			foreach($queryresult->result() as $rows)
				$result = $rows->IS_COMPRE_PASSER;
        else
            $result = "Error";   
        
		return $result;
    }
    
    public function loadThesisStatus($studentNumber) {
        
        $result = array();
        
		$queryresult = $this->db->query("SELECT
                                            defLevel.DESCRIPTION 'LEVEL',
                                            DATE_FORMAT(CONCAT(defense.SCHEDULE, ':00'), '%b %d, %Y %h:%i %p') 'DATE',
                                            DAYNAME(CONCAT(defense.SCHEDULE, ':00')) 'DAY',
                                            evalType.DESCRIPTION 'EVALUATOR_TYPE',
                                            evaluator.EVALUATOR_NAME 'EVALUATOR',
                                            honoraria.M_RATE 'PAYMENT',
                                            (CASE
                                                WHEN defense.REMARKS IS NULL
                                                THEN 'INCOMPLETE'
                                                ELSE 'COMPLETE'
                                            END) 'STATUS'
                                        FROM t_thesis_dissertation thesis
                                        INNER JOIN t_defense defense
                                        ON defense.THESIS_DISSERTATION_ID = thesis.ID
                                        INNER JOIN r_defense_level defLevel
                                        ON defense.DEFENSE_LEVEL_ID = defLevel.ID
                                        INNER JOIN t_evaluator evaluator
                                        ON evaluator.DEFENSE_ID = defense.ID
                                        INNER JOIN r_evaluator_type evalType
                                        ON evalType.ID = evaluator.EVALUATOR_TYPE_ID
                                        INNER JOIN r_honoraria_rate honoraria
                                        ON honoraria.EVALUATOR_TYPE_ID = evalType.ID
                                        AND honoraria.DEFENSE_LEVEL_ID = defLevel.ID
                                        WHERE thesis.STUDENT_NO = ?", array($studentNumber));

			if($queryresult->num_rows() > 0) {
				foreach($queryresult->result() as $row)
					$result[] = array(
                        'level' => $row->LEVEL,
                        'dateTime' => $row->DATE,
                        'day' => $row->DAY,
                        'evaluatorType' => $row->EVALUATOR_TYPE,
                        'evaluator' => $row->EVALUATOR,
                        'payment' => $row->PAYMENT,
                        'status' => $row->STATUS,
					);
            }

			return $result;
    
    
    
    }
}