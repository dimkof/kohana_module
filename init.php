<?php defined('SYSPATH') or die ('No direct script access.');

if ( ! defined('DEFAULT_LANG')) {
	/**
	* setting the default language if it's not already set
	* if set to NULL, then the route won't include a language by default
	* if you want a language in the route, set default_lang to the language (ie, en-ca)
	*/
	define('DEFAULT_LANG', NULL);
}

if ( ! isset($lang_options)) {
	$lang_options = '(en-ca|fr-ca)';
}

$routes = Kohana::$config->load('xm.routes');

if ($routes['useradmin']) {
	Route::set('useradmin', '(<lang>/)useradmin(/<action>(/<id>))', array('lang' => $lang_options))
		->defaults(array(
			'controller' => 'useradmin',
			'lang' => DEFAULT_LANG,
			'action' => NULL,
	));
}

if ($routes['content_admin']) {
	// route for content admin
	Route::set('content_admin', 'content_admin(/<action>(/<id>))')
		->defaults(array(
			'controller' => 'content',
			'action' => 'index',
	));
}

if ($routes['dbchange']) {
	Route::set('dbchange', '(<lang>/)dbchange(/<action>)', array('lang' => $lang_options))
		->defaults(array(
			'controller' => 'dbchange',
			'lang' => DEFAULT_LANG,
			'action' => NULL,
	));
}