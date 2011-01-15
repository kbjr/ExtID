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
 * OpenID Directory Path
 *
 * @const  OPENID_DIRECTORY
 */
define('OPENID_DIRECTORY', dirname(__FILE__)."/php-openid/");

/**
 * No OpenID URL Given
 *
 * @const  OPENID_RETURN_NO_URL
 */
define('OPENID_RETURN_NO_URL', 10);

/**
 * Bad OpenID URL Given
 *
 * @const  OPENID_RETURN_BAD_URL
 */
define('OPENID_RETURN_BAD_URL', 20);

/**
 * Could not connect to verifying server
 *
 * @const  OPENID_RETURN_NO_CONNECT
 */
define('OPENID_RETURN_NO_CONNECT', 30);

/**
 * Verification canceled
 *
 * @const  OPENID_RETURN_CANCEL
 */
define('OPENID_RETURN_CANCEL', 40);

/**
 * Verification failure
 *
 * @const  OPENID_RETURN_FAILURE
 */
define('OPENID_RETURN_FAILURE', 50);

/**
 * CodeIgniter OpenID Class
 *
 * This class enables the easy use of OpenID authentication with auto-access
 * to several OpenID servers as well as a general OpenID URI entry ability.
 *
 * @package		CodeIgniter
 * @subpackage	EasyOpenID
 * @category	Libraries
 * @author		James Brumond
 * @link		http://code.kbjrweb.com/project/easyopenid
 */
class Openid {

/*
 * Statics
 */
	
	protected static $default_config = array(
		'store_method'       => 'file',
		'store_path'         => '/tmp/_php_consumer_test',
		'associations_table' => 'oid_associations',
		'nonces_table'       => 'oid_nonces',
		'popup_auth'         => TRUE
	);
	
	/**
	 * Reads an item from config
	 *
	 * @access  protected
	 * @param   string   the item to read
	 * @return  mixed
	 */
	protected static $config = null;
	protected static function read_config($item)
	{
		if (! self::$config)
		{
			self::$config = CI()->config->item('openid', 'extid');
		}
		$conf = null;
		if (isset(self::$config[$item]))
		{
			$conf = self::$config[$item];
		}
		elseif (isset(self::$default_config[$item]))
		{
			$conf = self::$default_config[$item];
		}
		return $conf;
	}
	
	/**
	 * Include needed files
	 *
	 * @access  protected
	 * @return  void
	 */
	protected static function do_includes()
	{
		/**
		 * Require the OpenID consumer code.
		 */
		require_once OPENID_DIRECTORY.'Auth/OpenID/Consumer.php';
		/**
		 * Require the needed "store" file.
		 */
		$store_type = self::read_config('store_method');
		switch ($store_type)
		{
			case 'file':
				require_once OPENID_DIRECTORY.'Auth/OpenID/FileStore.php';
			break;
			case 'database':
				require_once OPENID_DIRECTORY.'EasyOpenID_Database.php';
			break;
			default:
				throw new Exception("OpenID store_method is invalid.");
			break;
		}
		/**
		 * Require the Simple Registration extension API. (just about everyone)
		 */
		require_once OPENID_DIRECTORY.'Auth/OpenID/SReg.php';
		/**
		 * Require the AX extension API. (google and yahoo)
		 */
		require_once OPENID_DIRECTORY.'Auth/OpenID/AX.php';
		/**
		 * Require the PAPE extension module.
		 */
		require_once OPENID_DIRECTORY.'Auth/OpenID/PAPE.php';
		/**
		 * Require the session class.
		 */
		require_once OPENID_DIRECTORY.'EasyOpenID_Session.php';
		/**
		 * Require the UI extension module.
		 */
		require_once OPENID_DIRECTORY.'Auth/OpenID/UI.php';
	}

/*
 * Magic Methods
 */
	
	/**
	 * Constructor
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct()
	{
		CI()->config->load('extid', TRUE);
		CI()->load->library('session');
		self::do_includes();
		$this->pape_policy_uris = array(
			PAPE_AUTH_MULTI_FACTOR_PHYSICAL,
			PAPE_AUTH_MULTI_FACTOR,
			PAPE_AUTH_PHISHING_RESISTANT
		);
	}

/*
 * Private Properties
 */
	
	protected $ax_properties = array(
		'email'     => 'http://axschema.org/contact/email',
		'firstname' => 'http://axschema.org/namePerson/first',
		'fname'     => 'http://axschema.org/namePerson/first',
		'lastname'  => 'http://axschema.org/namePerson/last',
		'lname'     => 'http://axschema.org/namePerson/last',
		'username'  => 'http://axschema.org/namePerson/friendly',
		'nickname'  => 'http://axschema.org/namePerson/friendly'
	);
	
