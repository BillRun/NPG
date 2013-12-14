<?php

/*
 * Controller for incoming POST messages from internal (number transactions)
 * The internal is proxy owner which uses the proxy to communicate with other providers
 *
 * @package     ApplicationController
 * @subpackage  InternalController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Internal Controller Class
 *
 * @package     ApplicationController
 * @subpackage  InternalController
 */
class InternalController extends Zend_Controller_Action {

	/**
	 * Index Action - "http://SERVER/internal"   
	 * This is where the internal's POST Messages will point to.- 
	 * aFirst step get the parameters in POST
	 * 
	 */
	public function indexAction() {
		$params = Application_Model_General::getParamsArray($this->getRequest()->getParams());
		$this->AddParamsToInternalReq($params);
		$model = new Application_Model_Internal($params);
		$obj = new stdClass();
		$obj->status = $model->Execute();
		$request_params = $model->getParams();
		if (isset($request_params)) {
			$obj->reqId = isset($request_params['REQUEST_ID']) ? $request_params['REQUEST_ID'] : '';
			$obj->msgType = isset($request_params['MSG_TYPE']) ? $request_params['MSG_TYPE'] : '';
		}
		$desc = $model->getErrorMsg();
		if (!empty($desc)) {
			$obj->desc = $desc;
		}
		$this->view->ack = $obj;
	}

	/**
	 * Provider Action - "http://SERVER/internal/Provider"   
	 * gets params from Internal Model in GET and Sends to Provider
	 * 
	 */
	public function providerAction() {
		$params = Application_Model_General::getParamsArray($this->getRequest()->getParams());
		if (isset($params['REQID'])) {
			$params['REQUEST_ID'] = $params['REQID'];
		}
		$reqModel = new Application_Model_Request($params);
		$reqModel->ExecuteFromInternal();
	}

	/**
	 * method to force publish on specific request
	 */
	public function publishAction() {
		$disabled_output = $this->getRequest()->getParam('no-output');
		if (!isset($disabled_output) || !$disabled_output) {
			$output_enabled = true;
		} else {
			$output_enabled = false;
		}
		$this->view->output_enabled = $output_enabled;
		$reqId = strtoupper($this->getRequest()->getParam('reqId'));
		$cron = new Application_Model_Cron();
		$request = $cron->getRequestByID($reqId);
		if (FALSE !== ($request)) {
			$sentRows = $cron->checkPublish($request);
			if ($sentRows === TRUE) {
				$this->view->status = "Publish response";
			} else {
				$this->view->status = TRUE;
				$this->view->rows = $sentRows;
			}
		} else {
			$this->view->status = "No request with this id: " . $reqId;
		}
	}

	/**
	 * Adds Hardcoded Values to Missing Fields for SOAP Request 
	 * 
	 * @param array $params 
	 * 
	 */
	private function AddParamsToInternalReq(&$params) {
		$params['FORK'] = 1;
		$params['FROM'] = Application_Model_General::getSettings('InternalProvider'); //requests from internal - Transfer to internal
		$params['PROCESS_TYPE'] = Application_Model_General::getProcessType($params['MSG_TYPE']);
		$params['VERSION_NO'] = Application_Model_General::getSettings("VersionNo");
		$params['RETRY_NO'] = 1;
		$params['RETRY_DATE'] = Application_Model_General::getDateIso();
		$params['NETWORK_TYPE'] = Application_Model_General::getSettings("NetworkType");
//		$params['NUMBER_TYPE'] = Application_Model_General::getSettings("NumberType");
	}

}

