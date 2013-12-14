<?php

/**
 * Np_Method Class
 * Model for Number Transaction operations.
 * 
 * @package Np_Method
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method Class Definition
 * 
 * @package Np_Method
 */
abstract class Np_Method {

	/**
	 * Required Fields for Header in Basic Transaction
	 * 
	 * @var array
	 */
	static $require_header_key = array("REQUEST_ID", "PROCESS_TYPE", "MSG_TYPE", "FROM", "TO");

	/**
	 * Header Params Array
	 * 
	 * @var array 
	 */
	protected $header;

	/**
	 * Body Params Array
	 * 
	 * @var array 
	 */
	protected $body;

	/**
	 * The Transaction Type Property (MSG_TYPE)
	 * 
	 * @var string 
	 */
	public $type;

	/**
	 * instances of Np_Method's Child Classes  
	 *  
	 * @var array 
	 */
	protected static $instances = array();

	/**
	 * Last Transaction Type used
	 * 
	 * @var string 
	 */
	public $last_method;

	/**
	 * Last Transaction Time
	 * 
	 * @var string (date)
	 */
	public $last_method_time;

	/**
	 * Last Transfer Time
	 * 
	 * @var string (date)
	 */
	public $last_transfer_time;

	/**
	 * Constructor 
	 * Gets Params and places them accordingly as Header or Body Fields
	 * using the Switch Case
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		Application_Model_General::writeToLog($options);
		$this->type = str_replace("Np_Method_", "", get_class($this));
		$this->header = $this->body = array();
		//SET HEADER - for all methods
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Request_id":
				case "Process_type":
				case "Msg_type":
				case "Trx_no":
				case "Version_no":
				case "Retry_no":
				case "Retry_date":
				case "From":
				case "To":
					$this->setHeader($key, $value);
					break;
				case "Ack_code":
				case "Ack_date":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * 
	 * 
	 * @param string $value Tracking Number
	 */
	public function setTrxNo($value) {
		$this->setHeader("TRX_NO", $value);
	}

	/**
	 * sets param in $this->header array by key
	 * changes key to uppercase
	 * 
	 * @param string $key
	 * @param string $value 
	 */
	public function setHeader($key, $value) {
		$this->header[strtoupper($key)] = $value;
	}

	/**
	 * sets param in $this->body array by key
	 * changes key to uppercase
	 * 
	 * @param string $key
	 * @param string $value
	 */
	protected function setBodyField($key, $value) {
		$this->body[strtoupper($key)] = $value;
	}

	/**
	 * method to get request Ack
	 * 
	 * @return string ACK_CODE 
	 */
	public function getAck() {
		return $this->getBodyField('ACK_CODE');
	}

	/**
	 * method to set request Ack
	 * 
	 * @param String $ack ack code
	 * @return String current ack code
	 */
	public function setAck($ack) {
		$this->setBodyField('ACK_CODE', $ack);
		return $this->getAck();
	}

	/**
	 * method to set reject reason code
	 * 
	 * @param String $reject_reason_code reject reason code
	 * @return String current reject reason code
	 */
	public function setRejectReasonCode($reject_reason_code) {
		$this->setBodyField('REJECT_REASON_CODE', $reject_reason_code);
		return $this->getRejectReasonCode();
	}

	public function getIDValue() {
		if ($this->getBodyField("IDENTIFICATION_VALUE")) {

			return $this->getBodyField("IDENTIFICATION_VALUE");
		} else {
			return NULL;
		}
	}

	/**
	 * method to get reject reason code
	 * 
	 * @return string REJECT_REASON_CODE 
	 */
	public function getRejectReasonCode() {
		return $this->getBodyField("REJECT_REASON_CODE");
	}

	/**
	 * method to set request Ack as request success & valid
	 * 
	 * @return String current ack code
	 */
	public function setCorrectAck() {
		return $this->setAck("Ack00");
	}

	/**
	 * getHeaders returns the header segment from the parsed data
	 * 
	 * @return array $this->header
	 */
	public function getHeaders() {
		return $this->header;
	}

	/**
	 * getHeaders returns the body segment from the parsed data
	 * 
	 * @return array $this->body
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * getHeaderField returns aspecific header field from the parsed data 
	 * 
	 * @param string $key
	 * @return type 
	 */
	public function getHeaderField($key) {
		if (isset($this->header[$key])) {
			return $this->header[$key];
		}
		return NULL;
	}

	//
	/**
	 * getBodyField returns aspecific body field from the parsed data
	 * 
	 * @param string $key
	 * @return mixed String or BOOL 
	 */
	public function getBodyField($key) {
		if (isset($this->body[$key])) {
			return $this->body[$key];
		}
		return NULL;
	}
	
