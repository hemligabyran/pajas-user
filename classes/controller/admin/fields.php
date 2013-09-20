<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Admin_Fields extends Admincontroller {

	public function before()
	{
		$this->xslt_stylesheet = 'admin/users';
		xml::to_XML(array('admin_page' => 'Fields'), $this->xml_meta);
	}

	public function action_index()
	{
		$fields = array();
		foreach (User::get_data_fields() as $field_id => $field_name)
		{
			$fields['field id="'.$field_id.'"'] = $field_name;
		}

		$this->xml_content_users = $this->xml_content->appendChild($this->dom->createElement('users'));
		xml::to_XML($fields, $this->xml_content_users);
	}

	public function action_add_field()
	{
		if ( ! empty($_POST) && isset($_POST['field_name']))
		{
			$post = new Validation($_POST);
			$post->filter('trim');
			$post->rule('Valid::not_empty',           'field_name');
			$post->rule('User::field_name_available', 'field_name');

			if ($post->validate())
			{
				$post_values = $post->as_array();
				User::new_field($post_values['field_name']);
				$this->add_message('Field '.$post_values['field_name'].' added');
			}
			else
			{
				$this->add_error('Fix errors and try again');
				$this->add_form_errors($post->errors());

				$this->set_formdata($post->as_array());
			}
		}
	}

	public function action_edit_field()
	{
		$field_id = $this->request->param('options');

		xml::to_XML(
			array(
				'field' => array(
					'@id' => $field_id,
					'$content' => User::get_data_field_name($field_id)
				)
			),
			$this->xml_content
		);

		if (count($_POST) && isset($_POST['field_name']))
		{
			$post = new Validation($_POST);
			$post->filter('trim');
			$post->rule('Valid::not_empty', 'field_name');

			if ($post->validate())
			{
				$post_values = $post->as_array();
				if ($post_values['field_name'] != User::get_data_field_name($field_id) && ! User::field_name_available($post_values['field_name']))
				{
					$post->add_error('field_name', 'User::field_name_available');
				}
			}

			// Retry
			if ($post->validate())
			{
				$post_values = $post->as_array();
				User::update_field($field_id, $post_values['field_name']);
				$this->add_message('Field '.$post_values['field_name'].' updated');
				$this->set_formdata(array('field_name' => $post_values['field_name']));
			}
			else
			{
				$this->add_error('Fix errors and try again');
				$this->add_form_errors($post->errors());

				$this->set_formdata(array_intersect_key($post->as_array(), $_POST));
			}
		}
		else
		{
			$this->set_formdata(array('field_name' => User::get_data_field_name($field_id)));
		}
	}

	public function action_rm_field()
	{
		User::rm_field($this->request->param('options'));
		$this->redirect();
	}

}
