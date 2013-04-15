<?php defined('SYSPATH') or die('No direct script access.');

return array(
//	'driver' => 'mysql',
	'driver'        => Kohana::$config->load('pdo.default.driver'),
	'root_password' => 'toor', // Set to FALSE to inactivate root account
	'password_salt' => 'change this to something random',
	'shared_key'    => 'this should also be something random',
);