	protected $ax_aliases = array(
		'http://axschema.org/contact/email'       => 'email',
		'http://axschema.org/namePerson/first'    => 'firstname',
		'http://axschema.org/namePerson/last'     => 'lastname',
		'http://axschema.org/namePerson/friendly' => 'nickname'
	);
	
	protected $providers = array(
		'openid'  => 'Sign in with OpenID',
		'google'  => 'Sign in using your Google Account',
		'yahoo'   => 'Sign in using Yahoo!',
		'blogger' => 'Sign in with your Blogger Account',
		'myspace' => 'Sign in using MySpaceID',
		'aol'     => 'Sign in with your AOL Account'
	);
	
	protected $_error = null;
	
	protected $use_popup = false;

/*
 * Public Properties
 */
	
	public $pape_policy_uris = null;

/*
 * Private Methods
 */
	
	/**
	 * Read an item from config.
	 *
	 * @access  protected
	 * @param   string   the item to read
	 * @return  mixed
	 */
	protected function _read_config($item)
	{
		$conf = CI()->config->item($item, 'openid');
		$conf = ((! $conf && array_key_exists($item, self::$default_config)) ?
			self::$default_config[$item] : $conf);
		return $conf;
	}

	/**
	 * Create a store object.
	 *
	 * @access  protected
	 * @return  mixed
	 */
	protected function &_get_store()
	{
		$store_type = $this->_read_config('store_method');
		switch ($store_type)
		{
			case 'file':
				$store_path = $this->_read_config('store_path');
				if (!file_exists($store_path) && !mkdir($store_path))
				{
					throw new Exception("Could not create the FileStore directory '$store_path'. ".
					" Please check the effective permissions.");
				}
				$r = new Auth_OpenID_FileStore($store_path);
			break;
			case 'database':
				$conn = new EasyOpenID_Database();
				$r = new Auth_OpenID_SQLStore($conn,
					$this->_read_config('associations_table'),
					$this->_read_config('nonces_table'));
			break;
		}
		return $r;
	}
	
	/**
	 * Gets a session handler
	 *
	 * @access  protected
	 * @return  OpenID_Session
	 */
	protected function &_get_session()
	{
		$r = new OpenID_Session();
		return $r;
	}

	/**
	 * Create a consumer object.
	 *
	 * @access  protected
	 * @return  Auth_OpenID_Consumer
	 */
	protected function &_get_consumer()
	{
		$store = $this->_get_store();
		$sess = $this->_get_session();
		$r = new Auth_OpenID_Consumer($store, $sess);
		return $r;
	}

	/**
	 * Create a popup enabling UI object.
	 *
	 * @access  protected
	 * @param   &string   the return to address
	 * @return  OpenID_UI_Request
	 */
	protected function &_get_ui(&$return_to)
	{
		CI()->session->set_userdata('_openid_popup', true);
		$r = new OpenID_UI_Request();
		$r->setIcon();
		$r->setPopup();
		return $r;
	}

	/**
	 * Create the current scheme (http or https).
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function _get_scheme()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on')
		{
			$scheme .= 's';
		}
		return $scheme;
	}

	/**
	 * Create the base url.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function _get_trust_root()
	{
		static $base_url;
		if (! $base_url)
		{
			$base_url = CI()->config->item('base_url');
			$index_page = CI()->config->item('index_page');
			if (! empty($index_page))
			{
				$base_url.$index_page.'/';
			}
		}
		return $base_url;
	}

	/**
	 * Escape a string.
	 *
	 * @access  protected
	 * @param   string   the string to escape
	 * @return  string
	*/
	protected function _escape($str)
	{
		return htmlentities($str);
	}

	/**
	 * Return a url to the current path.
	 *
	 * @access  protected
	 * @return  string
	 */
	protected function _get_self()
	{
		return $this->_get_trust_root().substr(CI()->uri->uri_string(), 1);
	}

	/**
	 * Turns an absolute path into a URL.
	 *
	 * @access  protected
	 * @param   string   the path to convert
	 * @return  string
	 */
	protected function _make_url($path)
	{
		return ((empty($_SERVER['HTTPS'])) ? "http" : "https") . "://" .
			$_SERVER['HTTP_HOST'] . str_replace($_SERVER['DOCUMENT_ROOT'], "", $path);
	}

	/**
	 * Creates the <img /> tag for an OpenID icon
	 *
	 * @access  protected
	 * @param   string   image url
	 * @return  string
	 */
	protected function _build_icon($url)
	{
		return '<img src="'.$url.'" alt="" title="" width="16" height="16" />';
	}
	
