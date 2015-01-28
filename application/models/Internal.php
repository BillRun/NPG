<?php

/**
 * Internal Model
 * 
 * @package         ApplicationModel
 * @subpackage      InternalModel
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Internal Object
 * 
 * @package     ApplicationModel
 * @subpackage  InternalModel
 */
class Application_Model_Internal {

	/**
	 * Required fields for diffrent MSG_TYPES
	 * 
	 * @var array $require_fields
	 */
	protected static $require_fields = array(
		'Check' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Request' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER", "PORT_TIME"),
		'Update' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER", "PORT_TIME"),
		'Cancel' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Execute' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Return' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Publish' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Cancel_publish' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'KD_update' => array("MSG_TYPE", "TO_PROVIDER", "PHONE_NUMBER"),
		'Up_system' => array("MSG_TYPE", "TO_PROVIDER"),
		'Down_system' => array("MSG_TYPE", "TO_PROVIDER"),
		'Inquire_number' => array("MSG_TYPE", "FROM_PROVIDER", "PHONE_NUMBER"),
	);

	/**
	 *
	 * @var array
	 */
	protected $params;

	/**
	 *
	 * @var string
	 */
	protected $error_msg = null;

	/**
	 * protocol pass to internal, current available: http, soap
	 * 
	 * @var string
	 * @since 2.0
	 */
	protected $protocal = 'http';

	/**
	 * Constructor
	 * Receives Params and uses setParams to put them in params property
	 * 
	 * @param array $params 
	 */
	public function __construct($params) {
		$this->setParams($params);
		if (isset($params['protocol'])) {
			$this->protocal = $params['protocol'];
		}
	}

	/**
	 *
	 * @param array $data 
	 */
	protected function setParams($data) {
		$this->params = $data;
	}

	/**
	 *
	 * @return array the message params 
	 */
	public function getParams() {
		return $this->params;
	}

	protected function setReqID($reqID) {
		$this->reqID = $reqID;
	}

	/**
	 *
	 * @return array the message params 
	 */
	public function getReqID() {
		return $this->reqID;
	}

	/**
	 * method to create request row in the table including unique request id
	 * save to request table request_id has to be consisten to id of the table
	 * 
	 * @return mixed request_id string or FALSE
	 */
	protected function createRequestId() {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$adapter = $tbl->getAdapter();
		$adapter->beginTransaction();
		try {
			// we create temp request id because it build from the mysql internal id
			$temp_request_id = Application_Model_General::createRandKey(21);
			if (
				(strtoupper($this->params['MSG_TYPE']) != 'UP_SYSTEM' && strtoupper($this->params['MSG_TYPE']) != 'DOWN_SYSTEM')) {
				$row_insert = array(
					'request_id' => $temp_request_id,
					'status' => 1,
					'last_transaction' => $this->params["MSG_TYPE"],
					'phone_number' => $this->params["PHONE_NUMBER"],
				);
				if (isset($this->params['MSG_TYPE']) == 'CHECK' && isset($this->params['IDENTIFICATION_VALUE'])) {
					$row_insert['flags'] = json_encode(array('identification_value' => $this->params['IDENTIFICATION_VALUE']));
				}
			} else {
				$row_insert = array(
					'request_id' => $temp_request_id,
					'status' => 1,
					'last_transaction' => $this->params["MSG_TYPE"]
				);
			}
			// we set to from & to as the direction of number transfer
			if (strtoupper($this->params['MSG_TYPE']) != 'RETURN_NUMBER') {
				$row_insert['from_provider'] = $this->params["TO_PROVIDER"];
				$row_insert['to_provider'] = $this->params["FROM_PROVIDER"];
			} else {
				$row_insert['from_provider'] = $this->params["FROM_PROVIDER"];
				$row_insert['to_provider'] = $this->params["TO_PROVIDER"];
			}
			if (isset($this->params["AUTO_CHECK"]) && $this->params["AUTO_CHECK"]) {
				$row_insert['auto_check'] = 1;
//				$row_insert['transfer_time'] = Application_Model_General::getDateTimeInSqlFormat($this->params['PORT_TIME']);
			}

			$_id = $tbl->insert($row_insert);
			
			$id = substr("0000" . $_id, -5, 5);

			$request_id = "NP"
						. $this->params['FROM_PROVIDER']  //AA
						. $this->params['TO_PROVIDER']    //BB
						. date("ymd")                     //YYMMDD
						. $id
						. "0001";                         //ZZZZ  

			
			$tbl->update(array('request_id' => $request_id), "id = " . $_id);

			$adapter->commit();
			return $request_id;
		} catch (Exception $e) {
			error_log("Cannot create request ID. Reason: " . $e->getMessage());
			$adapter->rollBack();
		}
		return FALSE;
	}

