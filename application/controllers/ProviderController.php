<?php

/**
 * Controller for incoming Soap messages (from external providers)
 * 
 * @package         ApplicationController
 * @subpackage      ProviderController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Provider Controller Class
 * 
 * @package ApplicationController
 * @subpackage ProviderController
 */
class ProviderController extends Zend_Controller_Action {

	/**
	 * Index Action - "http://SERVER/Provider"   
	 * This is where the Provider's SOAP Requests will point to.
	 * 
	 * @package ApplicationController
	 * @subpackage ProviderController
	 */
	public function indexAction() {

		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->setLayout('clean');
		//add validation if there is a soap
		$res = $this->handleSOAP();
		$this->view->ack = $res;
	}

	/**
	 * function SOAP Handle. points to Np_Soap_Handler 
	 * the class for the used for the soap method called .
	 * 
	 * @return bool $result The Result of the SOAP Handle Function
	 * 
	 */
	private function handleSOAP() {
		$soap = new Zend_Soap_Server(Application_Model_General::getWsdl(), array('soap_version' => SOAP_1_1));
		$soap->setClass('Np_Soap_Handler');
		$soap->handle();
		$response = $soap->getLastResponse();

		return $response; // - change to result from handle 
	}

	/**
	 * internal Action - "http://SERVER/Provider/internal"
	 * 
	 * Gets params from GET and puts them in a new Request Model Object .
	 * then calls executeRequest() to send the message to internal. 
	 * 
	 * @package		ApplicationController      
	 * @subpackage	ProviderController
	 */
	public function internalAction() {
		$params = Application_Model_General::getParamsArray($this->getRequest()->getParams());
		if (isset($params['SLEEP'])) {
			sleep((int) $params['SLEEP']);
		}
		
		if (isset($params['NUMBER'])) {
			$params['PHONE_NUMBER'] = $params['NUMBER'];
		}
		
//		error_log(print_R($params, 1));
		$reqModel = new Application_Model_Request($params);
		$manual = isset($params['MANUAL']) && $params['MANUAL'];
		$reqModel->ExecuteRequest($manual);
	}

}
