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

// ------------------------------------------------------------------------

/**
 * CodeIgniter OAuth Class
 *
 * Adds various authentication abilities for using APIs such as Facebook
 * Connect and Twitter Signin.
 *
 * @package		CodeIgniter
 * @subpackage	EasyOpenID
 * @category	Libraries
 * @author		James Brumond
 * @link		http://code.kbjrweb.com/project/easyopenid
 */

// Internal shortcut to the CI object
if (! function_exists('CI'))
{
	function &CI()
	{
		return get_instance();
	}
}

// Interal shortcut to the OAuth object
function OAuth()
{
	return OAuth::get_instance();
}

// The system's temp directory
define('OAUTH_TMP_DIR', function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : realpath($_ENV["TMP"])); 

class OAuth {

	protected static $instance;
	protected static $config;
	public static function get_instance()
	{
		if (! self::$instance) new self();
		return self::$instance;
	}

	public function __construct()
	{
		self::$instance =& $this;
		
		if (! function_exists('curl_init'))
		{
			$this->raise('Facebook needs the CURL PHP extension.');
		}

		if (! function_exists('json_decode'))
		{
			throw new Exception('Facebook needs the JSON PHP extension.');
		}
		
		$this->facebook = new OAuth_Facebook();
		$this->twitter  = new OAuth_Twitter();
		
		CI()->load->helper('url');
	}
	
	public function read_config($item)
	{
		return CI()->config->item($item, 'openid');
	}
	
	public function raise($msg, $lvl = E_USER_ERROR)
	{
		throw new OAuth_Exception($msg, $lvl);
	}
	
	public $facebook;
	public $twitter;

}



/**
 * Does facebook authentication
 *
 * @class   OAuth_Facebook
 * @parent  OAuth
 */

class OAuth_Facebook { 
	
	// Holds config data
	protected static $config = false;
	protected static $defaults = array(
		'cookie' => true
	);
	
	// Constructor
	public function __construct()
	{
		// Include the SDK
		require_once dirname(__FILE__).'/oauth-sdks/facebook.php';
		
		// Read the config
		if (! self::$config)
		{
			self::$config = OAuth()->read_config('facebook_connect');
		}
		
		// Initialize if available
		if (isset(self::$config['app_id']) && is_string(self::$config['app_id']) &&
		isset(self::$config['secret']) && is_string(self::$config['secret'])) {
			$this->initialize(self::$config['app_id'], self::$config['secret']);
		}
	}
	
	// Read-only properties
	protected $readonly = array( 'is_active' );
	public function __get($name)
	{
		if (in_array($name, $this->readonly))
		{
			if (method_exists(__CLASS__, $name))
			{
				return $this->$name();
			}
			return $this->$name;
		}
	}
	
	// Application info
	protected $app_id;
	protected $secret;
	protected $cookie;
	protected $facebook;
	protected $session;
	protected $is_active = false;
	
	/**
	 * Initializes the class.
	 *
	 * @access  public
	 * @param   string    the app ID
	 * @param   string    the "secret"
	 * @return  self
	 */
	public function initialize($app_id, $secret, $cookie = null)
	{
		// Using cookies?
		if (! is_bool($cookie))
		{
			if (isset(self::$config['cookie']) && is_bool(self::$config['cookie']))
			{
				$cookie = self::$config['cookie'];
			}
			else
			{
				$cookie = self::$defaults['cookie'];
			}
		}
		
		// Set data
		$this->app_id = $app_id;
		$this->secret = $secret;
		$this->cookie = $cookie;
		
		// Initialize connection
		$this->facebook = new Facebook(array(
			'appId'  => $this->app_id,
			'secret' => $this->secret,
			'cookie' => $cookie
		));
		$this->session = $this->facebook->getSession();
		
		// Test the session
		$me = false;
		if ($this->session)
		{
			try
			{
				$uid = $this->facebook->getUser();
				$me  = $this->facebook->api('/me');
				
			}
			catch (FacebookApiException $e)
			{
				error_log($e);
			}
		}
		if ($me)
		{
			$this->is_active = true;
		}
		
		return $this;
	}
	
	/**
	 * Remove the session cookie
	 */
	public function reset()
	{
		$this->facebook->deleteSession();
	}
	
