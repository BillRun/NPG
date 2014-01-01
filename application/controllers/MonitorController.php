<?php

/**
 * Controller for monitor Processes 
 * 
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class MonitorController extends Zend_Controller_Action {
	
	public function init() {
		$this->_helper->layout->setLayout('monitor');
		$this->view->baseUrl = Application_Model_General::getBaseUrl();
		$this->view->active = $this->getRequest()->getActionName();
	}

	/**
	 * @method loggerAction 
	 * in this screen we monitor the transactions,requests and logs 
	 * tables . we do this by using the form at the top of the page
	 * which filters the data by number , date and time. it shows date
	 * from the date and time inserted until the day after.
	 * if no data is submitted to the form. it will show records for all numbers
	 * in db from this morning until tomorrow morning.
	 */
	public function indexAction() {
		$monitorModel = new Application_Model_Monitor();
		$this->view->form = new Application_Form_DPFilter();
		$formDefaults = array();
		$request_id = $this->getRequest()->getParam('request_id');
		if (empty($request_id)) {
			$request_id = FALSE;
			$formDefaults['request_id'] = '';
		} else {
			$this->view->reqlog = $monitorModel->getReqLog($request_id);
			$formDefaults['request_id'] = $request_id;
		}
		$phone = $this->getRequest()->getParam('phone');
		if (empty($phone)) {
			$phone = FALSE;
			$formDefaults['phone'] = '';
		} else {
			$formDefaults['phone'] = $phone;
		}
		$date = $this->getRequest()->getParam('date');
		$time = $this->getRequest()->getParam('time');
		$formDefaults['date'] = $date;
		$formDefaults['time'] = $time;
		if (!empty($date) && !empty($time)) {
			$date .= ' ' . $time;
		} else if ($date === null) {
			$date = date('Y-m-d');
		}

		$stage = $this->getRequest()->getParam('stage');
		if (empty($stage) || $stage == 'All') {
			$stage = FALSE;
			$formDefaults['stage'] = 'All';
		} else {
			$formDefaults['stage'] = $stage;
		}
		$this->view->form->setDefaults($formDefaults);
		$this->view->requestsTable = $monitorModel->getAllLogData('Requests', $date, $phone, $request_id, $stage);
		$this->view->requestsTableFields = array(
			'id' => 'id',
			'request_id' => 'request_id',
			'from_provider' => 'from_provider',
			'to_provider' => 'to_provider',
			'status' => 'status',
			'last_request_time' => 'last_request_time',
			'last_transaction' => 'last_transaction',
			'flags' => 'flags',
			'phone_number' => 'phone_number',
			'transfer_time' => 'transfer_time',
			'disconnect_time' => 'disconnect_time',
			'connect_time' => 'connect_time',
		); 
		$this->view->transactionsTable = $monitorModel->getAllLogData('Transactions', $date, $phone, $request_id, $stage);
		$this->view->logsTable = $monitorModel->getAllLogData('Logs', $date, $phone, $request_id, $stage);
	}

	public function editAction() {
		$model = new Application_Model_Monitor();
		$form = new Application_Form_Edit();
		if ($this->getRequest()->isPost()) {
			$post_data = $this->getRequest()->getPost();
			unset($post_data['submit']);
			$model->saveRow($post_data);
			$this->_redirect(Application_Model_General::getBaseUrl() . '/monitor?phone=' . (string) $post_data['phone_number']);
			exit('redirect...');
			//redirect to logger
		}
		$table = (string) $this->getRequest()->getParam('table');
		$id = (int) $this->getRequest()->getParam('id');
		$data = $model->getTableRow($table, $id);
		if ($data) {
			$model->createForm($form, $table, $data);
			$this->view->form = $form;
		}
		$this->view->headLink()->appendStylesheet(Application_Model_General::getBaseUrl() . '/css/style.css');
	}
	
	public function requestAction() {
		$form = new Application_Form_Request();
		$params = $this->getRequest()->getParams();
		$form->setDefaults($params);
		$this->view->form = $form;
		$this->view->form->setAction("/monitor/send/");

		if (isset($params['message'])) {
			$this->view->message = (string) $params['message'];
		}
		if (isset($params['success'])) {
			$this->view->success = (int) $params['success'];
		}
	}
	
	public function sendAction() {
		$params = $this->getRequest()->getParams();
		$url = 'Internal';
		$method = $params['MSG_TYPE'];
		$args = array(
			'method' => Application_Model_Internal::getMethodName($method),
			'msg_type' => $method,
			'provider' => $params['TO'],
			'number' => $params['NUMBER'],
			'request_time' => time(),
		);

		if ($method == 'Request' || $method == 'Update') {
			$args['transfer_time'] = $params['porttime'];
		} else if ($method == 'Execute_response') {
			$args['more']['connect_time'] = time();
		}

		$success = Application_Model_General::forkProcess($url, $args, 0, true);
		if ($success) {
			$params['success'] = 1;
			$params['message'] = 'Request sent';
		} else {
			$params['success'] = 0;
			$params['message'] = 'Request failed';
		}
		
		$this->redirect(Application_Model_General::getBaseUrl() . '/monitor/request?' . http_build_query($params));
	}

}