	protected function getRequestId() {
		if (isset($this->params['REQID'])) {
			$this->setReqID($this->params['REQID']);
			return $this->params['REQID'];
		}

		if (isset($this->params['MSG_TYPE']) && $this->params['MSG_TYPE'] == "Request") {
			if ($this->previousCheckExists()) {
				$this->params['AUTO_CHECK'] = 0;
			} else {
				$this->params['MSG_TYPE'] = "Check";
				$this->params['AUTO_CHECK'] = 1;
			}
		}
		if (isset($this->params['MSG_TYPE']) && $this->params['MSG_TYPE'] == "Check" && Application_Model_General::previousCheck($this->params['PHONE_NUMBER']) || $this->params['MSG_TYPE'] == "Inquire_number" //might need to take these off because of standard process flow
			|| $this->params['MSG_TYPE'] == "Return" || $this->params['MSG_TYPE'] == "Execute" || $this->params['MSG_TYPE'] == "Publish" || $this->params['MSG_TYPE'] == "Cancel_publish" || $this->params['MSG_TYPE'] == "Up_system" || $this->params['MSG_TYPE'] == "Down_system") {
			$this->params['REQUEST_ID'] = $this->createRequestId();
		} elseif ($this->params['MSG_TYPE'] == "Check" && !Application_Model_General::previousCheck($this->params['PHONE_NUMBER'])) {
			$this->setErrorMsg("the phone number you have submitted is already in process");
			return FALSE;
		} elseif (($this->params['MSG_TYPE'] == "Cancel" &&
			Application_Model_General::previousCheck($this->params['PHONE_NUMBER'])) ||
			($this->params['MSG_TYPE'] == "Update" &&
			Application_Model_General::previousCheck($this->params['PHONE_NUMBER']))) {
			$this->setErrorMsg("invalid order of transactions .");
			return FALSE;
		}


		return TRUE;
	}

	/**
	 * if prevalidate and request id return true.
	 * sends params to internal/prodivder (action for sending to provider)
	 * via GET in new process
	 *
	 * @return string "wait" or bool FALSE
	 */
	public function Execute() {
		Application_Model_General::writeToLog($this->params);
		if ($this->PreValidate() && $this->getRequestId()) {
			if (Application_Model_General::forkProcess("/internal/provider", $this->params)) {
				return "wait";
			}
		}
		return false;
	}

	/**
	 * automatic response for  CheckResponse,RequestResponse and UpdateResponse
	 * sent to internal/provider via GET in new PROCESS
	 * @param string $type 
	 */
	public function sendAutoRequest($type) {
		$request = null;
		switch ($type) {
			case "CheckResponse":
				if (($transfer_time = Application_Model_General::isAutoCheck($this->params['REQUEST_ID'])) !== FALSE) {
					$request = array(
						"REQUEST_ID" => $this->params['REQUEST_ID'],
						"MSG_TYPE" => "Request",
						"TO" => $this->params['FROM'],
						"PORT_TIME" => $transfer_time,
						"PHONE_NUMBER" => $this->params['PHONE_NUMBER'],
						"PROCESS_TYPE" => $this->params['PROCESS_TYPE'],
						"FROM" => $this->params['TO'],
						"RETRY_DATE" => Application_Model_General::getDateTimeIso(),
						"RETRY_NO" => $this->params['RETRY_NO'],
						"VERSION_NO" => Application_Model_General::getSettings("VersionNo"),
					);
				}
				break;
			case "RequestResponse":
			case "UpdateResponse":
			case "CancelResponse":
				$request = array(
					"REQUEST_ID" => $this->params['REQUEST_ID'],
					"MSG_TYPE" => "KD_update",
					"FROM" => Application_Model_General::getSettings("InternalProvider"),
					"TO" => "KD",
					"KD_update_type" => strtoupper(str_ireplace("Response", "", $type)),
					"PROCESS_TYPE" => $this->params['PROCESS_TYPE'],
					"PHONE_NUMBER" => $this->params['PHONE_NUMBER'],
					"RETRY_DATE" => Application_Model_General::getDateTimeIso(),
					"RETRY_NO" => $this->params['RETRY_NO'],
					"VERSION_NO" => Application_Model_General::getSettings("VersionNo"),
				);
				break;
		}
		if ($request) {
			Application_Model_General::forkProcess("/internal/provider", $request, false, Application_Model_General::getSettings('fork-sleep-internal', 1));
		}
	}

