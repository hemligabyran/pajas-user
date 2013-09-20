<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Driver_Users extends Model
{

	protected $ids;
	protected $limit = 100;
	protected $offset;

	public function __construct()
	{
		parent::__construct();
		if (Kohana::$environment == Kohana::DEVELOPMENT)
		{
			$user_driver = new Driver_User();

			if ( ! $user_driver->check_db_structure())
			{
				$user_driver->create_db_structure();
				$user_driver->insert_initial_data();
			}
		}
	}

	abstract public function get();

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

	public function search($string)
	{
		if ($string === NULL) $this->search = NULL;
		else                  $this->search = (string) $string;

		return $this;
	}

}