	/**
	 * Throws an internal error
	 *
	 * @access  protected
	 * @param   mixed    error constant/msg
	 * @return  int
	 */
	protected function _throw_error($error, $msg = null)
	{
		if (is_int($error))
		{
			if (is_string($msg))
			{
				$this->_error = $msg;
			}
			else
			{
				$this->_error = $this->error_msg($error);
			}
			$r = $error;
		}
		elseif (is_string($error))
		{
			$this->_error = $error;
			$r = 0;
		}
		
		return $r;
	}
	

/*
 * Public Methods
 */

	/**
	 * Get the last occuring error.
	 *
	 * @access  public
	 * @return  string
	 */
	public function last_error()
	{
		return $this->_error;
	}

	/**
	 * Get the error message for a given return code.
	 *
	 * @access  public
	 * @param   int      the return code
	 * @return  string
	 */
	public function error_msg($code)
	{
		switch ($code)
		{
			case OPENID_RETURN_NO_URL:
				$r = 'No OpenID provider URL was provided';
			break;
			case OPENID_RETURN_BAD_URL:
				$r = 'OpenID provider URL provided was invalid';
			break;
			case OPENID_RETURN_NO_CONNECT:
				$r = 'Connection to the verifying server could not be established';
			break;
			case OPENID_RETURN_CANCEL:
				$r = 'Verification was canceled';
			break;
			case OPENID_RETURN_FAILURE:
				$r = 'Verification failed';
			break;
			default:
				$r = 'No error has occured';
			break;
		}
		return $r;
	}

	/**
	 * Sets the "use popup" flag
	 *
	 * @access  public
	 * @param   bool   set to what
	 * @return  void
	 */
	public function use_popup($flag)
	{
		$this->use_popup = !! $flag;
		return $this;
	}

	/**
	 * Try to authenticate a user on Google accounts
	 *
	 * @access  public
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  string
	 */
	public function try_auth_google($return_to, $policy_uris = array(), $required = null, $optional = null)
	{
		CI()->session->set_userdata('EasyOpenID_provider', 'google');
		if (! $required) $required = array('email');
		if (! $optional) $optional = array('fname', 'lname');
		return $this->try_auth_ax('https://www.google.com/accounts/o8/id', $return_to, $policy_uris, $required, $optional);
	}

	/**
	 * Try to authenticate a user on Yahoo! accounts
	 *
	 * @access  public
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  string
	 */
	public function try_auth_yahoo($return_to, $policy_uris = array(), $required = null, $optional = null)
	{
		CI()->session->set_userdata('EasyOpenID_provider', 'yahoo');
		if (! $required) $required = array('email');
		if (! $optional) $optional = array('fname', 'lname');
		return $this->try_auth_ax('https://www.yahoo.com', $return_to, $policy_uris, $required, $optional);
	}

	/**
	 * Try to authenticate a user on MySpaceID accounts
	 *
	 * @access  public
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  string
	 */
	public function try_auth_myspace($return_to, $policy_uris = array(), $required = null, $optional = null)
	{
		CI()->session->set_userdata('EasyOpenID_provider', 'myspace');
		if (! $required) $required = array('email');
		if (! $optional) $optional = array('fullname');
		return $this->try_auth_sreg('http://www.myspace.com', $return_to, $policy_uris, $required, $optional);
	}

	/**
	 * Try to authenticate a user on Blogger/Blogspot accounts
	 *
	 * @access  public
	 * @param   string   the *.blogspot.com subdomain for the user's blog
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  string
	 */
	public function try_auth_blogger($blog_name, $return_to, $policy_uris = array(), $required = null, $optional = null)
	{
		CI()->session->set_userdata('EasyOpenID_provider', 'blogger');
		if (! $required) $required = array('email');
		if (! $optional) $optional = array('fullname');
		return $this->try_auth_sreg('http://'.$blog_name.'.blogspot.com', $return_to, $policy_uris, $required, $optional);
	}

	/**
	 * Try to authenticate a user on AOL accounts
	 *
	 * @access  public
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  string
	 */
	public function try_auth_aol($return_to, $policy_uris = array(), $required = null, $optional = null)
	{
		CI()->session->set_userdata('EasyOpenID_provider', 'aol');
		if (! $required) $required = array('email');
		if (! $optional) $optional = array('fullname');
		return $this->try_auth_sreg('openid.aol.com', $return_to, $policy_uris, $required, $optional);
	}

