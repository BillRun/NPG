<?php

/**
 * Controller for Test Processes 
 * 
 * @package         ApplicationController
 * @subpackage      CronController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Test Controller Class
 * 
 * @package ApplicationController
 * @subpackage TestController
 */
class TestController extends Zend_Controller_Action {

	/**
	 * Test Controller Class
	 * Index Action - "http://SERVER/Test"
	 * This useful for testing the NGP
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function indexAction() {
		$reply = Application_Model_General::getSettings('test-response', 'true');
		$params = $this->getRequest()->getParams();
//		error_log(print_R($params, 1));
		switch (strtolower($reply)) {
			case 'rand':
			case 'random':
				$ack = $this->randResponse();
				break;
			case 'true':
				$ack = 'true';
				break;
			case 'ack00':
			default:
				$ack = 'Ack00';
				break;
		}
		$arr = array(
			'reqId' => $params['reqId'],
			'status' => $ack,
			'desc' => 'run on ' . $reply,
		);
		
		switch ($params['method']) {
			case "execute_transfer":
				$arr['more']['connect_time'] = time();
				break;
		}

		$response = json_encode($arr);
		$this->view->response = $response;
	}

	protected function randResponse() {
		$success = rand(0, 1);
		if ($success) {
			return 'Ack00';
		}
		$options = array(
			'Check' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Gen09',
				'Req02',
				'Req03',
				'Req05',
				'Req07',
			),
			'Request' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Gen09',
				'Req02',
				'Req03',
				'Req05',
				'Req07',
			),
			'Update' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Gen09',
				'Upd02',
				'Upd03',
				'Upd04',
				'Upd05',
				'Upd07',
			),
			'Update' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Gen09',
			),
			'Execute' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Gen09',
				'Exe02',
				'Exe04',
				'Exe06',
			),
			'Publish' => array(
				'Gen04',
				'Gen05',
				'Gen06',
				'Gen07',
				'Pub01',
				'Pub02',
				'Pub03',
				'Pub05',
			),
		);
		return rand(0, count($options[rand(0, count($options) - 1)]) - 1);
	}

}
