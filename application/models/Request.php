<?php

/**
 * Request Model
 * Model for Number Transaction operations.
 * 
 * @package         ApplicationModel
 * @subpackage      RequestModel
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Request Object
 * 
 * @package     ApplicationModel
 * @subpackage  RequestModel
 */
class Application_Model_Request {

	/**
	 *
	 * @var object $request the instance of the request object from Np_Method
	 */
	protected $request;

	/**
	 *
	 * @var array $data  an array of message params
	 */
	protected $data;

	/**
	 * Constructor
	 * 
	 * Checks if message is soap or not 
	 * 
	 * @param array $params
	 * @return void 
	 */
	public function __construct($params) {
		//check if this is a soap request
		if (is_object($params) && isset($params->NP_MESSAGE)) {
			$this->request = Np_Method::getInstance($params);
			$this->data = $params;
			if (!isset($this->data['PHONE_NUMBER'])) {
				$this->data['PHONE_NUMBER'] = Application_Model_General::getFieldFromRequests("phone_number", $params['REQUEST_ID']);
			}
		} else if (is_array($params)) {
			// this is internal and not external (soap)
			if (isset($params['MANUAL']) && $params['MANUAL']) {
				$fields = array(
					'last_transaction',
					'from_provider',
					'to_provider',
				);
				$values = Application_Model_General::getFieldFromRequests($fields, $params['REQUEST_ID'], 'request_id');
				if ($values['last_transaction'] != $params['MSG_TYPE'] && ($values['last_transaction']) != $params['MSG_TYPE'] . '_response') {
					$response = array(
						'success' => 0,
						'error' => 1,
						'manual' => 1,
						'desc' => 'The request is not at the right stage',
					);
					die(json_encode($response));
				}
				if (!isset($params['FROM']) || !isset($params['TO'])) {
					$params['FROM'] = $values['to_provider'];
					$params['TO'] = $values['from_provider'];
				}
				if (!isset($params['TRX_NO'])) {
					$transaction = Application_Model_General::getTransactions($params['REQUEST_ID'], $params['MSG_TYPE']);
					$params['TRX_NO'] = $transaction[0]['trx_no'];
					$params['RETRY_DATE'] = Application_Model_General::getDateTimeIso($transaction[0]['last_transaction_time']);
				}
				$this->data = $params;
			} else if (is_array($params)) {
				// we are receiving data => need to send to provider
				if (!isset($params['REQUEST_ID']) && ($params['MSG_TYPE'] != "Check") && isset($params['PHONE_NUMBER'])) {
					//CALL FROM INTERNAL WITH NO REQUEST ID
					//if the type is check the request id will be added later- on insert to DB
					$params['REQUEST_ID'] = Application_Model_General::getFieldFromRequests("request_id", $params['PHONE_NUMBER']);
				}
				$this->data = $params;
			} else {
				//no parameters
				$this->data = NULL;
				$this->request = NULL;
				return;
			}

			$this->request = Np_Method::getInstance($this->data);
		}
	}

	/**
	 * checks if PreValidate Passed
	 * 
	 * @return BOOL 
	 */
	protected function RequestValidate() {
		//each Method implements Validate
		return isset($this->request) && $this->request->PreValidate();
	}

