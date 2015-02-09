<?php

/**
 * Controller for API calls by 3rd party
 * 
 * @package         ApplicationController
 * @subpackage      ConsoleController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * API Controller Class
 * 
 * @package ApplicationController
 * @subpackage ApiController
 */
class ApiController extends Zend_Controller_Action {

	public function init() {
		$this->_helper->viewRenderer->setScriptAction('index');
	}
	public function indexAction() {
		$output = array('status' => 'SDOC NPG API works!');
		$this->view->response = Zend_Json::encode($output);
	}
	
	public function requestAction() {
		$searchValue = $this->getRequest()->getParam('request_id');
		if (!empty($searchValue)) {
			$searchColumn = 'request_id';
			$searchValue = $searchValue;
		} else {
			$searchColumn = 'phone_number';
			$searchValue = $this->getPhoneNumber();
		}
		
		if (empty($searchValue)) {
			$status = 0;
			$results = array();
		} else {
			$status = 1;
			$limit = $this->getRequest()->getParam('limit');
			$results = Application_Model_General::getRequests($searchValue, $searchColumn, array(), array(), 'id DESC', $limit);
			if ($this->getRequest()->getParam('include_transactions')) {
				$filterStage = $this->getRequest()->getParam('transaction_filter_stage') ? $this->getRequest()->getParam('transaction_filter_stage') : null;
				$filterReject = $this->getRequest()->getParam('transaction_filter_reject') ? $this->getRequest()->getParam('transaction_filter_reject') : null;
				foreach ($results as &$result) {
					$result['transactions'] = Application_Model_General::getTransactions($result['request_id'], $filterStage, $filterReject);
				}
			}
		}
		
		$output = array(
			'status' => $status,
			'results' => $results,
		);
		$this->view->response = $this->encodeResponse($output);
	}
	
	public function phoneproviderAction() {
		$phone_number = $this->getPhoneNumber();
		if (!empty($phone_number)) {
			$status = 1;
			$transfer_statuses = array('Execute_response', 'Publish_response', 'Return_response');
			$where = array(
				'last_transaction IN (?)' => $transfer_statuses,
			);
			$result = Application_Model_General::getRequests($phone_number, 'phone_number', array('to_provider'), $where, 'id DESC', 1);

			// if phone number not found return the default provider (by its number)
			if (empty($result)) {
				$result = Application_Model_General::getDefaultProvider($phone_number);
			}
		} else {
			$status = 0;
			$result = array();
			
		}
		$output = array(
			'status' => $status,
			'results' => $result,
		);
//		print "<pre>";
//		print_R($output);die;
		$this->view->response = $this->encodeResponse($output);
	}
	
	protected function getPhoneNumber() {
		$ret = $this->getRequest()->getParam('phone_number');
		if (empty($ret)) {
			$ret = $this->getRequest()->getParam('number');
		}
		return $ret;
	}
	
	protected function encodeResponse($response) {
		return Zend_Json::encode($response);
	}
}
