<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ---------------------------------------------------------------------------

/**
 * Internal shortcut to the CI object
 *
 * Check for it ahead of time as I use this in many of my
 * CodeIgniter libraries, and would not want them to conflict.
 */
if (! function_exists('CI'))
{
	function &CI()
	{
		return get_instance();
	}
}

// Internal shortcut to the ExtID object
function &ExtID()
{
	return ExtID::get_instance();
}

// The unique exception class for ExtID
class ExtID_Exception extends Exception { }

// ---------------------------------------------------------------------------

// The filepath containing ExtID supportting files (WITH a trailing slash)
define('EXTID_PATH', dirname(__FILE__).'/extid/');

// The basename of the ExtID configuration file
define('EXTID_CONFIG_FILE', 'extid');

// The prefix used for all session values
define('EXTID_SESSION_PREFIX', 'extid_');

// The prefix user for storing config data in session
define('EXTID_SESSION_CONFIG', EXTID_SESSION_PREFIX.'config_');

// Authenticate by SREG OpenID
define('EXTID_AUTH_SREG', 'sreg');

// Authenticate by AX OpenID
define('EXTID_AUTH_AX', 'ax');

// Authenticate by 2Leg OAuth
define('EXTID_AUTH_2LEG', '2leg');

// Authenticate by 3Leg OAuth
define('EXTID_AUTH_3LEG', '3leg');

// Authenticate with the Facebook SDK
define('EXTID_AUTH_FACEBOOK', 'fbsdk');

// Authenticate with Twitter
define('EXTID_AUTH_TWITTER', 'twitter');

// The user data placeholder
define('EXTID_USERDATA', '%U');

// The address used for blogspot (put here to avoid some stupidity...)
define('EXTID_BLOGSPOT_ADDRESS', 'http://'.EXTID_USERDATA.'.blogspot.com');

// ---------------------------------------------------------------------------

/**
 * CodeIgniter External Authentication Class
 *
 * A general class for external ID authentication. Makes use of both OpenID
 * and OAuth to a wide variety of external authentication providers. The
 * built-in providers are Google (OpenID only), Facebook, Twitter, MySpace,
 * Yahoo, AOL, and Blogger.
 *
 * @package		CodeIgniter
 * @subpackage	ExtID
 * @category	Libraries
 * @author		James Brumond
 * @link		http://code.kbjrweb.com/project/extid
 */

class ExtID {

	/**
	 * Configuration reader
	 *
	 * @access  protected
	 * @param   string    the config item to read
	 * @return  mixed
	 */
	protected static $config = false;
	protected static function read_config($item)
	{
		// Check if the config file is loaded
		if (! self::$config)
		{
			self::$config = array( );
			CI()->config->load(EXTID_CONFIG_FILE, true);
		}
		
		// Check if the specific config item is loaded
		if (! isset(self::$config[$item]))
		{
			self::$config[$item] = CI()->config->item($item, EXTID_CONFIG_FILE);
		}
		
		return self::$config[$item];
	}
	
	/**
	 * Instance handler
	 *
	 * @access  public
	 * @return  self
	 */
	protected static $_instance;
	public static function get_instance()
	{
		if (! self::$_instance) new self();
		return self::$_instance;
	}
	
	/**
	 * Redirects to another page
	 */
	protected static function redirect($url)
	{
		CI()->load->helper('url');
		redirect($url);
	}
	