	/**
	 * Returns the needed markup/js to include the Facebook SDK into
	 * the website. This should not be put in the <head> of a document
	 * as it contains a <div> element.
	 *
	 * @access  public
	 * @return  string
	 */
	public function get_javascript_sdk()
	{
		return implode("\n", array(
			'<div id="fb-root"></div>',
			'<script type="text/javascript">',
			'  window.fbAsyncInit = function() {',
			'	FB.init({',
			'	  appId   : '.$this->app_id.',',
			'	  session : '.json_encode($this->session).',', // don't refetch the session when PHP already has it
			'	  status  : true, // check login status',
			'	  cookie  : '.(($this->cookie) ? 'true' : 'false').',',
			'	  xfbml   : true  // parse XFBML',
			'	});',
			'',
			'	// whenever the user logs in, we refresh the page',
			'	FB.Event.subscribe(\'auth.login\', function() {',
			'	  window.location.reload();',
			'	});',
			'  };',
			'',
			'  (function() {',
			'	var e = document.createElement(\'script\');',
			'	e.src = document.location.protocol + \'//connect.facebook.net/en_US/all.js\';',
			'	e.async = true;',
			'	document.getElementById(\'fb-root\').appendChild(e);',
			'  }());',
			'</script>'
		));
	}
	
	/**
	 * Gets the facebook data gathered by user login
	 *
	 * @access  public
	 * @param   string    what to collect
	 * @return  mixed
	 */
	public function api($cmd)
	{
		return $this->facebook->api($cmd);
	}
	
	/**
	 * Gets the facebook login URL
	 *
	 * @access  public
	 * @return  string
	 */
	public function login_url($data = array())
	{
		return $this->facebook->getLoginUrl($data);
	}
	
	/**
	 * Gets the facebook login URL
	 *
	 * @access  public
	 * @return  string
	 */
	public function logout_url()
	{
		return $this->facebook->getLogoutUrl();
	}
	
}



/**
 * Does twitter authentication
 *
 * @class   OAuth_Twitter
 * @parent  OAuth
 */

class OAuth_Twitter {
	
	// Holds config data
	protected static $config = false;

	protected $key;
	protected $secret;
	protected $token;
	protected $twitter;

	public function __construct()
	{
		require_once dirname(__FILE__).'/oauth-sdks/twitter.php';
		
		// Read the config
		if (! self::$config)
		{
			self::$config = OAuth()->read_config('twitter_oauth');
		}
		
		// Initialize if available
		if (isset(self::$config['key']) && is_string(self::$config['key']) &&
		isset(self::$config['secret']) && is_string(self::$config['secret'])) {
			$this->initialize(self::$config['app_id'], self::$config['secret']);
		}
	}
	
	protected function is_active()
	{
		return $this->twitter->is_active;
	}
	
	// Read-only properties
	protected $readonly = array( 'is_active' );
	public function __get($name)
	{
		if (in_array($name, $this->readonly))
		{
			if (method_exists(__CLASS__, $name))
			{
				return $this->$name();
			}
			return $this->$name;
		}
	}
	
	// Initialize the twitter handler
	public function initialize($key, $secret)
	{
		// Check the public key
		if (! is_string($key) || strlen($key) !== 22)
		{
			return OAuth()->raise('Invalid twitter key "'.$key.'"', E_USER_WARNING);
		}
		
		// Check the private key
		if (! is_string($secret) || strlen($secret) !== 40)
		{
			return OAuth()->raise('Invalid twitter secret "'.$secret.'"', E_USER_WARNING);
		}
		
		// Set data
		$this->key = $key;
		$this->secret = $secret;
		
		// Initialize
		$this->twitter = new Twitter(array(
			'app_id' => $key,
			'secret' => $secret
		));
	}
	
	public function authenticate($route)
	{
		if ($route[0] === '/')
		{
			$route = substr($route, 1);
		}
		$route = CI()->config->item('base_url').$route;
		return $this->twitter->get_request_token($route);
	}
	
	public function on_callback($route = null)
	{
		$this->twitter->get_access_token();
		if (is_string($route))
		{
			redirect($route);
		}
	}
	
	public function login_url()
	{
		return $this->twitter->get_login_url();
	}
	
	public function reset()
	{
		$this->twitter->reset();
	}
	
	public function api($cmd, $params = array(), $method = 'GET')
	{
		return $this->twitter->api($cmd, $params, $method);
	}
	
	public function image($img)
	{
		CI()->load->helper('file');
		$content = read_file(dirname(__FILE__).'/oauth-sdks/twitter-oauth/images/'.$img);
		header('Content-Type: image/png');
		die($content);
	}

}



// The unique exception class for OAuth errors; nothing special,
// just to seperate their's from our's.
class OAuth_Exception extends Exception { }



/* End of file OAuth.php */
/* Location: ./system/libraries/OAuth.php */
