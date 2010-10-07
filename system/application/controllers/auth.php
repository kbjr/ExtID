<?php

class Auth extends Controller {

	function Auth()
	{
		parent::Controller();
		$this->load->library('ExtID');
	}
	
	// The main test page
	function index()
	{
		$data['login_code'] = $this->extid->generate_login('default');
		$this->load->view('auth_test', $data);
	}
	
	// Handles the startup of the authentication process
	function login()
	{
		$this->extid->authenticate();
	}
	
	// Handles the middle stage of a 3Leg auth (eg. Twitter) as well
	// as completing OpenID auths
	function callback()
	{
		$data = $this->extid->finish_auth();
		header('Content-Type: text/plain');
		print_r($data); die();
	}
	
	// Loads icons
	function load_icon()
	{
		$this->extid->load_image();
	}

}

/* End of file auth.php */