	/**
	 * A list of the valid providers with all of
	 * the needed configuration data.
	 */
	protected static $providers = array(
		'openid' => array(
			'auth_type' => EXTID_AUTH_SREG,
			'user_data' => 'OpenID Address',
			'address'   => EXTID_USERDATA,
			'text'      => 'Sign in with OpenID',
			'image'     => 'openid.png',
			'secure'    => false
		),
		'google' => array(
			'auth_type' => EXTID_AUTH_AX,
			'address'   => 'https://www.google.com/accounts/o8/id',
			'text'      => 'Sign in using your Google Account',
			'image'     => 'google.png',
			'secure'    => false
		),
		'yahoo' => array(
			'auth_type' => EXTID_AUTH_AX,
			'address'   => 'https://www.yahoo.com',
			'text'      => 'Sign in using Yahoo!',
			'image'     => 'yahoo.png',
			'secure'    => false
		),
		'aol' => array(
			'auth_type' => EXTID_AUTH_SREG,
			'address'   => 'openid.aol.com',
			'text'      => 'Sign in with your AOL Account',
			'image'     => 'aol.png',
			'secure'    => false
		),
		'myspace' => array(
			'auth_type' => EXTID_AUTH_SREG,
			'address'   => 'http://www.myspace.com',
			'text'      => 'Sign in using MySpaceID',
			'image'     => 'myspace.png',
			'secure'    => false
		),
		'blogger' => array(
			'auth_type' => EXTID_AUTH_SREG,
			'user_data' => 'Blogger Account',
			'address'   => EXTID_BLOGSPOT_ADDRESS,
			'text'      => 'Sign in with your Blogger Account',
			'image'     => 'blogger.png',
			'secure'    => false
		),
		'facebook' => array(
			'auth_type' => EXTID_AUTH_FACEBOOK,
			'text'      => 'Connect with Facebook',
			'image'     => 'facebook.png',
			'secure'    => true
		),
		'twitter' => array(
			'auth_type' => EXTID_AUTH_TWITTER,
			'text'      => 'Sign in with Twitter',
			'image'     => 'twitter.png',
			'secure'    => true
		)
	);
	
	/**
	 * The OAuth subclass
	 *
	 * @class   OAuth
	 * @file    ./extid/OAuth.php
	 */
	public $oauth;
	
	/**
	 * The OpenID subclass
	 *
	 * @class   EasyOpenID
	 * @file    ./extid/EasyOpenID.php
	 */
	public $openid;
	
	/**
	 * Constructor
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct()
	{
		self::$_instance =& $this;
		
		// Include the OAuth class
		require_once EXTID_PATH.'OAuth.php';
		$this->oauth = new OAuth();
		
		// Include the OpenID class
		require_once EXTID_PATH.'EasyOpenID.php';
		$this->openid = new EasyOpenID();
		
		// Set the random generator
		$rand_source = self::read_config('rand_source');
		if (! defined('Auth_OpenID_RAND_SOURCE') && $rand_source !== false)
		{
			define('Auth_OpenID_RAND_SOURCE', $rand_source);
		}
	}
	
	/**
	 * Throws an exception
	 *
	 * @access  public
	 * @param   string    the message
	 * @param   int       the error level
	 * @return  void
	 */
	public function raise($msg, $lvl = E_USER_ERROR)
	{
		throw new ExtID_Exception($msg, $lvl);
	}
	
	/**
	 * Builds a block of markup for a given provider
	 *
	 * @access  protected
	 * @param   array     the config options
	 * @return  string
	 */
	protected function generate_markup_block($config, $provider_config, $provider, $id)
	{
		// Build the attribute string
		$attr = $provider;
		if (isset($config['classname']))
		{
			$attr = $config['classname'].' '.$attr;
		}
		$attr = ' class="'.$attr.'"';
		
		$base_url = CI()->config->item('base_url');
		
		// Build the route
		$route = $config['route'];
		if ($route[0] === '/')
		{
			$route = substr($route, 1);
		}
		$route = $base_url.$route.'/'.$id.'/'.$provider;
		
		// Get the icon loader
		$icon = $provider_config['image'];
		if (isset($config['icon_route']))
		{
			$icon = $config['icon_route'].'/'.$icon;
		}
		
		// Build the style string
		$style = ' style="display: inline-block; background: transparent url('.$base_url.$icon.') center left no-repeat;"';
		
		// Build the text string
		$text = $provider_config['text'];
		if (isset($provider_config['hide_text']) && $provider_config['hide_text'])
		{
			$text = '<span style="height:1px;width:1px;visibility:hidden">'.$text.'</span>';
		}
		
		// Build the markup block
		$markup = array( '<li'.$attr.'><div>' );
		if (isset($provider_config['user_data']))
		{
			if (isset($config['force_link']) && $config['force_link'])
			{
				$text_line = '<a href="#"'.$style.'>'.$text.'</a>';
			}
			else
			{
				$text_line = '<span'.$style.'>'.$text.'</span>';
			}
			
			$markup[] = implode("\n", array(
				$text_line,
				'<form action="'.$route.'" method="post">',
				'<input type="text" name="user_data" value="" placeholder="'.$provider_config['user_data'].'" />',
				'<input type="submit" value="Sign In" />',
				'</form>'
			));
		}
		else
		{
			$markup[] = '<a href="'.$route.'"'.$style.'>'.$text.'</a>';
		}
		$markup[] = '</div></li>';
		
		return implode("\n", $markup);
	}
	
