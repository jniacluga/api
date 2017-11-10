<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
    
    var $m = null;
    
    function __construct() {
        parent::__construct();
        
		$this->load->model("HomeModel");
        $this->m = new HomeModel();
    }
    
    public function index() {
        echo "You are at: api/<b>home</b>/index";
    }
    
    public function loadHome() {
        
        $studentNumber = "2017-00056-OUCM-0";
        
        print(json_encode($this->m->loadHome($studentNumber)));
    }
    
}