	/**
	 * If "Publish response" 
	 * saves to transactions table then sends to internal
	 * if other response 
	 * sends to internal without saving to transactions
	 * if not response 
	 * Sends Params to provider/ineternal via GET in New Process 
	 * 
	 * @return BOOL 
	 */
	public function Execute() {
		if (TRUE === ($requestValidate = $this->RequestValidate())) {
			$msg_type = explode("_", $this->request->getHeaderField("MSG_TYPE"));
			if (isset($msg_type[1]) && $msg_type[1] == "response") {
				// if is response 
				if ($msg_type[0] == "Publish") {
					//if is publish response
					//don't pass to Internal - it is saved and taking care of in our system
					$this->request->setAck("Ack00");
				}
				$this->ExecuteResponse();
			} elseif (count($msg_type) > 1 && $msg_type[1] == 'publish' && $msg_type[2] == 'response') {
				// if is cancel publish response
				$this->ExecuteResponse();
			} elseif ($this->request->getHeaderField("MSG_TYPE") == "Inquire_number_response") {
				// if is inquire publish response
				$this->request->setAck("Ack00");
				$this->ExecuteResponse();
			} else {
				//if not response
				if (Application_Model_General::forkProcess("/provider/internal", $this->data, false, Application_Model_General::getSettings('fork-sleep-provider', 1))) {
					// create new process for not responses
					$someAck = $this->request->getAck();
					$this->request->setAck("Ack00");
				} else {
					$this->request->setAck("Inv01");
				}
			}
		} else {
			// if doesnt validate
//			$this->saveTransactionsDB();
		}
		// TODO oc666: not required for all cases (set ack as reject reason code)
		return $this->request->setRejectReasonCode($this->request->getAck());
	}

	/**
	 * checks PostValidate()
	 * if TRUE
	 * saves data to db.
	 * send request to internal and returns response ack.
	 * if FALSE 
	 * puts FALSE in ACK
	 * send response via CreateMethodResponse
	 */
	public function ExecuteRequest($manual = false) {
		$validate = $this->request->PostValidate();
		$internalModel = new Application_Model_Internal($this->data);
		if ($validate === TRUE || strtolower($validate) === 'ack00') { // ack00 check is for b/c
			$this->saveDB();

			$content = $internalModel->SendRequestToInternal($this->request);
			if (empty($content)) {
				Application_Model_General::logRequest($this->request->getHeaderField('REQUEST_ID'), "ExecuteRequest: Internal CRM error");
				return FALSE;
			}
			$response = Zend_Json::decode($content, Zend_Json::TYPE_OBJECT);
			if (!isset($response->status)) {
				$response->status = "";
			} elseif ($response->status == "FALSE") {
				$response->status = "";
			}
		} else {
			$this->saveTransactionsDB();
			if ($validate === FALSE) {
				$validate = "Inv02";
			}
			$response = new stdClass();
			$response->status = $validate;
		}

		$internalModel->CreateMethodResponse($response, $manual);
	}

	/**
	 * checks PostValidate()
	 * if TRUE checks if request type is execute response
	 * 			if it is updates ack in db
	 * 			saves data to db.
	 * 			send request to internal and returns response ack.
	 * if FALSE 
	 * 		puts FALSE in ACK
	 * @return void 
	 */
	public function ExecuteResponse() {
		$validate = $this->request->PostValidate();
		if ($validate === true) {
			$this->request->setCorrectAck();
			$this->saveDB();
			$internalModel = new Application_Model_Internal($this->data);
			$json = $internalModel->SendRequestToInternal($this->request);
			$obj = json_decode($json);
			$reject_reason_code = $this->request->getRejectReasonCode();
			// send auto request only if no reject reason code
			if (empty($reject_reason_code)) {
				$internalModel->sendAutoRequest($this->request->type);
			}
			if (isset($obj->more)) {
				$this->request->postInternalRequest($obj->more);
			}

			if (isset($obj->status)) {
				if (strtolower($obj->status) == 'true' || strtolower($obj->status) == 'false') {
					$ret = 'Ack00';
				} else {
					$ret = $obj->status;
				}
			} else if (isset($obj->resultCode) && strtoupper($obj->resultCode) != "OK") {
				// backward compatibility
				$ret = $obj->resultCode;
			} else if (!isset($obj->resultCode) && !isset($obj->status)) {
				$ret = "Ack00";
			}
		} else {
			if ($validate !== FALSE) {
				$ret = $validate;
			}
		}
		$this->request->setAck($ret);
	}

