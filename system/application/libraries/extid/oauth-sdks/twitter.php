<?php

/**
 * This contains an interface for the twitter oauth system
 *
 * @author   James Brumond
 */

// Where to store the authentication token
define('TWITTER_SESSION_PREFIX', 'oauth_twitter_');

require_once dirname(__FILE__).'/twitter-oauth/twitteroauth/twitteroauth.php';

// The twitter class
class Twitter {

	protected static $api_calls = array(
		
	);

	protected $token;
	protected $app_id;
	protected $secret;
	protected $twitter;
	protected $is_active = false;

	public function __construct($params)
	{
		if (! is_array($params))
		{
			OAuth()->raise('Invalid parameter type for constructor Twitter()', E_USER_WARNING);
		}
		
		if (! isset($params['app_id']))
		{
			OAuth()->raise('No app ID given', E_USER_WARNING);
		}
		
		if (! isset($params['secret']))
		{
			OAuth()->raise('No app private key given', E_USER_WARNING);
		}
		
		CI()->load->library('session');
		$this->app_id = $params['app_id'];
		$this->secret = $params['secret'];
		
		$this->is_active = $this->read_session('active');
		if ($this->is_active)
		{
			$token = $this->get_token('access');
			$this->twitter = new TwitterOAuth($this->app_id, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
		}
	}
	
	// Read-only properties
	protected $readonly = array( 'is_active' );
	public function __get($name)
	{
		if (in_array($name, $this->readonly))
		{
			return $this->$name;
		}
	}
	
	// Read from the session
	protected function read_session($name)
	{
		return unserialize(CI()->session->userdata(TWITTER_SESSION_PREFIX.$name));
	}
	
	// Write to the session
	protected function write_session($name, $value = null)
	{
		if (is_array($name) && $value === null)
		{
			foreach ($name as $key => $value)
			{
				$this->write_session($key, $value);
			}
		}
		else
		{
			return CI()->session->set_userdata(TWITTER_SESSION_PREFIX.$name, serialize($value));
		}
	}
	
	// Erase from the session
	protected function erase_session($name)
	{
		if (is_array($name))
		{
			foreach ($name as $value)
			{
				$this->erase_session($value);
			}
		}
		else
		{
			return CI()->session->unset_userdata(TWITTER_SESSION_PREFIX.$name);
		}
	}
	
	// Write to token storage
	protected function store_token($token, $which = 'request')
	{
		$sess['app_id'] = $this->app_id;
		$sess['secret'] = $this->secret;
		$sess[$which.'_token'] = $token;
		$this->write_session($sess);
	}
	
	// Read from token storage
	protected function get_token($which = 'request')
	{
		$app_id = $this->read_session('app_id');
		$secret = $this->read_session('secret');
		if ($app_id === $this->app_id && $secret === $this->secret)
		{
			return $this->read_session($which.'_token');
		}
		return null;
	}
	
	// Reset the entire process
	public function reset()
	{
		$this->erase_session(array(
			'app_id', 'secret', 'request_token', 'access_token', 'active'
		));
		$this->is_active = false;
	}
	
	// Returns the twitter authentication URL
	public function get_login_url($signin = true)
	{
		return $this->twitter->getAuthorizeURL($this->get_token(), $signin);
	}
	
	// Starts the authentication proccess
	public function get_request_token($callback)
	{
		$this->twitter = new TwitterOAuth($this->app_id, $this->secret);
		$request_token = $this->twitter->getRequestToken($callback);
		$this->store_token($request_token);
	}
	
	// Called inside the callback route; 
	public function get_access_token()
	{
		$token = $this->get_token();
		if (! $token || ! isset($_REQUEST['oauth_verifier']))
		{
			OAuth()->raise('Token/verifier not available');
		}
		$this->twitter = new TwitterOAuth($this->app_id, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
		$access_token = $this->twitter->getAccessToken($_REQUEST['oauth_verifier']);
		$this->store_token($access_token, 'access');
		$this->write_session(array(
			'verifier' => $_REQUEST['oauth_verifier'],
			'active' => true
		));
		$this->is_active = true;
		return $access_token;
	}
	
	// Make a call to the API
	public function api($cmd, $params = array(), $method = 'GET')
	{
		if (! is_string($method))
		{
			OAuth()->raise('Invalid method given');
		}
		$method = strtolower($method);
		$result = $this->twitter->$method($cmd, $params);
		if (isset($result->error))
		{
			OAuth()->raise($result->error);
		}
		return $result;
	}

}


/* End of file twitter.php */
