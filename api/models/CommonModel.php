/*
    This is where all the common model functions will go
*/

<?php

class CommonModel extends CI_Model {
    
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
}