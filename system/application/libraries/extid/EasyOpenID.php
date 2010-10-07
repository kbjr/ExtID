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
 * CodeIgniter EasyOpenID Class
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

require_once dirname(__FILE__).'/OpenID.php';

/**
 * A class for abstracting seperate OpenID requests
 *
 * @class  Eoid
 */
class Eoid {

	public $provider       = null;
	public $provider_data  = null;
	public $return_to      = null;
	public $required       = array( );
	public $optional       = array( );
	public $policies       = array(
		PAPE_AUTH_MULTI_FACTOR_PHYSICAL => 0,
		PAPE_AUTH_MULTI_FACTOR          => 0,
		PAPE_AUTH_PHISHING_RESISTANT    => 0
	);

}

/**
 * The simplified active-use version of the OpenID class
 *
 * @class   EasyOpenID
 * @parent  OpenID
 */
class EasyOpenID extends OpenID {
	
	protected $eoid = null;
	protected $data = false;
	
	/**
	 * Constructor
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->eoid = new Eoid();
	}
	
	/**
	 * Reset all active-use data (replace the Eoid
	 * with a new one)
	 *
	 * @access  public
	 * @return  self
	 */
	public function reset()
	{
		$this->eoid = new Eoid();
		return $this;
	}
	
	/**
	 * Set a PAPE policy as on or off
	 *
	 * @access  public
	 * @param   int      the policy to set
	 * @param   bool     turn it on (TRUE) or off (FALSE)
	 * @return  self
	 */
	public function set_policy($policy, $flag = true)
	{
		$flag = ($flag) ? 1 : 0;
		if (array_key_exists($policy, $this->eoid->policies))
		{
			$this->eoid->policies[$policy] = $flag;
		}
		return $this;
	}
	
	/**
	 * Set the multi-factor PAPE policy as on or off
	 *
	 * @access  public
	 * @param   bool     turn it on (TRUE) or off (FALSE)
	 * @return  self
	 */
	public function multi_factor($flag = true)
	{
		$this->set_policy(PAPE_AUTH_MULTI_FACTOR, $flag);
		return $this;
	}
	
	/**
	 * Set the multi-factor physical PAPE policy as on or off
	 *
	 * @access  public
	 * @param   bool     turn it on (TRUE) or off (FALSE)
	 * @return  self
	 */
	public function multi_factor_physical($flag = true)
	{
		$this->set_policy(PAPE_AUTH_MULTI_FACTOR_PHYSICAL, $flag);
		return $this;
	}
	
	/**
	 * Set the phishing resistant PAPE policy as on or off
	 *
	 * @access  public
	 * @param   bool     turn it on (TRUE) or off (FALSE)
	 * @return  self
	 */
	public function phishing_resistant($flag = true)
	{
		$this->set_policy(PAPE_AUTH_PHISHING_RESISTANT, $flag);
		return $this;
	}
	
	/**
	 * Set the OpenID provider
	 *
	 * @access  public
	 * @param   string   the provider to use
	 * @param   string   any secondary data needed (currently
	 *                   only used by the blogger function).
	 * @return  self
	 */
	public function provider($provider, $provider_data = null)
	{
		if (is_string($provider))
			$this->eoid->provider = $provider;
		if (is_string($provider_data))
			$this->eoid->provider_data = $provider_data;
		return $this;
	}
	
	/**
	 * Set the route to return to after authentication
	 * (the "finish auth" page)
	 *
	 * @access  public
	 * @param   string   the route to redirect to
	 * @return  self
	 */
	public function return_to($route)
	{
		if (is_string($route))
			$this->eoid->return_to = $route;
		return $this;
	}
	
	/**
	 * Value or values requested as "required" from the provider
	 *
	 * @access  public
	 * @param   mixed    string value or array of values
	 * @return  self
	 */
	public function required($value)
	{
		if (is_string($value))
			$this->eoid->required[] = $value;
		if (is_array($value))
			$this->eoid->required = array_merge($value, $this->eoid->required);
		$this->eoid->required = array_unique($this->eoid->required);
		return $this;
	}
	
	/**
	 * Value or values requested as "optional" from the provider
	 *
	 * @access  public
	 * @param   mixed    string value or array of values
	 * @return  self
	 */
	public function optional($value)
	{
		if (is_string($value))
			$this->eoid->optional[] = $value;
		if (is_array($value))
			$this->eoid->optional = array_merge($value, $this->eoid->optional);
		$this->eoid->optional = array_unique($this->eoid->optional);
		return $this;
	}
	
	protected $types = array(
		'google'  => 'ax',
		'yahoo'   => 'ax',
		'myspace' => 'sreg',
		'blogger' => 'sreg',
		'aol'     => 'sreg'
	);
	
