<?php defined('SYSPATH') OR die('No direct access allowed.');

class Driver_User_Mysql extends Driver_User
{

	protected function check_db_structure()
	{
		$columns = $this->pdo->query('SHOW TABLES like \'user%\';')->fetchAll(PDO::FETCH_COLUMN);
		return count($columns) == 4;
	}

	protected function create_db_structure()
	{
		$this->pdo->query('CREATE TABLE `user_data_fields` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `name` (`name`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
		$this->pdo->query('CREATE TABLE `user_users` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
		$this->pdo->query('CREATE TABLE `user_users_data` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` bigint(20) DEFAULT NULL,
			`field_id` int(11) DEFAULT NULL,
			`data` text COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `users_fields` (`user_id`,`field_id`),
			KEY `field_id` (`field_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
		$this->pdo->query('ALTER TABLE `user_users_data`
			ADD CONSTRAINT `user_users_data_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `user_data_fields` (`id`),
			ADD CONSTRAINT `user_users_data_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_users` (`id`);');
		$this->pdo->query('CREATE TABLE IF NOT EXISTS `user_roles_rights` (
			`role` varchar(128) NOT NULL,
			`uri` varchar(128) NOT NULL,
			PRIMARY KEY (`role`,`uri`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	}

	public function get_data_field_id($field_name)
	{
		return $this->pdo->query('SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field_name))->fetchColumn();
	}

	public function get_data_field_name($field_id)
	{
		return $this->pdo->query('SELECT name FROM user_data_fields WHERE id = '.$this->pdo->quote($field_id))->fetchColumn();
	}

	public function get_data_fields()
	{
		$data_fields = array();
		foreach ($this->pdo->query('SELECT id, name FROM user_data_fields ORDER BY name;') as $row)
		{
			$data_fields[$row['id']] = $row['name'];
		}

		return $data_fields;
	}

	public function get_user_data($user_id)
	{
		$sql = '
			SELECT
				name AS field_name,
				data
			FROM
				user_users_data
				JOIN
					user_data_fields ON
						user_data_fields.id = user_users_data.field_id
			WHERE user_users_data.user_id = '.$this->pdo->quote($user_id);

		$user_data = array();

		foreach ($this->pdo->query($sql) as $row)
			$user_data[$row['field_name']][] = $row['data'];

		ksort($user_data);

		return $user_data;
	}

	public function get_user_id_by_field($field, $value = FALSE)
	{
		$sql = '
			SELECT user_id
			FROM user_users_data
			WHERE
				field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).')';

		if ($value)
			$sql .= '
				AND data = '.$this->pdo->quote($value);

		return $this->pdo->query($sql)->fetchColumn();
	}

	public function get_user_id_by_username($username)
	{
		return $this->pdo->query('SELECT id FROM user_users WHERE username = '.$this->pdo->quote($username))->fetchColumn();
	}

	public function get_user_id_by_username_and_password($username, $password)
	{
		return $this->pdo->query('SELECT id FROM user_users WHERE username = '.$this->pdo->quote($username).' AND password = '.$this->pdo->quote($password))->fetchColumn();
	}

	public function get_username_by_id($user_id)
	{
		return $this->pdo->query('SELECT username FROM user_users WHERE id = '.$this->pdo->quote($user_id))->fetchColumn();
	}

	public function get_restricted_URIs()
	{
		return $this->pdo->query('SELECT uri FROM user_roles_rights GROUP BY uri ORDER BY uri')->fetchAll(PDO::FETCH_COLUMN);
	}

	public function get_roles()
	{
		$roles = array();
		foreach ($this->pdo->query('SELECT * FROM user_roles_rights')->fetchAll(PDO::FETCH_ASSOC) as $row)
			$roles[$row['role']][] = $row['uri'];

		return $roles;
	}

	public function get_users($q = FALSE, $start = 0, $limit = FALSE, $order_by = FALSE, $field_search = FALSE, $return_fields = TRUE)
	{
		/* Same thing, but with JOIN. Have a bug when there are multiple values of the same data_field... and also isnt faster with current DB structure * /
			$columns = 'users.id,users.username,';
			$from    = 'user_users AS users ';
			foreach ($this->pdo->query('SELECT id, name FROM user_data_fields ORDER BY name;') as $row)
			{
				$columns .= 'GROUP_CONCAT('.Mysql::quote_identifier('data_'.$row['name']).'.data SEPARATOR \', \') AS '.Mysql::quote_identifier($row['name']).',';
				$from    .= 'LEFT JOIN user_users_data AS '.Mysql::quote_identifier('data_'.$row['name']).' ON '.Mysql::quote_identifier('data_'.$row['name']).'.user_id = users.id AND '.Mysql::quote_identifier('data_'.$row['name']).'.field_id = '.$row['id'].' ';
			}
			$columns = substr($columns, 0, strlen($columns) - 1);

			$sql = 'SELECT '.$columns.' FROM '.$from.' GROUP BY users.id';

			if ( ! empty($order_by))
			{
				if (is_string($order_by))
				{
					$sql .= ' ORDER BY IF(ISNULL(GROUP_CONCAT('.Mysql::quote_identifier('data_'.$row['name']).'.data SEPARATOR \', \')),1,0),GROUP_CONCAT('.Mysql::quote_identifier('data_'.$row['name']).'.data SEPARATOR \', \')';
				}
				elseif (is_array($order_by))
				{
					$sql .= ' ORDER BY ';
					foreach ($order_by as $field => $order)
					{
						$sql .= 'IF(ISNULL(GROUP_CONCAT('.Mysql::quote_identifier('data_'.$row['name']).'.data SEPARATOR \', \')),1,0),GROUP_CONCAT('.Mysql::quote_identifier('data_'.$row['name']).'.data SEPARATOR \', \')';

						if ($order == 'ASC' || $order == 'DESC') $sql .= ' '.$order;

						$sql .= ',';
					}
					$sql = substr($sql, 0, strlen($sql) - 1);
				}
			}
		/**/

		$data_fields = array();
		$sql         = 'SELECT users.id,users.username,';

		if ( ! empty($return_fields))
		{
			$fields_sql = 'SELECT id, name FROM user_data_fields ';

			if (is_array($return_fields))
			{
				$fields_sql .= 'WHERE name IN (';
				foreach ($return_fields as $return_field)
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

		if (is_string($q) || ! empty($field_search)) $sql .= ' AND (';

		if (is_string($q)) $sql .= 'username LIKE '.$this->pdo->quote('%'.$q.'%').' OR users_data.data LIKE '.$this->pdo->quote('%'.$q.'%').' OR';

		if ( ! empty($field_search))
		{
			foreach ($field_search as $field => $search_string)
			{
				if (is_array($search_string))
				{
					foreach ($search_string as $this_search_string)
						$sql .= 'users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data = '.$this->pdo->quote($this_search_string).') OR';
				}
				else
					$sql .= 'users.id IN (SELECT user_id FROM user_users_data WHERE field_id = (SELECT id FROM user_data_fields WHERE name = '.$this->pdo->quote($field).') AND data = '.$this->pdo->quote($search_string).') OR';
			}
		}

		if (is_string($q) || ! empty($field_search)) $sql = substr($sql, 0, strlen($sql) - 3).')';

		$sql .= ' GROUP BY users.id';

		if ( ! empty($order_by))
		{
			if (is_string($order_by) && in_array($order_by, $data_fields))
				$sql .= ' ORDER BY IF(ISNULL('.Mysql::quote_identifier($order_by).'),1,0),'.Mysql::quote_identifier($order_by);
			elseif ($order_by == 'username')
				$sql .= ' ORDER BY username';
			elseif (is_array($order_by))
			{
				$order_by_set = FALSE;

				foreach ($order_by as $field => $order)
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
		if ($limit)
		{
			if ($start) $sql .= ' LIMIT '.$start.','.$limit;
			else        $sql .= ' LIMIT '.$limit;
		}

		return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function new_field($field_name)
	{
		if ($field_name != 'id')
		{
			$this->pdo->exec('INSERT INTO user_data_fields (name) VALUES('.$this->pdo->quote($field_name).') ON DUPLICATE KEY UPDATE id = id');
			return $this->get_data_field_id($field_name);
		}
		else return FALSE;
	}

	public function new_role_uri($role, $uri)
	{
		return $this->pdo->exec('REPLACE INTO user_roles_rights (role, uri) VALUES('.$this->pdo->quote($role).','.$this->pdo->quote($uri).')');
	}

	public function new_user($username, $password, $user_data = array())
	{
		$this->pdo->exec('INSERT INTO user_users (username, password) VALUES('.$this->pdo->quote($username).','.$this->pdo->quote($password).')');

		$user_id = $this->pdo->lastInsertId();

		if (count($user_data))
		{
			$sql = 'INSERT INTO user_users_data (user_id,field_id,data) VALUES';
			foreach ($user_data as $field_name => $field_data)
			{
				if ($field_id = User::get_data_field_id($field_name))
				{
					if ( ! is_array($field_data))
					{
						$field_data = array($field_data);
					}

					foreach ($field_data as $field_data_piece)
					{
						$sql .= '('.$user_id.','.$field_id.','.$this->pdo->quote($field_data_piece).'),';
					}
				}
			}
			$this->pdo->exec(substr($sql, 0, strlen($sql) - 1));
		}

		return $user_id;
	}

	public function rm_field($field_id)
	{
		$this->pdo->exec('DELETE FROM user_users_data WHERE field_id = '.$this->pdo->quote($field_id));
		$this->pdo->exec('DELETE FROM user_data_fields WHERE id = '.$this->pdo->quote($field_id));
		return TRUE;
	}

	public function rm_role_uri($role, $uri)
	{
		$this->pdo->exec('DELETE FROM user_roles_rights WHERE role = '.$this->pdo->quote($role).' AND uri = '.$this->pdo->quote($uri));
	}

	public function rm_user($user_id)
	{
		$this->pdo->query('DELETE FROM user_users_data WHERE user_id = '.$this->pdo->quote($user_id));
		$this->pdo->query('DELETE FROM user_users WHERE id = '.$this->pdo->quote($user_id));
		return TRUE;
	}

	public function set_data($user_id, $user_data, $clear_previous_data = TRUE)
	{

		if ($clear_previous_data)
		{
			$fields = array();
			foreach ($user_data as $field => $content)
			{
				if ($field != 'id') $fields[] = $this->pdo->quote($field);
			}

			if (count($fields))
			{
				$this->pdo->query('
					DELETE user_users_data.*
					FROM user_users_data
						JOIN user_data_fields ON user_data_fields.id = user_users_data.field_id
					WHERE
						user_data_fields.name IN ('.implode(',', $fields).') AND
						user_users_data.user_id = '.$this->pdo->quote($user_id));
			}
		}

		if (count($user_data))
		{
			$sql = 'INSERT INTO user_users_data (user_id, field_id, data) VALUES';
			foreach ($user_data as $field => $content)
			{
				if ( ! is_array($content)) $content = array($content);

				foreach ($content as $content_piece)
				{
					if ($content_piece != '')
					{
						$sql .= '
							(
								'.$this->pdo->quote($user_id).',
								'.User::get_data_field_id($field).',
								'.$this->pdo->quote($content_piece).'
							),';
					}
				}
			}
			$sql = substr($sql, 0, strlen($sql) - 1);

			return $this->pdo->query($sql);
		}

		return TRUE;
	}

	public function set_password($user_id, $password)
	{
		return $this->pdo->query('UPDATE user_users SET password = '.$this->pdo->quote($password).' WHERE id = '.$this->pdo->quote($user_id));
	}

	public function set_username($user_id, $username)
	{
		return $this->pdo->query('UPDATE user_users SET username = '.$this->pdo->quote($username).' WHERE id = '.$this->pdo->quote($user_id));
	}

	public function update_field($field_id, $field_name)
	{
		return $this->pdo->exec('UPDATE user_data_fields SET name = '.$this->pdo->quote($field_name).' WHERE id = '.$this->pdo->quote($field_id));
	}

}
