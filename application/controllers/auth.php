<?php

class Auth extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('ExtID');
	}
	
	// The main test page
	public function index()
	{
		$data['extid_config'] = 'default';
		$this->load->view('auth_test', $data);
	}
	
	// Handles the startup of the authentication process
	public function login()
	{
		try
		{
			$this->extid->authenticate();
		}
		catch (Exception $e)
		{
			$data['error'] = $e->getMessage();
			$this->load->view('auth_test', $data);
		}
	}
	
	// Handles the middle stage of a 3Leg auth (eg. Twitter) as well
	// as completing OpenID auths
	public function callback()
	{
		try
		{
			$data['result'] = $this->extid->finish_auth();
		}
		catch (Exception $e)
		{
			$data['error'] = $e->getMessage();
		}
		$this->load->view('auth_test', $data);
	}
	
	// Clears out the session
	public function clear()
	{
		$this->session->sess_destroy();
		$this->load->helper('url');
		redirect('auth');
	}
	
	// Loads resource files
	public function resource()
	{
		$this->extid->load_resource();
	}

}

/* End of file auth.php */
/* Location ./application/controllers/auth.php */
