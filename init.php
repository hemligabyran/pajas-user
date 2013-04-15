<?php defined('SYSPATH') or die('No direct script access.');

// Set version
define('PAJAS_USER_VERSION', '1.0');

// Check dependencies

// Compare kohana version, must be 3.2.x to be compatible
if (
	version_compare(Kohana::VERSION, '3.2', '<')
	|| version_compare(Kohana::VERSION, '3.3', '>=')
)
	throw new Kohana_Exception('Kohana version 3.2.x required, current version is :kohana_version',
		array(':kohana_version', Kohana::VERSION));

// Check for pajas-database
if ( ! version_compare(PAJAS_DATABASE_VERSION, '1.0', '>='))
	throw new Kohana_Exception('Pajas database module version 1.0 required');


// If page is restricted, check if visitor is logged in, and got access
// Check if the page is restricted
$user = new User;

if ( ! $user->has_access_to($_SERVER['REQUEST_URI']) && $this->ignore_acl == FALSE)
{
	if ($this->acl_redirect_url) $this->redirect($this->acl_redirect_url);
	else                         throw new HTTP_Exception_403('403 Forbidden');
}
