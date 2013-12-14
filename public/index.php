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

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
if (strpos($_SERVER['HTTP_HOST'], '1.1.1.1') !== FALSE ||
	strpos($_SERVER['HTTP_HOST'], 'localhost') !== FALSE) {
	if (strpos($_SERVER['REQUEST_URI'], 'np2') !== FALSE) {
		$config_path = DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .
			'np2.ini';
	} else if (strpos($_SERVER['REQUEST_URI'], 'np3') !== FALSE) {
		$config_path = DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .
			'np3.ini';
	} else {
		$config_path = DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .
			'np.ini';
	}
} else {
	$config_path = '/configs/application.ini';
}

$application = new Zend_Application(
	APPLICATION_ENV, APPLICATION_PATH . $config_path
);
$application->bootstrap()
	->run();
