<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Admin_Users extends Admincontroller {

	public function before()
	{
		// Set the name of the template to use
		$this->xslt_stylesheet = 'admin/users';
		xml::to_XML(array('admin_page' => 'Users'), $this->xml_meta);
	}

	public function action_index()
	{
		$this->xml_content_users = $this->xml_content->appendChild($this->dom->createElement('users'));

		$fields = array();
		foreach (User::get_data_fields() as $field_id => $field_name)
			$fields['field id="'.$field_id.'"'] = $field_name;

		xml::to_XML($fields, $this->xml_content_users);
		xml::to_XML(User::get_users(FALSE, 0, 10000, array('role' => 'ASC', 'username'=>'ASC'), FALSE, array('firstname', 'lastname', 'role')), $this->xml_content_users, 'user', 'id');
	}

	private function list_available_data_fields()
	{
		$fields = array();
		foreach (User::get_data_fields() as $field_id => $field_name)
		{
			$fields['field id="'.$field_id.'"'] = $field_name;
		}

		$this->xml_content_users = $this->xml_content->appendChild($this->dom->createElement('users'));
		xml::to_XML($fields, $this->xml_content_users);
	}

	public function action_user()
	{
		$formdata = array();
		if (isset($_GET['id']))
		{
			$user = new User($_GET['id'], FALSE, FALSE, 'default', FALSE);
			if ( ! $user->logged_in()) $this->redirect();
		}

		$this->list_available_data_fields();

		if ( ! empty($_POST) && isset($_POST['username']) && isset($_POST['password']))
		{
			$post = new Validation($_POST);
			$post->filter('trim');
			$post->filter('strtolower', 'username');
			$post->rule('Valid::not_empty', 'username');
			if (isset($user))
			{
				if ($_POST['username'] != $user->get_username())
				{
					$post->rule('User::username_available', 'username');
				}
			}
			else $post->rule('User::username_available', 'username');

			if ( ! isset($user)) $post->rule('Valid::not_empty', 'password');

			if (isset($_POST['do_add_field']))
			{
				// Add another user data field and save no data, but repopulate the form fields
				if ( ! isset($_SESSION['detail_fields'])) $_SESSION['detail_fields'] = array();

				$_SESSION['detail_fields'][] = $_POST['add_field'];

				// Reconstruct the form data to repopulate the form
				$formdata    = array();
				$counter     = 0;
				$post_values = $post->as_array();
				foreach ($post_values as $field => $data)
				{
					if (substr($field, 0, 8) == 'fieldid_')
					{
						foreach ($data as $data_piece)
						{
							$counter++;
							$formdata['field_'.substr($field, 8).'_'.$counter] = trim($data_piece);
						}
					}
					elseif ($field == 'username') $formdata[$field] = $post_values[$field];
				}
			}
			else
			{
				// Check for form errors
				if ($post->validate())
				{
					// No form errors, add the user!
					$post_values = $post->as_array();

					// Erase the empty data fields
					foreach ($post_values as $key => $value)
					{
						if (substr($key, 0, 8) == 'fieldid_' && is_array($value))
						{
							foreach ($value as $nr => $value_piece)
							{
								if ($value_piece == '') unset($post_values[$key][$nr]);
							}
						}
					}

					// Organize the field data and set the session fields
					$fields = $_SESSION['detail_fields'] = array();

					foreach ($post_values as $key => $value)
					{
						if (substr($key, 0, 6) == 'field_')
						{
							list($foobar, $field_id, $field_nr) = explode('_', $key);
							$fields[User::get_data_field_name($field_id)][] = $value;
						}
					}

					if ( ! isset($_GET['id']))
					{
						// Actually add the user
						User::factory()->new_user(
							$post_values['username'],
							$post_values['password'],
							$fields
						);
						$this->add_message('User '.$post_values['username'].' added');
					}
					elseif (isset($user))
					{
						$user->set_user_data(array_merge($fields, array('username' => $post_values['username'], 'password' => $post_values['password'])), TRUE);
						$this->add_message('User data saved');
					}
				}
				else
				{
					// Form errors detected!
					$this->add_error('Fix errors and try again');
					$this->add_form_errors($post->errors());
					$formdata    = array();
					$counter     = 0;
					$post_values = $post->as_array();
					foreach ($post_values as $field => $data)
					{
						if (substr($field, 0, 8) == 'fieldid_')
						{
							foreach ($data as $data_piece)
							{
								$counter++;
								$formdata['field_'.substr($field, 8).'_'.$counter] = trim($data_piece);
							}
						}
						elseif ($field == 'username') $formdata[$field] = $post_values[$field];
					}
				}
			}
		}

		if (isset($user))
		{
			$formdata = array('username' => $user->get_username(),);
			$counter  = 0;
			foreach ($user->get_user_data() as $field => $data)
			{
				foreach ($data as $data_piece)
				{
					$counter++;
					$formdata['field_'.User::get_data_field_id($field).'_'.$counter] = $data_piece;
				}
			}
		}

		if ( ! empty($_SESSION['detail_fields']))
		{
			foreach ($_SESSION['detail_fields'] as $field_id)
			{
				$counter = 1;
				while (isset($formdata['field_'.$field_id.'_'.$counter]))
				{
					$counter++;
				}
				$formdata['field_'.$field_id.'_'.$counter] = '';
			}
		}

		$this->set_formdata($formdata);
	}

	public function action_rm_user()
	{
		$user_id = $this->request->param('options');
		$user    = new User($user_id, FALSE, FALSE, 'default', FALSE);

		$user->rm_user();

		$this->redirect();
	}

}