	/**
	 * If Request is sent before T2DR2 timer return FALSE 
	 * 
	 * @return BOOL 
	 */
	protected function previousCheckExists() {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->order("id DESC")
			->where('phone_number=?', $this->params['PHONE_NUMBER'])
			->where('status=?', 1)
			->order('id DESC');
		$result = $select->query()->fetchObject();
		if ($result) {
			$last_request_time_diff = time() - strtotime($result->last_request_time);
			if ((in_array($result->last_transaction, Application_Model_General::$inProcess)) ||
				($result->last_transaction == "Check_response" &&
				$last_request_time_diff < Np_Timers::get("T2DR2")
				)) {
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * @return String transfer date of autocheck if it's autocheck else false
	 */
	protected function isAutoCheck() {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()
			->where('request_id=?', $this->params['REQUEST_ID'])
			->order('id DESC');
		$result = $select->query()->fetchObject();
		if ($result && $result->auto_check) {
			return Application_Model_General::getDateTimeInTimeStamp($result->transfer_time);
		}
		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function PreValidate() {
		if (isset($this->params['MSG_TYPE'])) {
			if ($this->params['MSG_TYPE'] != "Up_system" && $this->params['MSG_TYPE'] != "Down_system") {
				if (!$this->params['PHONE_NUMBER'] || !is_numeric($this->params['PHONE_NUMBER'])) {
					$this->setErrorMsg("the phone number you have submitted is invalid");
					return FALSE;
				}
			}
			$validator = self::$require_fields[$this->params['MSG_TYPE']];
			foreach ($validator as $key) {
				if (!isset($this->params[$key])) {
					$this->setErrorMsg("Missing field: " . $key);
					return false;
				}
			}
			return true;
		} else {

			return false;
		}
	}

	/**
	 *
	 * @return string 
	 */
	public function getErrorMsg() {
		return $this->error_msg;
	}

	/**
	 * sets error msg in error_msg property
	 * 
	 * @return void 
	 */
	public function setErrorMsg($string) {
		$this->error_msg = $string;
	}

	/**
	 * @TODO: implement send error to internal
	 */
	public function SendErrorToInternal($errorData) {
		$request = Np_Method::getInstance($errorData);
		if (isset($errorData['STATUS'])) {
			$request->setRejectReasonCode($errorData['STATUS']);
			$request->setAck($errorData['STATUS']);
		} else {
			$request->setRejectReasonCode(false);
			$request->setAck(false);
		}
		$this->SendRequestToInternal($request);
		return TRUE;
	}

	/**
	 * send http request to internal RPC and return response string.
	 * 
	 * @return string
	 */
	public function SendRequestToInternal(Np_Method $request) {
		Application_Model_General::virtualSleep(); // used to write to log to be in order
		$data = $this->createPostData($request);
		$url = Application_Model_General::getSettings('UrlToInternalResponse');
		$auth = Application_Model_General::getSettings('InternalAuth');
		$method = self::getMethodName($this->params['MSG_TYPE']);
		if ($this->protocal == 'http') {
			$client = new Zend_Http_Client();
			$client->setUri($url);
			$client->setParameterGet('method', $method);
			if (is_numeric($timeout = Application_Model_General::getSettings('InternalAuth'))) {
				$client->setConfig(array('timeout' => $timeout));
			}
			$client->setParameterPost($data);
			if (isset($auth['user'])) {
				$user = (string) $auth['user'];
				if (isset($auth['password'])) {
					$password = (string) $auth['password'];
					$client->setAuth($user, $password);
				}
				$client->setAuth($user);
			}
			$logContentRequest = "Send to internal " . PHP_EOL . print_R(array_merge(array('method' => $method), $data), 1) . PHP_EOL;
			Application_Model_General::logRequest($logContentRequest, $data['reqId']);
			$client->setMethod(Zend_Http_Client::POST);
			$response = $client->request();
			$ret = $response->getBody();
			$logContentResponse = "Response from internal " . PHP_EOL . $ret . PHP_EOL . PHP_EOL . PHP_EOL;
			Application_Model_General::logRequest($logContentResponse, $data['reqId']);
		} elseif ($this->protocal == 'soap') {
			$client = new Zend_Soap_Client();
			$client->setUri($url);
			$ret = call_user_func_array(array($client, $method), $data);
		}
		return $ret;
	}

	/**
	 * create data to post to the internal
	 * 
	 * @param Np_Method $request the request to create post data from
	 * 
	 * @return void
	 * 
	 * @todo refatoring to bridge classes
	 */
	protected function createPostData(Np_Method $request) {
		$ack = $request->getAck();
		$rejectReasonCode = $request->getRejectReasonCode();
		$idValue = $request->getIDValue();
		if ($this->params['FROM'] != Application_Model_General::getSettings('InternalProvider')) {
			$provider = $this->params['FROM'];
		} else {
			$provider = $this->params['TO'];
		}
		if (!$rejectReasonCode || $rejectReasonCode === "" || $rejectReasonCode == " ") {
			$rejectReasonCode = "OK";
		}
		if (empty($idValue)) {
			$idValue = "no details";
		}
		$ret = array(
			'number' => $this->params['PHONE_NUMBER'], //check is set otherwise select phone number from DB from request_id
			'provider' => $provider,
			'msg_type' => $this->params['MSG_TYPE'],
			'reqId' => $this->params['REQUEST_ID'],
			'more' => array(
				'identification_value' => $idValue,
				'resultCode' => $rejectReasonCode,
				'ack' => $ack,
			),
		);
		if (isset($this->params['PHONE_NUMBER'])) {
			$ret['number'] = $this->params['PHONE_NUMBER'];
		}
		if (isset($this->params['NUMBER_TYPE'])) {
			$ret['more']['number_type'] = $this->params['NUMBER_TYPE'];
			if ($ret['more']['number_type'] == "R") {
				unset($ret['number']);
				$ret['more']['to_number'] = $this->params['TO_NUMBER'];
				$ret['more']['from_number'] = $this->params['FROM_NUMBER'];
			}
		}
		if (Application_Model_General::isMsgResponse($this->params['MSG_TYPE'])) {
			$ret['more']['approval_ind'] = $this->params['APPROVAL_IND'];
		}
		$msg_type = strtoupper($this->params['MSG_TYPE']);
		if ($msg_type == "KD_UPDATE_RESPONSE") {
			$ret['more']['KD_update_type'] = $this->params['KD_UPDATE_TYPE'];
		}
		if ($msg_type == "EXECUTE_RESPONSE" && isset($this->params['DISCONNECT_TIME'])) {
			$ret['more']['disconnect_time'] = $this->params['DISCONNECT_TIME'];
		}
		if ($msg_type == "REQUEST" || $msg_type == "UPDATE") {
			if (!is_numeric($this->params['PORT_TIME'])) {
				// convert to unix timestamp in case is format as full datetime
				$transfer_time = strtotime($this->params['PORT_TIME']);
			} else {
				$transfer_time = $this->params['PORT_TIME'];
			}
			// all the cases for backward compatibility
			$ret['port_time'] = $ret['more']['port_time'] = $ret['transfer_time'] = $ret['more']['transfer_time'] = $transfer_time;
		}
		if (isset($ret['more']['approval_ind']) && $ret['more']['approval_ind'] == 'Y' 
			&& ($msg_type == "REQUEST_RESPONSE" || $msg_type == "UPDATE_RESPONSE")
			&& Application_Model_General::getSettings('InternalProvider') == $this->params['TO']) {
			$transfer_time = Application_Model_General::getFieldFromRequests('transfer_time', $this->params['REQUEST_ID'], 'request_id');
			$ret['more']['transfer_time'] = Application_Model_General::getDateTimeInTimeStamp($transfer_time);
		}
		if ($msg_type == "PUBLISH") {
			$ret['more']['donor'] = $this->params['DONOR'];
			$ret['more']['publish_type'] = $this->params['PUBLISH_TYPE'];

			if (isset($this->params['DISCONNECT_TIME'])) {
				$ret['more']['disconnect_time'] = $this->params['DISCONNECT_TIME'];
			}
			if (isset($this->params['CONNECT_TIME'])) {
				$ret['more']['connect_time'] = $this->params['CONNECT_TIME'];
			}
		}
		if ($msg_type == "CANCEL_PUBLISH") {
			$ret['more']['donor'] = $this->params['DONOR'];
		}
		if ($msg_type == "PUBLISH_RESPONSE") {
			$ret['more']['approval_ind'] = $this->params['APPROVAL_IND'];
			if (isset($this->params['ROUTE_TIME'])) {
				$ret['more']['route_time'] = Application_Model_General::getDateTimeInSqlFormat($this->params['ROUTE_TIME']);
			}
		}
		if ($msg_type == "CANCEL_PUBLISH_RESPONSE") {
			$ret['more']['msg_type'] = "Cancel_Publish_response";
			$ret['more']['approval_ind'] = $this->params['APPROVAL_IND'];
			$ret['more']['route_time'] = Application_Model_General::getDateTimeInSqlFormat($this->params['ROUTE_TIME']);
		}
		if ($msg_type == "INQUIRE_NUMBER_RESPONSE") {
			$ret['more']['approval_ind'] = $this->params['APPROVAL_IND'];
			$ret['more']['current_operator'] = isset($this->params['CURRENT_OPERATOR'])?$this->params['CURRENT_OPERATOR']:'  ';
		}
		if ($msg_type == "DB_SYNCH_REQUEST") {
			$ret['more']['from_date'] = $this->params['FROM_DATE'];
			$ret['more']['to_date'] = $this->params['TO_DATE'];
		}
		if ($msg_type == "DB_SYNCH_RESPONSE") {
			$ret['more']['file_name'] = $this->params['FILE_NAME'];
		}

		// let's keep on backward backward compatibility - all more fields should be also in the root
		foreach ($ret['more'] as $k => $v) {
			if (!isset($ret[$k])) {
				$ret[$k] = $v;
			}
		}

		return $ret;
	}

	/**
	 * Gets matching Function Call for Message Type
	 * 
	 * @param type $msg_type
	 * @return string 
	 */
	public static function getMethodName($msg_type) {
		// @TODO: make the mapping configurable
		$mapping = array(
			'Check' => 'check_transfer_availability',
			'Request' => 'transfer_request',
			'Update' => 'update_transfer_request',
			'Cancel' => 'cancel_transfer_request',
			'Execute' => 'execute_transfer',
			'Publish' => 'publish',
			'Cancel_publish' => 'cancel_publish',
			'Return' => 'return',
			'Inquire_number' => 'inquire_number',
			'Up_system' => 'up_system',
			'Down_system' => 'down_system',
		);
		$s = str_replace('_response', '', $msg_type);
		if (isset($mapping[$s])) {
			return $mapping[$s];
		}
		return NULL;
	}

	/**
	 * sends automatic response to transaction messages sent to internal.
	 * 
	 * @param bool $status
	 * 
	 * @todo refactoring to bridge classes
	 */
	public function CreateMethodResponse($status) {
		//update DB ack!
		//SEND RESPONSE TO PROVIDER
		$response = array(
			'REQUEST_ID' => $this->params['REQUEST_ID'],
			'PROCESS_TYPE' => $this->params['PROCESS_TYPE'],
			'MSG_TYPE' => $this->params['MSG_TYPE'] . "_response",
			'REQUEST_TRX_NO' => $this->params['TRX_NO'],
			'FROM' => $this->params['TO'],
			'TO' => $this->params['FROM'],
			'REQUEST_RETRY_DATE' => $this->params['RETRY_DATE'],
			'RETRY_DATE' => Application_Model_General::getDateTimeIso(),
			'RETRY_NO' => isset($this->params['RETRY_NO']) ? $this->params['RETRY_NO'] : 1,
			'VERSION_NO' => Application_Model_General::getSettings("VersionNo"),
			'NETWORK_TYPE' => Application_Model_General::getSettings('NetworkType'),
			'NUMBER_TYPE' => isset($this->params['NUMBER_TYPE'])?$this->params['NUMBER_TYPE']:Application_Model_General::getSettings("NumberType"),
		);
		$phone_number = array("PHONE_NUMBER" => $this->params['PHONE_NUMBER']);
		Application_Model_General::writeToLog(array_merge($response, $phone_number));
		//check $status 
		if ((($status->status == "Ack00" || $status->status == "true") && !isset($status->resultCode)) || ($status->status == NULL && $status->resultCode == "Ack00")) {
			$response['APPROVAL_IND'] = "Y";
		} elseif ($status->status == "Gen07") {
			$response['APPROVAL_IND'] = "N";
			$response['REJECT_REASON_CODE'] = "Gen07";
		} else {
			$response['APPROVAL_IND'] = "N";
			$response['REJECT_REASON_CODE'] = $status->status;
		}
		if ($this->params['MSG_TYPE'] == "Execute") {
			$time = isset($status->DISCONNECT_TIME) ? $status->DISCONNECT_TIME : null;
			$response['DISCONNECT_TIME'] = Application_Model_General::getDateTimeIso($time);
		}
		if ($response['MSG_TYPE'] == "Inquire_number_response") {
			$response['CURRENT_OPERATOR'] = isset($status->current_operator)?$status->current_operator:$status->more->current_operator;
		}
		if (isset($status->resultCode) && !empty($status->resultCode)) {
			$response['REJECT_REASON_CODE'] = $status->resultCode;
		}
		$reqModel = new Application_Model_Request($response);
		$reqModel->ExecuteFromInternal(FALSE); // ExecuteResponse();
	}

}
