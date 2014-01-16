<?php

/**
 * Soap Handler Class 
 * 
 * 
 * @package         Np_Soap
 * @subpackage      Np_Soap
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Db Class Definition
 * 
 * @package     Np_Soap
 * @subpackage  Np_Soap
 */
class Np_Soap_Handler {

	/**
	 * sendMessage method defined by NP wsdl
	 * called by external providers to send transaction messages to internal
	 * the call to internal will be forward by forking
	 * 
	 * @param		Array $params
	 * @return		array "NP_ACK" or string
	 */
	public function sendMessage($params) {
		Application_Model_General::virtualSleep();
		$reqModel = new Application_Model_Request($params); //prepares data for sending internal the message
		$ack = $reqModel->Execute();
		// log all received calls if request log enabled
		if (Application_Model_General::getSettings('EnableRequestLog')) {
			Application_Model_General::logRequestResponse($params, $ack, $reqModel->getRequest()->getHeaderField('REQUEST_ID'), '[Input] ');
		}
		if ($ack === FALSE || (strpos(strtolower($ack), "ack") === FALSE)) {
			$ack = "Ack00";
		}
		return array('NP_ACK' => array('ACK_CODE' => $ack, //returns default value for testing need to fix
				'ACK_DATE' => Application_Model_General::getDateTimeIso()));
	}

}