	/**
	 * internal send SOAP
	 * check InternalPostValidate()
	 * if true saves to db
	 * sends internal soap
	 * TODO: CR
	 */
	public function ExecuteFromInternal($verifyInternal = TRUE) {
		if ($this->data['MSG_TYPE'] == "Publish" || $this->data['MSG_TYPE'] == "Execute") {
			// @TODO oc666: check this condition 
			if (substr($this->data['REQUEST_ID'], 4, 2) == $this->data['FROM'] || $this->data['MSG_TYPE'] == "Execute") {
				$this->saveDB();
			} else {
				$this->saveTransactionsDB();
			}

			if (!isset($this->data['PHONE_NUMBER']) || $this->data['PHONE_NUMBER'] == NULL) {
				$this->data['PHONE_NUMBER'] = Application_Model_General::getFieldFromRequests('phone_number', $this->data['REQUEST_ID']);
			} else if ($this->data['MSG_TYPE'] != "Publish") {
//				Application_Model_General::writeToLog($this->data);
			}
		}
		$validate = false;
		$this->request->setAck('Inv03'); //??
		if (!$verifyInternal || $this->request->InternalPostValidate()) {
			$this->request->setCorrectAck();

			if ($this->data['MSG_TYPE'] != "Publish" && $this->data['MSG_TYPE'] != "Execute") {
				$this->saveDB();
			}
			$validate = true;
		}
		$this->createResponse($validate); //create soap response and send it to provider
	}
	
	/**
	 * Get the request object
	 * 
	 * @return Np_Method object
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Saves to Transactions and Requests tables
	 * 
	 */
	protected function saveDB() {
		$this->request->saveToDB();
		$this->saveTransactionsDB();
	}

