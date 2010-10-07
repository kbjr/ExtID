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

require_once OPENID_DIRECTORY.'Auth/Yadis/Manager.php';

/**
 * CodeIgniter OpenID Session Class
 *
 * This class is so php-openid can communicate with the codeigniter session
 * class.
 *
 * @package		CodeIgniter
 * @subpackage	EasyOpenID
 * @category	Libraries
 * @author		James Brumond
 * @link		http://code.kbjrweb.com/project/easyopenid
 */
class OpenID_Session extends Auth_Yadis_PHPSession {

	protected $ci = null;
	
	public function OpenID_Session()
	{
		$this->ci =& get_instance();
		$this->ci->load->library('session');
	}

	/**
	 * Set a session key/value pair.
	 *
	 * @param string $name The name of the session key to add.
	 * @param string $value The value to add to the session.
	 */
	function set($name, $value)
	{
		$this->ci->session->set_userdata($name, $value);
	}

	/**
	 * Get a key's value from the session.
	 *
	 * @param string $name The name of the key to retrieve.
	 * @param string $default The optional value to return if the key
	 * is not found in the session.
	 * @return string $result The key's value in the session or
	 * $default if it isn't found.
	 */
	function get($name, $default = null)
	{
		$r = $this->ci->session->userdata($name);
		$r = (($r === false) ? $default : $r);
		return $r;
	}

	/**
	 * Remove a key/value pair from the session.
	 *
	 * @param string $name The name of the key to remove.
	 */
	function del($name)
	{
		$this->ci->session->unset_userdata($name);
	}

	/**
	 * Return the contents of the session in array form.
	 */
	function contents()
	{
		return $this->session->userdata;
	}

}

/* End of file EasyOpenID_Session.php */
/* Location: ./system/application/libraries/php-openid/EasyOpenID_Session.php */
