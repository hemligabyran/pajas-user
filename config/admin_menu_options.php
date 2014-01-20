<?php defined('SYSPATH') or die('No direct script access.');

return array(
	// Users admin pages
	'users' => array(
		'name'        => 'Users',
		'@category'   => 'Users',
		'description' => 'User admin',
		'href'        => 'users',
		'position'    => 1,
	),
	'fields' => array(
		'name'        => 'Fields',
		'@category'   => 'Users',
		'description' => 'User data fields',
		'href'        => 'fields',
		'position'    => 2,
	),
);