	/**
	 * Generates a config array for a given provider
	 *
	 * @access  protected
	 * @param   string    the provider
	 * @param   array     the config array
	 * @return  array
	 */
	protected function get_provider_config($provider, $config)
	{
		$provider_config = array();
		if (isset(self::$providers[$provider]))
		{
			$overrides = array();
			if (isset($config[$provider]))
			{
				$overrides = $config[$provider];
			}
			$provider_config = array_merge(self::$providers[$provider], $overrides);
		}
		return $provider_config;
	}
	
	/**
	 * Generates login markup
	 *
	 * @access  public
	 * @param   array     the config to use
	 * @return  string
	 */
	public function generate_login($config = null)
	{
		// Stores the configuration's unique ID
		$config_id = null;
		
		// Default to the default config settings
		if (! $config)
		{
			$config = 'default';
		}
		
		// If a config name was given, use it
		if (is_string($config))
		{
			$config_id = $config;
			$config = self::read_config($config);
		}
		
		// No valid configuration found
		if (! is_array($config))
		{
			return $this->raise('No valid configuration found');
		}
		
		// Make sure we have a providers list
		if (! isset($config['providers']) || ! is_array($config['providers']))
		{
			return $this->raise('No providers list given');
		}
		
		// Make sure we have a config ID
		if (! $config_id)
		{
			$config_id = substr(md5(serialize($config)), 0, 10);
		}
		
		// Check if there is an ID attribute
		$id_attr = '';
		if (isset($config['list_id']))
		{
			$id_attr = ' id="'.$config['list_id'].'"';
		}
		
		// Build the markup
		$markup = array( '<ul'.$id_attr.'>' );
		foreach ($config['providers'] as $provider)
		{
			if (! isset(self::$providers[$provider]))
			{
				return $this->raise('Unknown provider "'.$provider.'"');
			}
			$provider_config = $this->get_provider_config($provider, $config);
			$markup[] = $this->generate_markup_block($config, $provider_config, $provider, $config_id);
		}
		$markup[] = '</ul>';
		$markup = implode("\n", $markup);
		
		// Store the config in the session
		CI()->session->set_userdata(EXTID_SESSION_CONFIG.$config_id, serialize($config));
		
		return $markup;
	}
	
	/**
	 * Fetch mid-step data
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function fetch_midstep_data(&$config_id, &$provider, &$config, &$provider_config)
	{
		// Parse URL data
		$config_id = CI()->uri->segment(3);
		$provider = CI()->uri->segment(4);
		if (strpos($provider, '?') !== false)
		{
			$provider = explode('?', $provider);
			$provider = $provider[0];
		}
		
		// Fetch the config
		$config = unserialize(CI()->session->userdata(EXTID_SESSION_CONFIG.$config_id));
		$provider_config = $this->get_provider_config($provider, $config);
	
		// Fetch user data if needed
		$user_data = null;
		if (isset($provider_config['user_data']))
		{
			$user_data = CI()->input->post('user_data');
			if (! $user_data)
			{
				$user_data = CI()->session->userdata(EXTID_SESSION_CONFIG.$config_id.'userdata');
			}
			if (! $user_data || empty($user_data))
			{
				return $this->raise('No user input given', E_USER_WARNING);
			}
			CI()->session->set_userdata(EXTID_SESSION_CONFIG.$config_id.'userdata', $user_data);
			$provider_config['address'] = str_replace(EXTID_USERDATA, $user_data, $provider_config['address']);
		}
	}
	
	/**
	 * Handles a login attempt
	 *
	 * Note: This function will redirect the user
	 *
	 * @access  public
	 * @return  void
	 */
	public function authenticate()
	{
		$this->fetch_midstep_data($config_id, $provider, $config, $provider_config);
		
		// The finish auth route
		$next = $config['callback'].'/'.$config_id.'/'.$provider;
		
		// Login...
		switch ($provider_config['auth_type'])
		{
			case EXTID_AUTH_SREG:
				$err = $this->openid->try_auth_sreg($provider_config['address'], $next,
					array(), array( 'fullname', 'email', 'nickname' ), array());
			break;
			case EXTID_AUTH_AX:
				$err = $this->openid->try_auth_ax($provider_config['address'], $next,
					array(), array( 'firstname', 'lastname', 'email', 'nickname' ), array());
			break;
			case EXTID_AUTH_TWITTER:
				$this->oauth->twitter->initialize($provider_config['app_id'], $provider_config['secret']);
				$this->oauth->twitter->authenticate($next);
				self::redirect($this->oauth->twitter->login_url());
			break;
			case EXTID_AUTH_FACEBOOK:
				$this->oauth->facebook->initialize($provider_config['app_id'], $provider_config['secret']);
				if (! $this->oauth->facebook->is_active)
				{
					$next = $this->oauth->facebook->login_url(array( 'next' => CI()->config->item('base_url').$next ));
				}
				self::redirect($next);
			break;
		}
		
		// Handle errors
		if (isset($err))
		{
			if (is_int($err))
			{
				return $this->raise($this->openid->error_msg($err));
			}
			elseif (is_string($err))
			{
				return $this->raise($err);
			}
		}
	}
	
