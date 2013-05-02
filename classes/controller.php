<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller extends Kohana_Controller
{

	public function before()
	{
		// If page is restricted, check if visitor is logged in, and got access
		// Check if the page is restricted
		$user = new User;

		if ( ! isset($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI'] = '';

		if ( ! $user->has_access_to($_SERVER['REQUEST_URI']) && $this->ignore_acl == FALSE)
		{
			if ($this->acl_redirect_url) $this->redirect($this->acl_redirect_url);
			else                         throw new HTTP_Exception_403('403 Forbidden');
		}
	}

}