	/**
	 * Try to authenticate a user using AX.
	 *
	 * @access  public
	 * @param   string   the openid url
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    a list of required return values
	 * @param   array    a list of optional return values
	 * @return  void
	 */
	public function try_auth_ax($openid, $return_to, $policy_uris = array(),
		$required = array('nickname', 'email'), $optional = array('fname', 'lname'))
	{
		if ($return_to[0] == '/')
			$return_to = substr($return_to, 1);
		$return_to = $this->_get_trust_root().$return_to;
		
		if (empty($openid))
		{
			return $this->_throw_error(OPENID_RETURN_NO_URL);
		}
		
		// Create OpenID consumer
		$consumer = $this->_get_consumer();

		// Create an authentication request to the OpenID provider
		$auth = $consumer->begin($openid);
		
		// Create UI if needed
		if ($this->use_popup)
		{
			$ui = $this->_get_ui($return_to);
			$auth->addExtension($ui);
		}

		// Create AX fetch request
		$ax = new Auth_OpenID_AX_FetchRequest;

		// Create attribute request object
		// See http://code.google.com/apis/accounts/docs/OpenID.html#Parameters for parameters
		foreach (array(1 => $required, 0 => $optional) as $key => $data)
		{
			foreach ($data as $item)
			{
				if (array_key_exists($item, $this->ax_properties))
				{
					$ax->add(Auth_OpenID_AX_AttrInfo::make($this->ax_properties[$item], 1, $key, $item));
				}
				else
				{
					return $this->_throw_error('AX property "'.$item.'" is not registered in the library');
				}
			}
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.
		if (! CI()->session->userdata('EasyOpenID_provider'))
			CI()->session->set_userdata('EasyOpenID_provider', $openid);

		// Add AX fetch request to authentication request
		$auth->addExtension($ax);

		// Redirect to OpenID provider for authentication
		$url = $auth->redirectURL($this->_get_trust_root(), $return_to);
		
		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if ($auth->shouldSendRedirect())
		{
			$redirect_url = $auth->redirectURL($this->_get_trust_root(), $return_to);

			// If the redirect URL can't be built, die.
			if (Auth_OpenID::isFailure($redirect_url))
			{
				return $this->_throw_error(OPENID_RETURN_NO_CONNECT);
			}
			else
			{
				// Send redirect.
				header("Location: ".$redirect_url);
			}
		}
		else
		{
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth->htmlMarkup(
				$this->_get_trust_root(), $return_to, FALSE, array('id' => $form_id));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($form_html))
			{
				return $this->_throw_error(OPENID_RETURN_NO_CONNECT);
			}
			else
			{
				die($form_html);
			}
		}
	}

	/**
	 * Try to authenticate a user using SReg.
	 *
	 * @access  public
	 * @param   string   the openid url
	 * @param   string   the path to return to after authenticating
	 * @param   array    a list of PAPE policies to request from the server
	 * @param   array    required data
	 * @param   array    optional data
	 * @return  void
	 */
	public function try_auth_sreg($openid, $return_to, $policy_uris = array(),
		$required = array('nickname', 'email'), $optional = array('fullname'))
	{
		if ($return_to[0] == '/')
			$return_to = substr($return_to, 1);
		$return_to = $this->_get_trust_root().$return_to;
		
		if (empty($openid))
		{
			return $this->_throw_error(OPENID_RETURN_NO_URL);
		}
		
		$consumer = $this->_get_consumer();

		// Begin the OpenID authentication process.
		$auth_request = $consumer->begin($openid);

		// No auth request means we can't begin OpenID.
		if (! $auth_request)
		{
			return $this->_throw_error(OPENID_RETURN_BAD_URL);
		}
		
		// Create UI if needed
		if ($this->use_popup)
		{
			$ui = $this->_get_ui($return_to);
			$auth_request->addExtension($ui);
		}

		$sreg_request = Auth_OpenID_SRegRequest::build($required, $optional);

		if ($sreg_request)
		{
			$auth_request->addExtension($sreg_request);
		}

		$policy_uris = null;

		$pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
		if ($pape_request)
		{
			$auth_request->addExtension($pape_request);
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.
		if (! CI()->session->userdata('EasyOpenID_provider'))
			CI()->session->set_userdata('EasyOpenID_provider', $openid);

		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if ($auth_request->shouldSendRedirect())
		{
			$redirect_url = $auth_request->redirectURL($this->_get_trust_root(), $return_to);

			// If the redirect URL can't be built, die.
			if (Auth_OpenID::isFailure($redirect_url))
			{
				return $this->_throw_error(OPENID_RETURN_NO_CONNECT);
			}
			else
			{
				// Send redirect.
				header("Location: ".$redirect_url);
			}
		}
		else
		{
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth_request->htmlMarkup(
				$this->_get_trust_root(), $return_to, FALSE, array('id' => $form_id));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($form_html))
			{
				return $this->_throw_error(OPENID_RETURN_NO_CONNECT);
			}
			else
			{
				die($form_html);
			}
		}
	}