	/**
	 * Finishes up the authentication process
	 *
	 * @access  public
	 * @return  array
	 */
	public function finish_auth()
	{
		$this->fetch_midstep_data($config_id, $provider, $config, $provider_config);
		
		// Login...
		switch ($provider_config['auth_type'])
		{
			case EXTID_AUTH_SREG:
			case EXTID_AUTH_AX:
				$result = $this->openid->finish_auth();
				if (is_int($result))
				{
					return $this->raise($this->openid->error_msg($result), E_USER_ERROR);
				}
				// fetch data out of nested structures
				foreach ($result as $i => $j)
				{
					if (is_array($j))
					{
						$result[$i] = ((array_key_exists(0, $j)) ? $j[0] : null);
					}
				}
				if (! isset($result['fullname']) && isset($result['firstname']) && isset($result['lastname']))
				{
					$result['fullname'] = $result['firstname'].' '.$result['lastname'];
					unset($result['firstname']);
					unset($result['lastname']);
				}
				if (! isset($result['nickname']))
				{
					$result['nickname'] = null;
				}
			break;
			case EXTID_AUTH_TWITTER:
				$this->oauth->twitter->initialize($provider_config['app_id'], $provider_config['secret']);
				if ($this->oauth->twitter->is_active)
				{
					$data = $this->oauth->twitter->api('account/verify_credentials');
					$result = array(
						'nickname' => $data->screen_name,
						'fullname' => $data->name,
						'email'    => null,  // Twitter does not supply email for security purposes
						'id'       => $data->id  // Supply the user id for Twitter as there is no
					                             // guarentee of getting any other identifiable info
					);
				}
				else
				{
					$this->oauth->twitter->on_callback($config['callback']);
				}
			break;
			case EXTID_AUTH_FACEBOOK:
				$this->oauth->facebook->initialize($provider_config['app_id'], $provider_config['secret']);
				$data = $this->oauth->facebook->api('/me');
				$data = array_merge(array(
					'name' => null,
					'email' => null
				), $data);
				$result = array(
					'fullname' => $data['name'],
					'email'    => $data['email'],
					'nickname' => null,      // Facebook does not use usernames
					'id'       => $data['id']    // Supply the user id for Facebook as there is no
					                             // guarentee of getting any other identifiable info
				);
			break;
		}
		
		// Add extra provider data
		$result['provider'] = $provider;
		$result['address'] = @$provider_config['address'];  // Yeah, ok, i used a @, get over it
		$result['provider_is_secure'] = $provider_config['secure'];
		
		return $result;
	}
	
	/**
	 * Loads images for the login form
	 *
	 * @access  public
	 * @param   string    the image to load
	 * @return  void
	 */
	public function load_image($img = null)
	{
		// If mo image is given, default it to URI segment 3
		if (! $img)
		{
			$img = CI()->uri->segment(3);
		}
		
		// Check that we have a string value
		if (! is_string($img))
		{
			$this->raise('Invalid value given for parameter one, string expected', E_USER_ERROR);
		}
		
		// Check that the image exists
		$file_path = EXTID_PATH.'icons/'.$img;
		if (! file_exists($file_path) || ! is_file($file_path))
		{
			show_404();
		}
		
		// Read the file data
		CI()->load->helper('file');
		$contents = read_file($file_path);
		
		// Output the image
		CI()->output->set_header('Content-Type: image/png');
		CI()->output->set_output($contents);
	}

}










/* End of file ExtID.php */
/* Location ./system/application/libraries/ExtID.php */
