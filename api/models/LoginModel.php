<?php

class LoginModel extends CI_Model {

    public function matchLoginCredentials($username, $password) {  
        
        $login = "";
        
		$queryresult = $this->db->query("SELECT SOURCE, USERNAME, ROLE_CODE, SOURCE, IMAGE 
                                        FROM R_USER
                                        WHERE (USERNAME = ? OR SOURCE = ? ) AND PASSWORD = ? LIMIT 1", 
                                        array($username, $username, $password) );
        

		if ( $queryresult->num_rows() > 0 ) { 
            
			foreach ( $queryresult->result() as $row ) {
                
                if ( sha1($row->USERNAME) == sha1($username) || sha1($row->SOURCE) == sha1($username) ) {
                    
                    switch($row->ROLE_CODE) {
                            
                        case "REGISTRAROU":
                        case "REGISTRARGS":
                            $query = "SELECT FP.FIRST_NAME, COALESCE(FP.MIDDLE_NAME, '') MIDDLE_NAME, FP.LAST_NAME, 
                                    P.COURSE_CODE, P.DESCRIPTION, P.ID PROGRAM_ID,
                                    FROM r_faculty_profile FP
                                    LEFT OUTER JOIN r_program P 
                                        ON P.CHAIRPERSON = FP.FACULTY_CODE
                                    WHERE FACULTY_CODE = '$row->SOURCE'";
                            break;
                        case "FACULTY":
                            $query = "SELECT FP.FIRST_NAME, COALESCE(FP.MIDDLE_NAME, '') MIDDLE_NAME, FP.LAST_NAME, 
                                    P.COURSE_CODE, P.DESCRIPTION, P.ID PROGRAM_ID
                                    FROM r_faculty_profile FP
                                    LEFT OUTER JOIN r_program P
                                        ON P.CHAIRPERSON = FP.FACULTY_CODE
                                    WHERE FACULTY_CODE = '$row->SOURCE'";
                            break;
                        case "STUDENT":
                            $query = "SELECT SP.FIRST_NAME, COALESCE(SP.MIDDLE_NAME, '') MIDDLE_NAME, SP.LAST_NAME, 
                                        COALESCE(S.DESCRIPTION, '--') SPECIALIZATION, C.PROGRAM_ID, P.COURSE_CODE, P.DESCRIPTION,
                                        ST.PROGRAM_TYPE, CM.CODE CAMPUS, ST.CAMPUS_ID, ST.STATUS
                                    FROM r_student_personal_data SP
                                    INNER JOIN t_student ST
                                        ON ST.STUDENT_NO = SP.STUDENT_NO
                                    INNER JOIN r_curriculum C
                                        ON ST.CURRICULUM_ID = c.ID
                                    INNER JOIN r_program P
                                        ON P.ID = C.PROGRAM_ID
                                    LEFT OUTER JOIN r_specialization S
                                        ON S.PROGRAM_ID = P.ID
                                    INNER JOIN r_campus CM
                                    	ON CM.ID = ST.CAMPUS_ID
                                    WHERE SP.STUDENT_NO = '$row->SOURCE'";
                            break;
                        case "CHAIR":
                            $query = "SELECT FP.FIRST_NAME, COALESCE(FP.MIDDLE_NAME, '') MIDDLE_NAME, FP.LAST_NAME, 
                                    P.COURSE_CODE, P.DESCRIPTION, P.ID PROGRAM_ID
                                    FROM r_faculty_profile FP
                                    LEFT OUTER JOIN r_program P
                                        ON P.CHAIRPERSON = FP.FACULTY_CODE
                                    WHERE FACULTY_CODE = '$row->SOURCE'";
                            break;
                        default:
                            break;
                    }

                    $innerQueryresult = $this->db->query($query);

                    if ( $innerQueryresult->num_rows() > 0 ) {
                        
                        foreach ( $innerQueryresult->result() as $innerRow ) {
                            
                            $login = array(
                                'sourceId' => $row->SOURCE,
                                'username' => $row->USERNAME,
                                'firstName' => $innerRow->FIRST_NAME,
                                'middleName' => $innerRow->MIDDLE_NAME,
                                'lastName' => $innerRow->LAST_NAME,
                                'specialization' => $innerRow->SPECIALIZATION != null ? $innerRow->SPECIALIZATION : '',
                                'programType' => $innerRow->PROGRAM_TYPE != null ? $innerRow->PROGRAM_TYPE : '',
                                'programId' => $innerRow->PROGRAM_ID,
                                'programCode' => $innerRow->COURSE_CODE,
                                'programDesc' => $innerRow->DESCRIPTION,
                                'campus' => $innerRow->CAMPUS != null ? $innerRow->CAMPUS : '',
                                'campusId' => $innerRow->CAMPUS_ID != null ? $innerRow->CAMPUS_ID : '',
                                'role' => $row->ROLE_CODE,
                                'status' => $innerRow->STATUS != null ? $innerRow->STATUS : '',
                            );
                        }
                               
                    }
                        
                }	
			}
            
		}
		
		return $login;
    }
}