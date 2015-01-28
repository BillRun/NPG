<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH . '/../library'),
	get_include_path(),
)));

// set timezone if not set
if (empty(ini_get('date.timezone'))) {
        $tz = 'Asia/Jerusalem';
        date_default_timezone_set($tz);
        ini_set('date.timezone', $tz);
}

/** Zend_Application */
require_once 'Zend/Application.php';

if (APPLICATION_ENV == 'development') {
	$host = $_SERVER['HTTP_HOST'];
	if (strpos($host, 'npg') === FALSE) {
			$host = 'npg' . substr($_SERVER['SERVER_PORT'], -1);
	}
} else if (APPLICATION_ENV == 'testing') {
	$host = strlen($_SERVER['REQUEST_URI']) > 3 ? substr($_SERVER['REQUEST_URI'], 1, 4) : 'testing';
} else {
	$host = APPLICATION_ENV;
}

switch ($host) {
	case 'npg1':
	case 'npg2':
	case 'npg3':
	case 'npg4':
	case 'npg5':
		$config_path = '/configs/' . $host . '.ini';
		break;
	case 'staging':
	case 'production':
	default:
		$config_path = '/configs/application.ini';
}

$application = new Zend_Application(
	APPLICATION_ENV, APPLICATION_PATH . $config_path
);
$application->bootstrap()
	->run();