	/**
	 * Parse SREG parameters into proper format
	 *
	 * @access  protected
	 * @param   array    values to parse
	 * @param   &array   where to put them when we're finished
	 * @return  self
	 */
	protected function parse_sreg($from, &$to)
	{
		foreach ($from as $field)
		{
			switch ($field)
			{
				case 'fullname':
				case 'name':
					$to[] = 'fullname';
				break;
				case 'nickname':
				case 'username':
					$to[] = 'nickname';
				break;
				case 'email':
					$to[] = 'email';
				break;
			}
		}
		$to = array_unique($to);
	}
	
	/**
	 * Parse AX parameters into proper format
	 *
	 * @access  protected
	 * @param   array    values to parse
	 * @param   &array   where to put them when we're finished
	 * @return  void
	 */
	protected function parse_ax($from, &$to)
	{
		foreach ($from as $field)
		{
			switch ($field)
			{
				case 'fname':
				case 'firstname':
					$to[] = 'fname';
				break;
				case 'lname':
				case 'lastname':
					$to[] = 'lname';
				break;
				case 'fullname':
				case 'name':
					$to[] = 'fname';
					$to[] = 'lname';
				break;
				case 'nickname':
				case 'username':
					$to[] = 'nickname';
				break;
				case 'email':
					$to[] = 'email';
				break;
			}
		}
		$to = array_unique($to);
	}
	
	/**
	 * Parse all parameters for a request, SREG or AX
	 *
	 * @access  protected
	 * @param   string   "sreg" or "ax"
	 * @return  array
	 */
	protected function parse_request($type)
	{
		$required = $optional = array( );
		if ($type == 'sreg')
		{
			$this->parse_sreg($this->eoid->required, $required);
			$this->parse_sreg($this->eoid->optional, $optional);
		}
		else
		{
			$this->parse_ax($this->eoid->required, $required);
			$this->parse_ax($this->eoid->optional, $optional);
		}
		return array($required, $optional);
	}
	
	/**
	 * Parse the stored policies into a usable list
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function parse_policies()
	{
		$policies = array( );
		foreach ($this->eoid->policies as $policy => $flag)
		{
			if ($flag) $policies[] = $policy;
		}
		return $policies;
	}
	
	/**
	 * Make the request
	 *
	 * Note: Upon success, this function will redirect the page.
	 *
	 * @access  public
	 * @param   string   if not using a built-in provider, is it SREG or AX
	 * @return  int
	 */
	public function make_request($type = 'sreg')
	{
		if (isset($this->types[$this->eoid->provider]))
		{
			$type = $this->types[$this->eoid->provider];
		}
		if (is_string($this->eoid->provider) && is_string($this->eoid->return_to))
		{
			$return = $this->eoid->return_to;
			$policies = $this->parse_policies();
			list($required, $optional) = $this->parse_request($type);
			switch ($this->eoid->provider)
			{
				case 'google':
					$result = $this->try_auth_google($return, $policies, $required, $optional);
				break;
				case 'yahoo':
					$result = $this->try_auth_yahoo($return, $policies, $required, $optional);
				break;
				case 'myspace':
					$result = $this->try_auth_myspace($return, $policies, $required, $optional);
				break;
				case 'blogger':
					$result = $this->try_auth_blogger($this->eoid->provider_data, $return, $policies, $required, $optional);
				break;
				case 'aol':
					$result = $this->try_auth_aol($return, $policies, $required, $optional);
				break;
				default:
					if (strtolower($type) == 'sreg')
					{
						$result = $this->try_auth_sreg($this->eoid->provider, $return, $policies, $required, $optional);
					}
					elseif (strtolower($type) == 'ax')
					{
						$result = $this->try_auth_ax($this->eoid->provider, $return, $policies, $required, $optional);
					}
					else
					{
						return $this->_throw_error('Invalid provider type "'.$type.'"');
					}
				break;
			}
		}
		else
		{
			return $this->_throw_error(OPENID_RETURN_NO_URL);
		}
		
		return $this->_throw_error($result);
	}
	
	/**
	 * Fetch and store the OpenID provider response
	 *
	 * Note: when using the popup mode, this function will close the window.
	 *
	 * @access  public
	 * @return  self
	 */
	public function fetch_response()
	{
		$resp = $this->finish_auth();
		if (! is_int($resp))
		{
			$this->ci->session->set_userdata('_openid_data', $resp);
			$this->data = $resp;
		}
		else
		{
			$this->_error = $this->error_msg($resp);
		}
		return $this;
	}
	
	/**
	 * Get the user data.
	 *
	 * @access  public
	 * @return  mixed
	 */
	public function result()
	{
		// get the result from the session
		$result = $this->ci->session->userdata('_openid_data');
		$this->ci->session->unset_userdata('_openid_data');
		
		$result = ($result !== false) ? $result : $this->data;
		
		if (is_array($result))
		{
			// fetch data out of nested structures
			foreach ($result as $i => $j)
			{
				if (is_array($j))
				{
					$result[$i] = ((array_key_exists(0, $j)) ? $j[0] : null);
				}
			}
		}
		else
		{
			return false;
		}
		
		return $result;
	}

}

/* End of file EasyOpenID.php */
/* Location: ./system/libraries/EasyOpenID.php */
