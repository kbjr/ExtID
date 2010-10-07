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
		$config = array(
			'route' => 'auth/login',
			'list_id' => 'login',
			'classname' => 'login-item',
			'icon_route' => 'auth/load_icon',
			'callback' => 'auth/callback',
			'providers' => array( 'openid', 'facebook', 'twitter', 'google', 'yahoo', 'aol', 'blogger' ),
			'facebook' => array(
				'app_id' => '122621151124354',
				'secret' => '57d1575a06df819ea0a12f146f7cf6f5'
			),
			'twitter' => array(
				'app_id' => 'WufM1zUzvOA3zJX335kCZA',
				'secret' => 'Fat3eReSifyozkktebMDaigcGifRwnLQgtMCYrpc'
			)
		);
		$data['login_code'] = $this->extid->generate_login($config);
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
