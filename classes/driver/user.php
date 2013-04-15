<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Driver_User extends Model
{

	public function __construct()
	{
		parent::__construct();
		if (Kohana::$environment == Kohana::DEVELOPMENT)
		{
			if ( ! $this->check_db_structure())
			{
				$this->create_db_structure();
				$this->insert_initial_data();
			}
		}
	}

	/**
	 * Create the db structure
	 *
	 * @return boolean
	 */
	abstract protected function create_db_structure();

	/**
	 * Returns true/false depending on if the db structure exists or not
	 *
	 * @return bool
	 * @author Johnny Karhinen, http://fullkorn.nu, johnny@fullkorn.nu
	 */
	abstract protected function check_db_structure();

	/**
	 * Create the first user, who also becomes an administrator.
	 *
	 * @return void
	 * @author Johnny Karhinen, http://fullkorn.nu, johnny@fullkorn.nu
	 */
	protected function insert_initial_data() {
		$this->new_field('role');
		$user = new User(FALSE, FALSE, FALSE, 'tmp', FALSE);
		$user->new_user('admin', 'admin', array('role' => 'admin'));
		$this->new_role_uri('admin', 'admin*');
	}

	/**
	 * Get data field id by field name
	 *
	 * @param str $field_name
	 * @return int
	 */
	abstract public function get_data_field_id($field_name);

	/**
	 * Get data field name
	 *
	 * @param int $field_id
	 * @return str
	 */
	abstract public function get_data_field_name($field_id);

	/**
	 * Get data fields
	 *
	 * @return array - Field id as key, field name as value
	 */
	abstract public function get_data_fields();

	/**
	 * Get user data
	 *
	 * @param int $user_id
	 * @return array - ex array('firstname' => array('John'), 'lastname' => array('Smith'), 'email' => array('one@larvit.se','two@larvit.se))
	 */
	abstract public function get_user_data($user_id);

	/**
	 * Get user id by field
	 *
	 * @param str $field - Field name
	 * @param str $value - Field value (OPTIONAL)
	 * @return int (Will only return first row if several matches exists)
	 */
	abstract public function get_user_id_by_field($field, $value = FALSE);

	/**
	 * Get user id by username
	 *
	 * @param str $username
	 * @return int
	 */
	abstract public function get_user_id_by_username($username);

	/**
	 * Get user id by username and (ENCRYPTED!!!) password
	 *
	 * @param str $username
	 * @param str $password - Should already be encrypted!
	 * @return int
	 */
	abstract public function get_user_id_by_username_and_password($username, $password);

	/**
	 * Get username by user id
	 *
	 * @param int $user_id
	 * @return str
	 */
	abstract public function get_username_by_id($user_id);

	/**
	 * Get list of users
	 *
	 * @param str $q             - Used as a search string in
	 *                             all data fields and username.
	 * @param int $start         - Limit the search to start from this row (0 means include
	 *                             all, 1 will omit the first result)
	 * @param int $limit         - Limit amount of rows to be returned. FALSE will return
	 *                             infinite number or rows.
	 * @param str $order_by      - Field to order by, string or array('field' => 'ASC/DESC')
	 * @param arr $field_search  - Array where key is field name and value is field value (or array of field values)
	 * @param arr $return_fields - TRUE to return all fields, FALSE for none or array of fields to return
	 * @return array             - array(
	 *                               array(
	 *                                 'id' => 1,
	 *                                 'username' => 'johnsmith'
	 *                               ),
	 *                               array(
	 *                                 'id' => 2,
	 *                                 'username' => 'adamjohansson'
	 *                               )
	 *                             )
	 */
	abstract public function get_users($q, $start = FALSE, $limit = FALSE, $order_by = FALSE, $field_search = FALSE, $return_fields = TRUE);

	/**
	 * Get restricted URIs
	 *
	 * @return arr
	 */
	abstract public function get_restricted_URIs();

	/**
	 * Get the user roles
	 *
	 * @return arr - array('role' => array(uri1, uri2 etc)
	 */
	abstract public function get_roles();

	/**
	 * Add a new field
	 *
	 * @param str $field_name
	 * @return int - New field id
	 */
	abstract public function new_field($field_name);

	/**
	 * Add new role URI
	 * All URIs that matches these rules will be restricted
	 * and only accessible by logged in users that belongs to
	 * a matching role
	 *
	 * @param str $role - the role that should have access to this URI
	 * @param str $uri  - the URI that should be given access to
	 * @return boolean
	 */
	abstract public function new_role_uri($role, $uri);

	/**
	 * Add a new user
	 *
	 * @param str $username
	 * @param str $password - Should already be encrypted!
	 * @param arr $user_data - ex array('firstname' => 'John', 'lastname' => 'Smith', 'email' => array('one@larvit.se','two@larvit.se))
	 * @return int - The new user id
	 */
	abstract public function new_user($username, $password, $user_data = array());

	/**
	 * Remove a field
	 *
	 * @param int $field_id
	 * @return boolean
	 */
	abstract public function rm_field($field_id);

	/**
	 * Remove user
	 *
	 * @param int $user_id
	 * @return boolean
	 */
	abstract public function rm_user($user_id);

	/**
	 * Set data
	 *
	 * @param int $user_id
	 * @param arr $data                - ex array('firstname' => 'John', 'lastname' => 'Smith', 'email' => array('one@larvit.se','two@larvit.se))
	 * @param bol $clear_previous_data - If TRUE, the previous data in the present
	 *                                   fields will be cleared
	 * @return boolean
	 */
	abstract public function set_data($user_id, $data, $clear_previous_data = TRUE);

	/**
	 * Set password
	 *
	 * @param int $user_id
	 * @param str $password - ENCRYPTED
	 * @return boolean
	 */
	abstract public function set_password($user_id, $password);

	/**
	 * Set username
	 *
	 * @param int $user_id
	 * @param str $username - Must be unique
	 * @return boolean
	 */
	abstract public function set_username($user_id, $username);

	/**
	 * Update field
	 *
	 * @param int $field_id
	 * @param str $field_name
	 * @return boolean
	 */
	abstract public function update_field($field_id, $field_name);

}
