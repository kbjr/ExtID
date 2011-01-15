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
		$data['extid_config'] = 'default';
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
		try {
			$data['result'] = $this->extid->finish_auth();
		} catch (Exception $e) {
			$data['error'] = $e->getMessage();
		}
		$this->load->view('auth_test', $data);
	}
	
	// Loads resource files
	function resource()
	{
		$this->extid->load_resource();
	}

}

/* End of file auth.php */
/* Location ./application/controllers/auth.php */