	/**
	 * create xml for provider response
	 * @return \SimpleXMLElement
	 */
	public function createXml() {
		return new SimpleXMLElement('<npMessageBody xmlns=""></npMessageBody>');
	}

	/**
	 * takes message type and places in adapter for creating instance of new 
	 * child object in $this->instances
	 * 
	 * @param array $data
	 * @return mixed BOOL or Object 
	 */
	public static function getInstance($data) {
		if (isset($data['MSG_TYPE'])) {
			$msgtype = $data['MSG_TYPE'];
		} else {
			return NULL;
		}
		$signature = md5(serialize($data));
		if (!isset(self::$instances[$signature])) {
			$adapter = 'Np_Method_' . str_replace(' ', '', ucwords(str_replace('_', ' ', $msgtype)));
			$instance = new $adapter($data);
			self::$instances[$signature] = $instance;
		}
		return self::$instances[$signature];
	}

	/**
	 * checks if all required header fields are present .
	 * 
	 * @return BOOL 
	 */
	protected function validateHeader() {
		foreach (self::$require_header_key as $key) {
			if (!$this->getHeaderField($key)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * validatebody params . overridden in child classes.
	 * 
	 * @return bool 
	 */
	protected function validateBody() {
		return true;
	}

	/**
	 * 
	 * sets ack code in body field using validate params
	 * and calls validateHeader and validateBody
	 * 
	 * @return BOOL 
	 */
	public function PreValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));
		return $this->validateBody() && $this->validateHeader();
	}

	protected function checkDirection() {
		if ($this->getHeaderField("TO") != Application_Model_General::getSettings('InternalProvider')) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * sets ack code in body field using validate params
	 * post validation checks for general soap field errors
	 * 
	 * @return mixed String or BOOL 
	 */
	public function PostValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));
		//first step is GEN
		if (!$this->checkDirection()) {
			return "Gen04";
		}
		//HOW TO CHECK Gen05
		//  $this->checkIfJew();


		if (!$this->ValidateDB()) {
			return "Gen07";
		}
		if (($timer_ack = Np_Timers::validate($this)) !== TRUE) {
			$timers_array = array('T2DR1', 'T2DR2', 'T3DR1', 'T3RK1'
				, 'T3DR1U', 'T3RK1', 'T3RK2', 'T3DR1D'
				, 'T3RK3', 'T3KR', 'T4DR0', 'T4DR1'
				, 'T4DR11', 'T4DR2', 'T4DR3', 'T4RR1'
				, 'T4RR11', 'T4OR1', 'T4OR11', 'T4RO1'
				, 'TNP', 'TNP1', 'TNP2', 'TNP3'
				, 'TNP4', 'TNPS', 'TNPB', 'T5BA2'
				, 'T5BA3', 'T5AO1', 'T5OR1', 'T5001'
				, 'T5002', 'T5003', 'TACK1', 'TACK2'
				, 'TRES2', 'T5BA2', 'T5BA22', 'PNP1'
				, 'PNP2', 'PNP3', 'PNP4', 'RACK3'
				, 'N2DR4', 'PNP10', 'PNP4', 'RACK3'
			);
			if (in_array($timer_ack, $timers_array)) {
				Application_Model_General::writeToTimersActivity($this->getHeaders(), $timer_ack);
			} 


			return $timer_ack;
		}
		return true;
	}

	/**
	 * validation for requests from internal
	 * 
	 * @return bool 
	 */
	public function InternalPostValidate() {
		$ret = $this->validateParams($this->getHeaders());
		$this->setAck($ret);
		return $this->ValidateDB();
	}

	/**
	 * checks if db returned row object with data
	 * 
	 * @param array $request
	 * @return bool 
	 */
	protected function RequestValidateDB($request) {

		if (is_object($request) && property_exists($request, "status")
			&& $request->status
			&& property_exists($request, "last_transaction")) {
			return true;
		}
		return false;
	}

	/**
	 * creates object from requests table row by REQUEST_ID if response returns
	 * requestValidateDB validation else FALSE
	 * 
	 * @return bool 
	 */
	protected function ValidateDB() {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()
			->where('request_id =?', $this->getHeaderField("REQUEST_ID"))
			->order('id DESC');
		$result = $select->query()->fetchObject();
		if ($result !== FALSE) {
			// set the variables used in timers
			$msg_type = strtoupper($this->getHeaderField('MSG_TYPE'));
			if ($msg_type == 'UPDATE_RESPONSE' || $msg_type == "CANCEL_RESPONSE" || $msg_type == 'UPDATE') {
				if ($msg_type != 'UPDATE') {
					$select = Np_Db::slave()->select()->from('Transactions')
						->where('request_id =?', $this->getHeaderField("REQUEST_ID"))
						->order('id DESC');
				} else {
					$select = Np_Db::slave()->select()->from('Transactions')
						->where('request_id =?', $this->getHeaderField("REQUEST_ID"))
						->where('message_type =?', "Request")
						->order('id DESC');
				}
				$last_transaction = $select->query()->fetchObject();
				$this->last_method = $last_transaction->message_type;
				$this->last_method_time = $last_transaction->last_transactions_time;
			} else {
				$this->last_method = $result->last_transaction;
				$this->last_method_time = $result->last_requests_time;
			}
			$this->last_transfer_time = $result->transfer_time;
			return $this->RequestValidateDB($result);
		}
		return false;
	}