	/**
	 * Finish up authentication.
	 *
	 * @access  public
	 * @return  string
	 */
	public function finish_auth()
	{
		$msg = $error = $success = '';
		$consumer = $this->_get_consumer();

		// Complete the authentication process using the server's response.
		$response = $consumer->complete($this->_get_self());

		// Check the response status.
		if ($response->status == Auth_OpenID_CANCEL)
		{
			$data = $this->_throw_error(OPENID_RETURN_CANCEL);
		}
		else if ($response->status == Auth_OpenID_FAILURE)
		{
			$data = $this->_throw_error(OPENID_RETURN_FAILURE, $response->message);
		}
		else if ($response->status == Auth_OpenID_SUCCESS)
		{
			// if AX
			$ax_resp = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response);
			if ($response->endpoint->used_yadis && $ax_resp)
			{
				$data = $ax_resp->data;
				$new_data = array();
				foreach ($data as $i => $item)
				{
					if (array_key_exists($i, $this->ax_aliases))
					{
						$new_data[$this->ax_aliases[$i]] = $item;
					}
					else
					{
						$new_data[$i] = $item;
					}
				}
				$data = $new_data;
			}
			// if SReg
			else
			{
				$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
				$data = $sreg_resp->contents();
			}
		}
			
		// if handling a popup request
		if (CI()->session->userdata('_openid_popup'))
		{
			CI()->session->unset_userdata('_openid_popup');
			
			// store the data in a session
			CI()->session->set_userdata('_openid_data', $data);
			
			// close the popup
			include OPENID_DIRECTORY.'EasyOpenID_close.php';
			die();
		}
		// if a standard, in-window request
		else
		{
			return $data;
		}
	}

	/**
	 * Build an OpenID authentication markup.
	 *
	 * @access  public
	 * @param   string   where should the links go
	 * @param   array    a list of providers to offer
	 * @param   string   what route loads icons
	 * @param   bool     authenticate with popups
	 * @return  string
	 */
	public function build_auth($handler = null, $providers = array('openid', 'google', 'yahoo'),
		$icon_loader = null, $popups = null)
	{
		if (! is_string($handler) || empty($handler))
		{
			throw new Exception('redirect url is required');
		}
		
		if ($handler[0] == '/')
		{
			$handler = substr($handler, 1);
		}
		
		if (is_string($icon_loader))
		{
			if($icon_loader[0] == '/')
			{
				$icons = substr($icons, 1);
			}
			$icons = $this->_get_trust_root().$icon_loader.'/';
		}
		else
		{
			$icons = $this->_make_url(OPENID_DIRECTORY.'EasyOpenID_Icons/');
		}
		
		if ($popups === null)
		{
			$popups = $this->_read_config('popup_auth');
		}
		$popups =!! $popups;
		
		$links = array();
		foreach ($providers as $provider)
		{
			if (array_key_exists($provider, $this->providers))
			{
				$icon = $icons.$provider.'.png';
				$link = (object) array(
					'provider' => $provider,
					'href'     => $handler.'/'.$provider,
					'rel'      => 'openid',
					'text'     => $this->providers[$provider],
					'icon'     => $this->_build_icon($icon),
					'anchor'   => null
				);
				$link->anchor = '<a href="'.$link->href.'" rel="'.$link->rel.'">'.$link->icon.
					'<span>'.$link->text.'</span></a>';
				$links[] = $link;
				unset($link);
			}
			else
			{
				throw new Exception('provider "'.$provider.'" does not exist in the EasyOpenID system', E_USER_NOTICE);
			}
		}
		return $links;
	}

	/**
	 * Load an icon.
	 *
	 * @access  public
	 * @return  void
	 */
	public function icon_loader()
	{
		$which = CI()->uri->segment(3);
		if (preg_match('/(.+)\.png$/', $which, $match))
		{
			$which = $match[1];
		}
		$allowed = array('google', 'yahoo', 'myspace', 'blogger', 'aol', 'openid');
		if (in_array($which, $allowed))
		{
			$icon = OPENID_DIRECTORY.'EasyOpenID_Icons/'.$which.'.png';
			$icon = file_get_contents($icon);
			CI()->output->set_header('Content-Type: image/png');
			CI()->output->set_output($icon);
		}
	}

}


/* End of file OpenID.php */
/* Location: ./system/libraries/OpenID.php */
