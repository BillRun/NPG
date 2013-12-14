<?php

/**
 * testing handler
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Np_Soap_HandlerNp {

	/**
	 * sendMessage method
	 *
	 * @param Array $param1
	 * @return array
	 */
	//SERVER SOAP - RECIVE REQUESTS
	//sendMessage is the function defined by the np wsdl  may receive 
	//and return arrays in defined format only 
	public function sendMessage($params) {
		$goodArray = $this->intoArray($params);
		return array('NP_ACK' => array('ACK_CODE' => 'ACK01', //returns default value for testing need to fix
				'ACK_DATE' => Date('Y-m-d\Th:i:s+h:i')));
	}

	//intoArray turns array back to the format readable by Application_Model_Request
	public function intoArray($params) {
		$goodParams = (array) $params->NP_MESSAGE;  //takes data out of np message array
		$xString = simplexml_load_string($goodParams['BODY']); //loads xml string from xml object in body 
		if ($xString == NULL) {
			$xString[0] = "NULL";
		}
		foreach ($goodParams['HEADER'] as $key => $value) {
			$fixpar[$key] = $value;  //takes fields from header array
		}
		foreach ($xString as $key => $value) {
			$fixpar[$key] = (string) $value;   //takes fields from xml object array
		}
		$fixpar['SOAP'] = 1; //sets  soap "signature"
		return $fixpar;  //returns simple array (1 level only)
	}

}