	/**
	 * saveTransactionDB saves data to Transactions table in db
	 * @return bool db Success or Failure 
	 */
	protected function saveTransactionsDB($TRX = FALSE) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::master());
		if ($TRX != FALSE) {
			$trxNo = $TRX;
		} else {
			$trxNo = $this->request->getHeaderField("TRX_NO");
		}

		$msgType = $this->request->getHeaderField("MSG_TYPE");
		$portTime = $this->request->getBodyField('PORT_TIME');
		$rejectReasonCode = $this->request->getRejectReasonCode();

		$reqId = $this->request->getHeaderField('REQUEST_ID');
		if ($trxNo) {
			$data = array(
				'trx_no' => $trxNo,
				'request_id' => $reqId,
				'message_type' => $msgType,
				'ack_code' => $this->request->getAck(),
				'target' => $this->request->getHeaderField("TO")
			);
			if (!$rejectReasonCode || $rejectReasonCode === NULL || $rejectReasonCode == "") {
				// do nothing
			} else {
				$data['reject_reason_code'] = $rejectReasonCode;
			}
			if ($msgType == "Update" || $msgType == "Request") {
				$data['requested_transfer_time'] = Application_Model_General::getDateTimeInSqlFormat($portTime);
			}
			if ($msgType == "Publish") {
				$data['donor'] = Application_Model_General::getDonorByReqId($reqId);
			}

			$res = $tbl->insert($data);

			return $res;
		} else {
			//this request if from internal - have to add trx_no
			//save to Transactions table trx_no  has to be consisten to id of the table
			$adapter = $tbl->getAdapter();
			$adapter->beginTransaction();
			try {
				$temp_trx_no = Application_Model_General::createRandKey(14);
				if (isset($this->data['request_id'])) {
					$reqId = $this->data['request_id'];
				}
				$row_insert = array(
					'trx_no' => $temp_trx_no,
					'request_id' => $reqId,
					'message_type' => $this->request->getHeaderField("MSG_TYPE"),
					'ack_code' => $this->request->getAck(),
					'target' => $this->request->getHeaderField("TO")
				);
				if (!$rejectReasonCode || $rejectReasonCode === NULL || $rejectReasonCode == "") {
					// do nothing
				} else {
					$row_insert['reject_reason_code'] = $rejectReasonCode;
				}
				if ($msgType == "Update" || $msgType == "Request" ||
					($msgType == "Check" && Application_Model_General::isAutoCheck($reqId))) {
					$row_insert['requested_transfer_time'] = Application_Model_General::getDateTimeInSqlFormat($portTime);
				}
				if ($msgType == "Publish") {

					$row_insert['donor'] = Application_Model_General::getDonorByReqId($reqId);
				}

				$_id = $tbl->insert($row_insert);
				$id = substr("00000000000" . $_id, -12, 12);
				$trx_no = Application_Model_General::getSettings('InternalProvider') . $id;
				$ret = $tbl->update(array('trx_no' => $trx_no), "id = " . $_id);
				$this->request->setTrxNo($trx_no);

				$adapter->commit();
				return true;
			} catch (Exception $e) {
				error_log("Error on create record in transactions table: " . $e->getMessage());
				$adapter->rollBack();
				return false;
			}
		}
	}

	/**
	 * If Status TRUE update request in requests table TRUE
	 * @param bool $status 
	 * @todo need to clear out the function that calling this function to 
	 * 		use the right value of the argument.
	 * 		For example when request get reject reason code need to input FALSE
	 * 		check: calling from executeReponse method
	 */
	protected function updateDB_ack($status) {
		//$status check?
		if ($status) {
			$result = Application_Model_General::updateRequest(
					$this->request->getHeaderField("REQUEST_ID"), $this->request->getHeaderField("MSG_TYPE"), array('status' => 1));
		} else {
			$result = Application_Model_General::updateRequest(
					$this->request->getHeaderField("REQUEST_ID"), $this->request->getHeaderField("MSG_TYPE"), array('status' => 0));
		}
	}

	/**
	 * Makes XML for SOAP Response Body
	 *  
	 */
	protected function setResponseXmlBody() {

		$xml = $this->request->createXml();
		$xmlString = $xml->asXML();
//		error_log($xmlString);
		$dom = new DOMDocument();
		$dom->loadXML($xmlString);
		$dom->formatOutput = true;
		$isValid = $dom->schemaValidate('npMessageBody.xsd');
		if ($isValid === TRUE || Application_Model_General::isProd()) {
			// format the xml with indentation
			return $dom->saveXML();
		} else {
			error_log("xml doesn't validate");
		}
	}

	/**
	 * 
	 * Internal's Soap Client Used to send Soap to Provider
	 * If "QA" set for provider it will return demi-value of true (bool)
	 * 	
	 * @return object SOAP result  
	 */
	protected function sendArray() {
		$lastTransaction = $this->request->getHeaderField("MSG_TYPE");
		if (strtoupper($lastTransaction) == "DOWN_SYSTEM") {
			Application_Model_General::saveShutDownDetails("GT", "DOWN");
		} elseif (strtoupper($lastTransaction) == "UP_SYSTEM") {
			Application_Model_General::saveShutDownDetails("GT", "UP");
		}

		if (strtoupper($lastTransaction) == "PUBLISH") {
			$url = $this->getRecipientUrl();
			$client = $this->getSoapClient($url);
			$ret = $this->sendAgain($client);
		} else {
			$url = $this->getRecipientUrl();
			$client = $this->getSoapClient($url);
			$ret = $this->sendSoap($client);
		}

		if (!isset($ret) || !isset($ret->NP_ACK->ACK_CODE)) {
			return false;
		}
		$soapAckCode = $ret->NP_ACK->ACK_CODE;
		$request_id = $this->request->getHeaderField("REQUEST_ID");
		Application_Model_General::updateSoapAckCode($soapAckCode, $request_id, $lastTransaction, $this->request->getHeaderField("TRX_NO"));
		return $ret;
	}

	// TODO oc666: require spec
	function sendAgain($client) {
		$timeout = 5;
		$ret = $this->sendSoap($client);
		$tries = 1;
		sleep($timeout);
		while (!isset($ret->NP_ACK)) {
			$ret = $this->sendSoap($client);
			sleep($timeout);
			$tries++;
			if ($tries == 3) {
				error_log($tries . " tries have failed.");
				break;
			}
		}
		return $ret;
	}

	function getSoapClient($url) {
		$client = new Zend_Soap_Client(
			Application_Model_General::getWsdl(), array(
			'uri' => $url,
			'location' => $url,
			'soap_version' => SOAP_1_1,
			'encoding' => 'UTF-8',
			'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_DEFLATE
			)
		);
		return $client;
	}

	function sendSoap($client) {
		try {
			$soapArray = $this->responseArray();
			$retryNo = Application_Model_General::checkIfRetry($this->request->getHeaderField('REQUEST_ID'), $this->request->getHeaderField('MSG_TYPE'));
			if (!empty($retryNo)) {
				$soapArray['NP_MESSAGE']['HEADER']['RETRY_NO'] = $retryNo;
			}
			$ret = $client->sendMessage($soapArray);
			// log all sending calls
			if (Application_Model_General::getSettings('EnableRequestLog')) {
				Application_Model_General::logRequestResponse($soapArray, $ret, $this->request->getHeaderField("REQUEST_ID"), '[Output] ');
			}
		} catch (SoapFault $e) {
			$ret = FALSE;
		}
		return $ret;
	}

	/**
	 * Creates array for internal's SOAP Response
	 * 
	 * @return array $soapMsg 
	 */
	protected function responseArray() {
		$soapMsg = array(
			'NP_MESSAGE' => array(
				'HEADER' => $this->request->getHeaders(),
				'BODY' => $this->setResponseXmlBody(),
			)
		);
		return $soapMsg;
	}

	/**
	 * Sends Internal's Response to Provider
	 *
	 * @param BOOL $status
	 * @return BOOL 
	 */
	public function createResponse($status) {
		if ($status) {
			$ack = $this->sendArray();
			if ($ack && isset($ack->NP_ACK) && isset($ack->NP_ACK->ACK_CODE)) {
				$ret = $ack->NP_ACK->ACK_CODE;
				$this->request->setAck($ret);
				//we need to see what validation needs to 
				//be here - ack from provider
				//update db - status OK
				if ($ack->NP_ACK->ACK_CODE == "Ack00") {
					Application_Model_General::updateTransactionsAck($this->request->getHeaderField('TRX_NO'), $ack->NP_ACK->ACK_CODE);
				} else {
					$this->SendErrorToInternal($ack->NP_ACK->ACK_CODE);
				}
			} else {
				if (strtoupper($this->request->getHeaderField('MSG_TYPE')) == 'CHECK') {
					Application_Model_General::updateRequest($this->request->getHeaderField('REQUEST_ID'), $this->request->getHeaderField('MSG_TYPE'), array('status' => 0));
				}
				Application_Model_General::updateTransactionsAck($this->request->getHeaderField('TRX_NO'), 'Err');
				return false;
			}
		} else {
			// @TODO: check this on all scenarios
//			$this->SendErrorToInternal(false);
		}
		return true;
	}
	
	/**
	 * send error to internal if happened directly 
	 * @param type $errorCode
	 * @return boolean
	 */
	protected function SendErrorToInternal($errorCode = false) {
		if (Application_Model_General::isMsgResponse($this->request->getHeaderField('MSG_TYPE'))) {
			// don't send error on response
			return true;
		}
		$params = $this->data;
		// send the error as response to internal
		$params['MSG_TYPE'] = $this->request->getHeaderField('MSG_TYPE') . '_response';
		$params['STATUS'] = $errorCode;
		$params['APPROVAL_IND'] = 'N';
		$params['FROM'] = $params['FROM_PROVIDER'];
		$params['TO'] = $params['TO_PROVIDER'];
		$response = new Application_Model_Internal($params);
		$response->SendErrorToInternal($params);
	}

	/**
	 * Get Provider RPC URL via "TO" field
	 * 
	 * @return string URL 
	 */
	protected function getRecipientUrl() {
		$providers = Application_Model_General::getSettings('provider');
		$key = $this->request->getHeaderField("TO");
		if (isset($providers[$key])) {
			return $providers[$key];
		}
		return FALSE;
	}

}