//	function createXML(){
//		$body = $this->getBody();
//		//This is the variable that will be typed as an XSD string
//		$deviceId = new SoapVar(array("xsd1:id" => 1234), XSD_ANYTYPE, "xsd1:LogicalDeviceId", "http://www.w3.org/2001/XMLSchema-instance", "deviceId");
//	}

	/**
	 * updates requests table row by request_id set last transaction
	 * msg_type
	 * 
	 * @return bool
	 */
	public function saveToDB() {

		$updateArray = array(
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
		);
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		return $tbl->update($updateArray, $whereArray);
	}

	/**
	 * validates header params for SOAP 
	 * returns matching ack code
	 * 
	 * @param array $params header array
	 * @return string ack code 
	 * @TODO: make validation functions for each validation type
	 */
	public function validateparams($params) {
		$regexArray = array(
			"REQUEST_ID" => "/NP[A-Z]{4}(\d{2}((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)|([02468][048]|[13579][26])0229)[0-9]{9}/",
			"TRX_NO" => "/[A-Z]{2}[0-9]{12}/",
			"REQUEST_TRX_NO" => "/[A-Z]{2}[0-9]{12}/",
			"RETRY_DATE" => "/^(\d{4})\D?(0[1-9]|1[0-2])\D?([12]\d|0[1-9]|3[01])(\D?([01]\d|2[0-3])\D?([0-5]\d)\D?([0-5]\d)?\D?(\d+)?([zZ]|([\+-])([01]\d|2[0-3])\D?([0-5]\d)?)?)?$/",
		);
		$arrayArrays = array(
			"PROCESS_TYPE" => array('PORT', 'RETURN', 'QUERY', 'MAINT'),
			"MSG_TYPE" => array('Check', 'Check_response', 'Request',
				'Request_response', 'Update', 'Update_response', 'Cancel',
				'Cancel_response', 'KD_update', 'KD_update_response', 'Execute',
				'Execute_response', 'Publish', 'Publish_response',
				'Cancel_publish', 'Cancel_publish_response', 'Return',
				'Return_response', 'Down_system', 'Up_system', 'Inquire_number',
				'Inquire_number_response'),
		);
		foreach ($params as $key => $val) {
			if (!settype($val, "string") || !isset($val) || empty($val)) {
				error_log($key . " doesn't exists or empty");
				return $ack = "Ack01";
			}
			switch ($key) {
				case 'REQUEST_ID':
				case 'TRX_NO':
				case 'REQUEST_TRX_NO':
				case 'RETRY_DATE':
					if (preg_match($regexArray[$key], $val) !== 1) {
						error_log($key . " isn't valid: " . $val);
						return $ack = "Ack02";
					}
					break;
				case 'PROCESS_TYPE':
				case 'MSG_TYPE':
					if (!in_array($val, $arrayArrays[$key])) {
						error_log($key . " isn't valid: " . $val);
						return $ack = "Ack02";
					}
					break;
				case 'VERSION_NO':
					if (!is_numeric($val) || $val > 999 || $val < 1) {
						error_log($key . " isn't valid: " . $val);
						return $ack = "Ack02";
					}
					break;
				case 'RETRY_NO':
					if ($val > 999 || $val < 1) {
						error_log($key . " isn't valid: " . $val);
						return $ack = "Ack02";
					}
					break;
				case 'TO':
				case 'FROM':
					if (!is_string($val) || strlen($val) != 2) {
						error_log($key . " isn't valid: " . $val);
						return $ack = "Ack02";
					}
					break;
			}
		}
		return "Ack00";
	}

	public function checkIfJew() {

		$nowJew = jdtojewish(gregoriantojd(date('M'), date('D'), date('Y')));
		var_dump($nowJew);
		die;
	}

}
