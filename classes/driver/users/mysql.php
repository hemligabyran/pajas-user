<?php defined('SYSPATH') OR die('No direct access allowed.');

class Driver_Users_Mysql extends Driver_Users
{

	public function get()
	{
		return $this->pdo->query('SELECT * FROM user_users')->fetchAll(PDO::FETCH_ASSOC);
	}


}