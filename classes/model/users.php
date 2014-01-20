<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Users extends Model
{

	protected $fields = TRUE;
	protected $get_by_fields;
	protected $ids;
	protected $limit = 100;
	protected $offset;
	protected $order_by = 'username';
	protected $search;
	protected $search_by_fields;

	public function get()
	{
		/*
		if ($this->fields)
		{
			$sql = 'SELECT
					u.id,
					u.username,
					udf.id   AS field_id,
					udf.name AS field_name,
					ud.data  AS field_data
				FROM
					     user_users_data  ud
					JOIN user_users       u   ON u.id   = ud.user_id
					JOIN user_data_fields udf ON udf.id = ud.field_id
				WHERE 1';

			if (is_array($this->fields))
			{
				$sql .= ' AND udf.name IN (';
				foreach ($this->fields as $field_name)
					$sql .= $this->pdo->quote($field_name).',';
				$sql = rtrim($sql, ',').')';
			}
		}

		if ($this->ids)      $sql .= ' AND u.id IN ('.implode(',', $this->ids).')';
		if ($this->search)   $sql .= ' AND (u.id IN (SELECT user_id FROM user_users_data WHERE data LIKE '.$this->pdo->quote($this->search).') OR username LIKE '.$this->pdo->quote($this->search).')';

		if ($this->order_by) $sql .= ' ORDER BY '.$this->pdo->quote($this->order_by);

		if ($this->limit)
		{
			$sql .= ' LIMIT '.$this->limit;
			if ($this->offset) $sql .= ' OFFSET '.$this->offset;
		}

		$users = array();
		foreach ($this->pdo->query($sql) as $row)
		{
			$users[$row['id']]['id']                 = $row['id'];
			$users[$row['id']]['username']           = $row['username'];
			$users[$row['id']][$row['field_name']][] = $row['field_data'];
		}

		return $users;
		*/

		$data_fields = array();
		$sql         = 'SELECT users.id,users.username,';

		if ( ! empty($this->fields))
		{
			$fields_sql = 'SELECT id, name FROM user_data_fields ';

			if (is_array($this->fields))
			{
				$fields_sql .= 'WHERE name IN (';
				foreach ($this->fields as $return_field)
					$fields_sql .= $this->pdo->quote($return_field).',';

				$fields_sql = substr($fields_sql, 0, strlen($fields_sql) - 1).') ';
			}

			$fields_sql .= 'ORDER BY name;';

			foreach ($this->pdo->query($fields_sql) as $row)
			{
				$sql .= '
					(
						SELECT GROUP_CONCAT(data ORDER BY data SEPARATOR \', \')
						FROM user_users_data
						WHERE field_id = '.$row['id'].' AND user_id = users.id
						ORDER BY data
					) AS '.Mysql::quote_identifier($row['name']).',';
				$data_fields[$row['id']] = $row['name'];
			}
		}

		$sql  = substr($sql, 0, strlen($sql) - 1);

		$sql .= ' FROM user_users AS users LEFT JOIN user_users_data AS users_data ON users_data.user_id = users.id';
		$sql .= ' WHERE 1 = 1';

		// Searches
			if ($this->search || $this->search_by_fields) $sql .= ' AND (';

			if ($this->search)
			{
				$sql .= 'username LIKE '.$this->pdo->quote('%'.$this->search.'%').'
					OR users_data.data LIKE '.$this->pdo->quote('%'.$this->search.'%').'
					OR users.id = '.$this->pdo->quote($this->search).' OR';
			}

			if ( ! empty($this->search_by_fields))
			{
				foreach ($this->search_by_fields as $field => $search_string)
				{
					if (is_array($search_string))
					{
						foreach ($search_string as $this_search_string)
							$sql .= ' users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data LIKE '.$this->pdo->quote('%'.$this_search_string.'%').') OR';
					}
					elseif ($search_string === TRUE)
						$sql .= ' users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).')) OR';
					else
						$sql .= ' users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data LIKE '.$this->pdo->quote('%'.$search_string.'%').') OR';
				}
			}

			if ($this->search || $this->search_by_fields) $sql = substr($sql, 0, strlen($sql) - 3).')';

		if ($this->get_by_fields)
		{
			foreach ($this->get_by_fields as $field => $search_string)
			{
				if (is_array($search_string))
				{
					foreach ($search_string as $this_search_string)
						$sql .= ' AND users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data = '.$this->pdo->quote($this_search_string).')';
				}
				elseif ($search_string === TRUE)
					$sql .= ' AND users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).'))';
				else
					$sql .= ' AND users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data = '.$this->pdo->quote($search_string).')';
			}
		}

		$sql .= ' GROUP BY users.id';

		if ($this->order_by)
		{
			if (is_string($this->order_by) && in_array($this->order_by, $data_fields))
				$sql .= ' ORDER BY IF(ISNULL('.Mysql::quote_identifier($this->order_by).'),1,0),'.Mysql::quote_identifier($this->order_by);
			elseif ($this->order_by == 'username')
				$sql .= ' ORDER BY username';
			elseif (is_array($this->order_by))
			{
				$order_by_set = FALSE;

				foreach ($this->order_by as $field => $order)
				{
					if (in_array($field, $data_fields) || $field == 'username')
					{
						if ( ! $order_by_set)
						{
							$sql .= ' ORDER BY ';
							$order_by_set = TRUE;
						}

						if ($field == 'username')
							$sql .= 'username';
						else
							$sql .= 'IF(ISNULL('.Mysql::quote_identifier($field).'),1,0),'.Mysql::quote_identifier($field);

						if ($order == 'ASC' || $order == 'DESC') $sql .= ' '.$order;

						$sql .= ',';
					}
				}
				if ($order_by_set) $sql = substr($sql, 0, strlen($sql) - 1);
			}
		}
		/**/
		if ($this->limit)
		{
			$sql .= ' LIMIT '.$this->limit;

			if ($this->offset) $sql .= ' OFFSET '.$this->offset;
		}

		return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function fields($fields)
	{
		if (is_array($fields))    $this->fields = $fields;
		elseif ($fields === TRUE) $this->fields = TRUE;
		else                      $this->fields = NULL;

		return $this;
	}

	public function get_by_fields($array)
	{
		if ($array === NULL) $this->get_by_fields = NULL;
		else                 $this->get_by_fields = $array;

		return $this;
	}

	public function ids($array)
	{
		if ($array === NULL) $this->ids = NULL;
		else
		{
			if (empty($array)) $array = array(-1); // No matches should be found
			else
			{
				$array = array_map('intval', $array);

				$this->ids = $array;
			}
		}

		return $this;
	}

	public function limit($int)
	{
		if ($int === NULL) $this->limit = NULL;
		else               $this->limit = (int) $int;

		return $this;
	}

	public function offset($int)
	{
		if ($int === NULL) $this->offset = NULL;
		else               $this->offset = (int) $int;

		return $this;
	}

	public function order_by($order_by)
	{
		if ($order_by === NULL) $this->order_by = NULL;
		else                    $this->order_by = $order_by;

		return $this;
	}

	public function search($string)
	{
		if ($string === NULL) $this->search = NULL;
		else                  $this->search = (string) $string;

		return $this;
	}

	public function search_by_fields($array)
	{
		if ($array === NULL) $this->search_by_fields = NULL;
		else                 $this->search_by_fields = $array;

		return $this;
	}

}


class _____Model_Users extends Model
{

	/**
	 * The database driver
	 *
	 * @var obj
	 */
	static $driver;

	/**
	 * Loads the driver if it has not been loaded yet, then returns it
	 *
	 * @return Driver object
	 * @author Johnny Karhinen, http://fullkorn.nu, johnny@fullkorn.nu
	 */
	public static function driver()
	{
		if (self::$driver == NULL) self::set_driver();
		return self::$driver;
	}

	/**
	 * Set the database driver
	 *
	 * @return boolean
	 */
	public static function set_driver()
	{
		$driver_name = 'Driver_Users_'.ucfirst(Kohana::$config->load('user.driver'));
		return (self::$driver = new $driver_name);
	}

	public function get()
	{
		return self::driver()->get();
	}

}