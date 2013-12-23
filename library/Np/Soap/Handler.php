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
	 * 		sendMessage os the function defined by our wsdl .
	 * 
	 * 		it will be called by
	 * 		other providers in order to send transactio messages to internal.
	 * 		1.the functions receives the params . 
	 * 		2.logs them to database 
	 * 		3.validates whether they are in correct format and not null
	 * 		4.sends Internal the message to internal via http request.
	 * 		5.returns the resulting ack code from the params validation. 
	 * 
	 * 		@param		Array $params
	 * 		@return		array "NP_ACK" or string
	 */
	public function sendMessage($params) {

		$data = $this->intoArray($params);
		$reqModel = new Application_Model_Request($data); //prepares data for sending internal the message
		$ack = $reqModel->Execute();
		// log all received calls if request log enabled
		if (Application_Model_General::getSettings('EnableRequestLog')) {
			Application_Model_General::logRequestResponse($params, $ack, $data['REQUEST_ID'], '[Input] ');
		}
		if ($ack === FALSE || (strpos(strtolower($ack), "ack") === FALSE)) {

			$ack = "Ack00";
		}
		return array('NP_ACK' => array('ACK_CODE' => $ack, //returns default value for testing need to fix
				'ACK_DATE' => Application_Model_General::getDateIso()));
	}

	/**
	 * turns the soap array into a simple array for sending to internal.
	 * sets  soap "signature" so array may be validated and sent back 
	 * through soap after it reaches internal's proxy
	 * 
	 * @param		Array $params
	 * @return		Array $params associative array 
	 */
	public function intoArray($params) {
		$data = (array) $params->NP_MESSAGE;  //takes data out of np message array
		$xmlString = simplexml_load_string($data['BODY']); //loads xml string from xml object in body 
		if ($xmlString == NULL) {
			$xmlString[0] = "NULL";
		}
		$header = (array) $data['HEADER'];
		$msgtype = $header['MSG_TYPE'];

		$xmlArray = $xmlString[0]->$msgtype;

		$convertedData = $this->convertArray($msgtype, $xmlArray, $header);
		//sets  soap "signature"
		$convertedData['SOAP'] = 1;
		return $convertedData;  //returns simple array (1 level only)
	}

	/**
	 * convert Xml data to associative array
	 * 
	 * @param string $msgType message type
	 * @param simple_xml $xmlArray simple xml object
	 * @param array $header the header data to join to the return data
	 * 
	 * @return array converted data with header and the xml
	 * @todo refactoring to inner bridge classes
	 */
	function convertArray($msgType, $xmlArray, $header) {
		$data = $header;
		switch ($msgType) {
			case "Check":
				$nType = Application_Model_General::getSettings("NetworkType");
				if ($nType === "M") {
					$networkType = "mobile";
					$data['NETWORK_TYPE'] = (string) $nType;
				} else {
					$networkType = "fixed";
					$data['NETWORK_TYPE'] = (string) $nType;
				}

				if (!empty($xmlArray->$networkType->mobileNumberIdentified) && $xmlArray->$networkType->mobileNumberIdentified !== NULL) {
					$data['IDENTIFICATION_VALUE'] = (string) $xmlArray->$networkType->mobileNumberIdentified->identificationValue;
					$data['IDENTIFICATION_VALUE_2ND'] = (string) $xmlArray->$networkType->mobileNumberIdentified->identificationValue2nd;
					$data['IDENTIFICATION_VALUE_3RD'] = (string) $xmlArray->$networkType->mobileNumberIdentified->identificationValue3rd;
					$data['NUMBER_TYPE'] = (string) $xmlArray->$networkType->mobileNumberIdentified->numberType;
					$data['NUMBER'] = (string) $xmlArray->$networkType->mobileNumberIdentified->number;
				} else {
					$data['NUMBER_TYPE'] = (string) $xmlArray->$networkType->mobileNumberUnidentified->numberType;
					$data['NUMBER'] = (string) $xmlArray->$networkType->mobileNumberUnidentified->number;
				}

				break;
			case "Request":
				$data['PORT_TIME'] = (string) $xmlArray->portingDateTime;
				break;
			case "Update":
				$data['PORT_TIME'] = (string) $xmlArray->portingDateTime;
				break;
			case "Cancel":
				break;
			case "KD_update":
				$data['KD_UPDATE_TYPE'] = (string) $xmlArray[0];
				$data['REMARK'] = (string) $xmlArray->remark;
				break;
			case "Execute":
				break;
			case "Publish":
				$data['DONOR'] = (string) $xmlArray->donor;
				$data['CONNECT_TIME'] = (string) $xmlArray->connectDateTime;
				$data['PUBLISH_TYPE'] = (string) $xmlArray->publishType;
				$data['DISCONNECT_TIME'] = (string) $xmlArray->disconnectDateTime;
				if (isset($xmlArray->fixed)) {
					$data['NUMBER_TYPE'] = (string) $xmlArray->fixed->fixedNumberSingle->numberType;
					if (isset($xmlArray->fixed->fixedNumberRange)) {
						$data['NUMBER_TYPE'] = (string) $xmlArray->fixed->fixedNumberRange->numberType;
						$data['FROM_NUMBER'] = (string) $xmlArray->fixed->fixedNumberRange->fromNumber;
						$data['TO_NUMBER'] = (string) $xmlArray->fixed->fixedNumberRange->toNumber;
					} else {
						$data['NUMBER'] = (string) $xmlArray->fixed->fixedNumberSingle->number;
					}
				} else {
					$data['NUMBER_TYPE'] = (string) $xmlArray->mobile->numberType;
					$data['NUMBER'] = (string) $xmlArray->mobile->number;
				}

				break;
			case "Cancel_publish":
				$data['DONOR'] = (string) $xmlArray->donor;
				break;
			case "Return":
				$data['NUMBER'] = (string) $xmlArray->number;
				if (isset($xmlArray->mobile)) {
					$data['NETWORK_TYPE'] = (string) $xmlArray->mobile->networkType;
					$data['NUMBER_TYPE'] = (string) $xmlArray->mobile->numberType;
				} else {
					$data['NETWORK_TYPE'] = (string) $xmlArray->fixed->networkType;
					$data['NUMBER_TYPE'] = (string) $xmlArray->fixed->numberType;
				}
				break;
			case "Inquire_number":
				$data['NUMBER'] = (string) $xmlArray->number;
				break;
			case "Up_system":
				$res = Application_Model_General::saveShutDownDetails($data['FROM'], "UP");
				break;
			case "Down_system":
				$res = Application_Model_General::saveShutDownDetails($data['FROM'], "DOWN");
				break;
			case "Check_response":
				$data['ESSENTIAL_INFO_1'] = (string) $xmlArray->essentialInfo1;
				$data['ESSENTIAL_INFO_2'] = (string) $xmlArray->essentialInfo2;
				$data['ESSENTIAL_INFO_3'] = (string) $xmlArray->essentialInfo3;
				$data['ESSENTIAL_INFO_4'] = (string) $xmlArray->essentialInfo4;
				$data['ESSENTIAL_INFO_5'] = (string) $xmlArray->essentialInfo5;
			case "Request_response":
				// this check because check_response go through this code (TODO: refactoring)
				if (isset($xmlArray->portingDateTime)) {
					$data['PORT_TIME'] = (string) $xmlArray->portingDateTime;
				}
			case "Update_response":
			case "Cancel_response":
			case "Execute_response":
			case "Publish_response":
			case "KD_update_response":
			case "Inquire_number_response":
			case "Cancel_publish_response":
			case "Return_response":
				if (isset($xmlArray->positiveApproval)) {
					$data['APPROVAL_IND'] = "Y";
				} else {
					$data['APPROVAL_IND'] = "N";
					$data['REJECT_REASON_CODE'] = (string) $xmlArray->negativeApproval->rejectReasonCode;
				}
				$data['REQUEST_TRX_NO'] = (string) $xmlArray->requestTrxNo;
				$data['REQUEST_RETRY_DATE'] = (string) $xmlArray->requestRetryDate;
		}
		return $data;
	}

}
