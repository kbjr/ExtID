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

/*
 * OpenID Database Class
 */
require_once OPENID_DIRECTORY.'Auth/OpenID/DatabaseConnection.php';

/*
 * OpenID SQLStore Class
 */
require_once OPENID_DIRECTORY.'Auth/OpenID/SQLStore.php';

/**
 * CodeIgniter OpenID Database Class
 *
 * This class is so php-openid can communicate with the codeigniter database
 * class.
 *
 * @package		CodeIgniter
 * @subpackage	EasyOpenID
 * @category	Libraries
 * @author		James Brumond
 * @link		http://code.kbjrweb.com/project/easyopenid
 */
class OpenID_Database extends Auth_OpenID_DatabaseConnection {

	protected $ci = null;

	public function OpenID_Database()
	{
		$this->ci =& get_instance();
		$this->ci->load->database();
	}

	/**
	 * Sets auto-commit mode on this database connection.
	 *
	 * @param bool $mode True if auto-commit is to be used; false if
	 * not.
	 */
	function autoCommit($mode)
	{
		if (! $mode)
		{
			$this->ci->db->trans_off();
		}
		else
		{
			$this->ci->db->trans_enabled = TRUE;
		}
	}

	/**
	 * Run an SQL query with the specified parameters, if any.
	 *
	 * @param string $sql An SQL string with placeholders.  The
	 * placeholders are assumed to be specific to the database engine
	 * for this connection.
	 *
	 * @param array $params An array of parameters to insert into the
	 * SQL string using this connection's escaping mechanism.
	 *
	 * @return mixed $result The result of calling this connection's
	 * internal query function.  The type of result depends on the
	 * underlying database engine.  This method is usually used when
	 * the result of a query is not important, like a DDL query.
	 */
	function query($sql, $params = array())
	{
		return $this->db->query($sql, $params);
	}

	/**
	 * Starts a transaction on this connection, if supported.
	 */
	function begin()
	{
		$this->ci->db->trans_begin();
	}

	/**
	 * Commits a transaction on this connection, if supported.
	 */
	function commit()
	{
		$this->ci->db->trans_commit();
	}

	/**
	 * Performs a rollback on this connection, if supported.
	 */
	function rollback()
	{
		$this->ci->db->trans_rollback();
	}

	/**
	 * Run an SQL query and return the first column of the first row
	 * of the result set, if any.
	 *
	 * @param string $sql An SQL string with placeholders.  The
	 * placeholders are assumed to be specific to the database engine
	 * for this connection.
	 *
	 * @param array $params An array of parameters to insert into the
	 * SQL string using this connection's escaping mechanism.
	 *
	 * @return mixed $result The value of the first column of the
	 * first row of the result set.  False if no such result was
	 * found.
	 */
	function getOne($sql, $params = array())
	{
		$row = $this->get_row($sql, $params);
		if (! $row)
			return false;
		foreach ($row as $col)
			return $col;
	}

	/**
	 * Run an SQL query and return the first row of the result set, if
	 * any.
	 *
	 * @param string $sql An SQL string with placeholders.  The
	 * placeholders are assumed to be specific to the database engine
	 * for this connection.
	 *
	 * @param array $params An array of parameters to insert into the
	 * SQL string using this connection's escaping mechanism.
	 *
	 * @return array $result The first row of the result set, if any,
	 * keyed on column name.  False if no such result was found.
	 */
	function getRow($sql, $params = array())
	{
		$query = $this->query($sql, $params);
		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}
		else
		{
			return false;
		}
	}

	/**
	 * Run an SQL query with the specified parameters, if any.
	 *
	 * @param string $sql An SQL string with placeholders.  The
	 * placeholders are assumed to be specific to the database engine
	 * for this connection.
	 *
	 * @param array $params An array of parameters to insert into the
	 * SQL string using this connection's escaping mechanism.
	 *
	 * @return array $result An array of arrays representing the
	 * result of the query; each array is keyed on column name.
	 */
	function getAll($sql, $params = array())
	{
		return $this->query($sql, $params)->result_array();
	}

}

/* End of file EasyOpenID_Database.php */
/* Location: ./system/application/libraries/php-openid/EasyOpenID_Database.php */
