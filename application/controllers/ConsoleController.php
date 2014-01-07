<?php

/**
 * Controller for backward compatibility of clients 
 * 
 * @package         ApplicationController
 * @subpackage      ConsoleController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Console Controller Class
 * Old system of client start with console url
 * This controller will forward request to provider (SDOC NPG external SOAP)
 * 
 * @package ApplicationController
 * @subpackage ConsoleController
 */
class ConsoleController extends Zend_Controller_Action {

	public function preDispatch() {
		$this->forward('index', 'provider');
	}

}
