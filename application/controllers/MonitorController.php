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
			Application_Model_General::prefixPhoneNumber($phone);
			$formDefaults['phone'] = $phone;
		}
		$date = $this->getRequest()->getParam('date');
		$time = $this->getRequest()->getParam('time');
		if (!empty($date) && !empty($time)) {
			$date .= ' ' . $time;
		} else if ($date === null) {
			$date = date('Y-m-d');
		}
		$formDefaults['date'] = $date;
		$formDefaults['time'] = $time;

		$stage = $this->getRequest()->getParam('stage');
		if (empty($stage) || $stage == 'All') {
			$stage = FALSE;
			$formDefaults['stage'] = 'All';
		} else {
			$formDefaults['stage'] = $stage;
		}
		$formDefaults['to'] = $to = $this->getRequest()->getParam('to');
		$formDefaults['from'] = $from = $this->getRequest()->getParam('from');
		$status = $formDefaults['status'] = $this->getRequest()->getParam('status', -1);
		$this->view->form->setDefaults($formDefaults);
		$this->view->requestsTable = $monitorModel->getAllLogData('Requests', $date, $phone, $request_id, $stage, $to, $from, $status);
		$this->view->requestsTableFields = array(
			'id' => 'Id',
			'request_id' => 'Request Id',
			'from_provider' => 'From',
			'to_provider' => 'To',
			'status' => 'Status',
			'last_request_time' => 'Last Request Time',
			'last_transaction' => 'Last Transaction',
			'flags' => 'Flags',
			'phone_number' => 'Phone',
			'transfer_time' => 'Transfer Time',
			'disconnect_time' => 'Disconnect Time',
			'connect_time' => 'Connect Time',
//			'auto_check' => 'Auto Check',
//			'cron_lock' => 'Cron Lock',
		);
		$this->view->transactionsTable = $monitorModel->getAllLogData('Transactions', $date, $phone, $request_id, $stage, $to, $from, $status);
		$this->view->logsTable = $monitorModel->getAllLogData('Logs', $date, $phone, $request_id, $stage, $to, $from, $status);
	}

	public function editAction() {
		$model = new Application_Model_Monitor();
		$editForm = new Application_Form_Edit();
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
		$last_transaction = strtolower($data['last_transaction']);
		if ($data) {
			$model->createForm($editForm, $table, $data);
			$this->view->editForm = $editForm;
			if ($data['status']) {
				if (!empty($data['transfer_time'] && ($data['to_provider'] == Application_Model_General::getSettings('InternalProvider') || $data['from_provider'] == Application_Model_General::getSettings('InternalProvider'))) 
					&& ($last_transaction == 'kd_update' || $last_transaction == 'kd_update_response' || $last_transaction == 'request' || $last_transaction == 'request_response' || $last_transaction == 'update' || $last_transaction == 'update_response' || $last_transaction == 'execute_response')) {
					$executeData = array(
						'id' => $data['id'],
						'request_id' => $data['request_id'],
						'from_provider' => $data['from_provider'],
						'to_provider' => $data['to_provider'],
						'phone_number' => $data['phone_number'],
					);
					$executeForm = new Application_Form_Execute();
					$executeForm->setDefaults($executeData);
					if ($data['from_provider'] == Application_Model_General::getSettings('InternalProvider')) {
						$executeForm->getElement('submit')->setLabel('Receive execute');
					}
					$this->view->executeForm = $executeForm;
				}

				if ((!empty($data['transfer_time']) || $last_transaction == 'return_response') && $data['to_provider'] == Application_Model_General::getSettings('InternalProvider') && ($last_transaction == 'publish' || $last_transaction == 'execute_response' || $last_transaction == 'return_response')) {
					$publishForm = new Application_Form_Publish();
					$publishForm->setDefaults($data);
					$this->view->publishForm = $publishForm;
				}
			} else {
				$this->view->executeForm = '';
			}
		}

		if (strpos($last_transaction, 'publish') !== FALSE) {
			$this->view->publishNotResponse = Application_Model_General::getProvidersRequestWithoutPublishResponse($data['request_id'], $data['from_provider'], $data['to_provider']);
		}
		$this->view->headLink()->appendStylesheet(Application_Model_General::getBaseUrl() . '/css/style.css');
	}

	public function executeAction() {
		$params = $this->getRequest()->getParams();
		if (isset($params['from_provider']) && $params['from_provider'] == Application_Model_General::getSettings('InternalProvider')) {
			$params['FROM_PROVIDER'] = $params['from_provider'];
			$params['PROCESS_TYPE'] = 'PORT';
			$params['MSG_TYPE'] = 'Execute';
			$params['NUMBER'] = $params['phone_number'];
			$params['MANUAL'] = 1;
			$params['REQUEST_ID'] = $params['request_id'];
			unset($params['phone_number']);
			unset($params['from_provider']);
			unset($params['to_provider']);
			$url = '/provider/internal';
		} else if (isset($params['from_provider']) && $params['to_provider'] == Application_Model_General::getSettings('InternalProvider')) {
			if (isset($params['TO_PROVIDER'])) {
				$params['TO'] = $params['TO_PROVIDER'];
			} else if (isset($params['to_provider'])) {
				$params['TO'] = $params['to_provider'];
			}
			$url = '/cron/transfer';
		} else {
			die("something got wrong");
		}
		unset($params['controller']);
		unset($params['action']);
		unset($params['module']);

		$success = Application_Model_General::forkProcess($url, $params, true);
		if ($success) {
			$params['success'] = 1;
			$params['message'] = 'Execute sent';
		} else {
			$params['success'] = 0;
			$params['message'] = 'Execute failed';
		}
		$this->_redirect(Application_Model_General::getBaseUrl() . '/monitor/?' . http_build_query($params));
	}

	public function publishAction() {
		$params = $this->getRequest()->getParams();

		if ($params['last_transaction'] == 'Execute_response') {
			$update = array('last_transaction' => 'Publish');
			Application_Model_General::updateRequest($params['request_id'], $params['last_transaction'], $update);
		}

		$success = Application_Model_General::forkProcess('/cron/checkpublish', $params, true);
		if ($success) {
			$params['success'] = 1;
			$params['message'] = 'Execute sent';
		} else {
			$params['success'] = 0;
			$params['message'] = 'Execute failed';
		}
		$this->_redirect(Application_Model_General::getBaseUrl() . '/monitor/?' . http_build_query($params));
	}

	public function requestAction() {
		$form = new Application_Form_Request();
		$params = $this->getRequest()->getParams();
		$form->setDefaults($params);
		$this->view->form = $form;

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
		Application_Model_General::prefixPhoneNumber($params['NUMBER']);
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

		$success = Application_Model_General::forkProcess($url, $args, true);
		if ($success) {
			$params['success'] = 1;
			$params['message'] = 'Request sent';
		} else {
			$params['success'] = 0;
			$params['message'] = 'Request failed';
		}

		$this->_redirect(Application_Model_General::getBaseUrl() . '/monitor/request?' . http_build_query($params));
	}

}
