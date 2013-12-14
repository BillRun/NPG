<?php

/**
 * Controller for debug Processes 
 * 
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class DebugController extends Zend_Controller_Action {

	protected $formatMySQLDate = 'yyyy-MM-dd HH:mm:ss';

	public function init() {
		
	}

	/**
	 * @method indexAction 
	 * function called for url /debug/
	 * puts the debug form in the index view and set it's target/action to 
	 * sendMethod action.
	 */
	public function indexAction() {
		//calls debug form
		$form = new Application_Form_Debug();
		$this->view->form = $form;
		$this->view->form->setAction("/np/debug/sendmethod/");
	}

	/**
	 * @method sendmethodAction 
	 * gets form data in POST from the debug form in /debug/ and sends 
	 * accordingly to provider or internal in relevant format .
	 * it then proceeds to pass parameters to the logger action
	 * via GET using the header() function
	 */
	public function sendmethodAction() {
		$post = $this->getRequest()->getPost();
		if (is_array($post) && count($post)) {
			$sendXML = new Application_Form_Helper_Debug($post);
			//@TODO: make function that get the current path of zend without host
			$this->_redirect(Application_Model_General::getBaseUrl() . '/debug/logger?phone=' . $post['NUMBER']);
		}
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
	public function loggerAction() {
//		$this->view->headerMenu = Application_Model_General::$menu;
		$debugModel = new Application_Model_Debug();
		$this->view->form = new Application_Form_DPFilter();
		$request_id = $this->getRequest()->getParam('request_id');
		if (empty($request_id)) {
			$request_id = FALSE;
		} else {
			$this->view->reqlog = $debugModel->getReqLog($request_id);
		}
		$phone = $this->getRequest()->getParam('phone');
		if (empty($phone)) {
			$phone = FALSE;
		}
		$date = $this->getRequest()->getParam('date');

		$time = $this->getRequest()->getParam('time');
		if (!empty($date) && !empty($time)) {
			$date .= ' ' . $time;
		} else if ($date === null) {
			$date = date('Y-m-d');
		}

		$this->view->requestsTable = $debugModel->getAllLogData('Requests', $date, $phone, $request_id);
		$this->view->transactionsTable = $debugModel->getAllLogData('Transactions', $date, $phone, $request_id);
		$this->view->logsTable = $debugModel->getAllLogData('Logs', $date, $phone, $request_id);
	}

	public function editAction() {
		$model = new Application_Model_Debug();
		$form = new Application_Form_Edit();
		if ($this->getRequest()->isPost()) {
			$post_data = $this->getRequest()->getPost();
			unset($post_data['submit']);
			$model->saveRow($post_data);
			$this->_redirect(Application_Model_General::getBaseUrl() . '/debug/logger?phone=' . (string) $post_data['number']);
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
}
