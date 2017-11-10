<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        
		$this->load->model("LoginModel", "m");
    }
    
    public function index() {
        echo "You are at: api/<b>login</b>/index";
    }
    
    public function matchLoginCredentials() {
    
        $username = $_POST['username'];
        $password = sha1($_POST['password']);
        
        // testing purposses
//        $username = "2017-00056-OUCM-0";
//        $password = sha1("password");
        // end testing purposes
        
        print(json_encode($this->m->matchLoginCredentials($username, $password)));
        
    }
    
}