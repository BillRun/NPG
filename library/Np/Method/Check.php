<?php

/**
 * Np_Method_Check File
 * 
 * @package         Np_Method
 * @subpackage      Np_Method_Check
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Check Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Check
 */
class Np_Method_Check extends Np_Method {

	/**
	 * required body keys for Check MSG_TYPE
	 * 
	 * @var array 
	 */
	static $require_body_key = array("NUMBER", "NETWORK_TYPE");

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array accordingly
	 * 
	 * @param array $options 
	 */
	protected function __construct(&$options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Network_type":
				case "Number_type":
				case "Number":
				case "From_number":
				case "To_number":
				case "Identification_value":
				case "Identification_value_2nd":
				case "Identification_value_3rd":
				case "Port_time": //when internal provider sending request directly
					$this->setBodyField($key, $value);
					break;
				case "Phone_number":
					$this->setBodyField('Number', $value);
					break;

			}
		}
	}

	/**
	 * 
	 * 
	 * @return bool are required body fields present 
	 */
	protected function validateBody() {
		foreach (self::$require_body_key as $key) {
			if (!$this->getBodyField($key)) {
				error_log('Require field is missing: ' . $key);
				$this->setAck('Ack01');
				return false;
			}
		}
		return true;
	}

	//from provider
	/**
	 * overridden function from parent Np_Method
	 * checks if db row exists
	 * 
	 * @return bool 
	 */
	protected function ValidateDB() {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->where('request_id =?', $this->getHeaderField("REQUEST_ID"));
		$result = $select->query()->fetchObject();
		if ($result) {
			return false; //if there is already request with this request_id.
		}
		return true;
	}

	public function InternalPostValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));
		return true;
	}

	/**
	 *
	 * extended function from parent Np_Method
	 * 
	 * @return mixed bool string reject reason code or TRUE (valid)
	 */
	public function PostValidate() {
		$ret = parent::PostValidate();
		if ($ret !== true) {
			return $ret;
		}

		if ($this->getBodyField("NETWORK_TYPE") != Application_Model_General::getSettings("NetworkType")) {
			return "Chk18";
		}
		$inProcessArray = Application_Model_General::$inProcess;
		$inProcess = Application_Model_General::getFieldFromRequests('last_transaction', $this->getBodyField('NUMBER'));
		if (!empty($inProcess) && in_array($inProcess, $inProcessArray)) {
			return "Chk04";
		}

		$number = $this->getBodyField("NUMBER");
		if (!is_numeric($number) || strlen($number) < 8) {
			$this->setAck('Ack02');
			return false;
		}

		return true;
	}

	/**
	 * overridden function from parent Np_Method
	 * 
	 * inserts row to requests table
	 * 
	 * @return bool 
	 */
	public function saveToDB() {
		if ($this->ValidateDB() === FALSE) {
			RETURN FALSE;
		}

		if ($this->getHeaderField("FROM") != Application_Model_General::getSettings('InternalProvider')) {
			// if the check received from external provider create request
			// request id already exists
			//else - it's from internal - already INSERT into Requests
			try {
				$data = array(
					'request_id' => $this->getHeaderField("REQUEST_ID"),
					'from_provider' => $this->getHeaderField("TO"),
					'to_provider' => $this->getHeaderField("FROM"),
					'status' => 1,
					'last_transaction' => $this->getHeaderField("MSG_TYPE"),
					'phone_number' => $this->getBodyField("NUMBER"),
				);
				$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
				return $tbl->insert($data);
			} catch (Exception $e) {
				error_log("Error on create record in transactions table: " . $e->getMessage());
			}
		}
	}

	/**
	 * method to create xml from the request
	 * 
	 * @return SimpleXml xml object
	 */
	public function createXml() {
		$xml = parent::createXml();

		$networkType = Application_Model_General::getSettings("NetworkType");
		$numberType = $this->getBodyField('NUMBER_TYPE');
		$msgType = $this->getHeaderField('MSG_TYPE');
		$number = $this->getBodyField('NUMBER');

		if ($networkType === "M") {
			$networkType = "mobile";
		} else {
			$networkType = "fixed";
		}

		$xml->$msgType->$networkType;
		$xml->$msgType->$networkType->networkType = "M";

		if ($numberType === "I") {
			$xml->$msgType->$networkType->mobileNumberIdentified->numberType = $numberType;
			$xml->$msgType->$networkType->mobileNumberIdentified->identificationValue = $this->getBodyField('IDENTIFICATION_VALUE');
			$xml->$msgType->$networkType->mobileNumberIdentified->identificationValue2nd = '';
			$xml->$msgType->$networkType->mobileNumberIdentified->identificationValue3rd = '';
			$xml->$msgType->$networkType->mobileNumberIdentified->number = $number;
		} else {
			$xml->$msgType->$networkType->mobileNumberUnidentified->numberType = $numberType;
			$xml->$msgType->$networkType->mobileNumberUnidentified->number = $number;
		}

		return $xml;
	}
	
	/**
	 * convert Xml data to associative array
	 * 
	 * @param simple_xml $xmlObject simple xml object
	 * 
	 * @return array converted data from hierarchical xml to flat array
	 */
	public function convertArray($xmlObject) {
		$ret = array();
		$networkTypeConfig = Application_Model_General::getSettings("NetworkType");
		if ($networkTypeConfig === "M") {
			$networkType = "mobile";
			$ret['NETWORK_TYPE'] = (string) $networkTypeConfig;
		} else {
			$networkType = "fixed";
			$ret['NETWORK_TYPE'] = (string) $networkTypeConfig;
		}

		if (!empty($xmlObject->$networkType->mobileNumberIdentified) && $xmlObject->$networkType->mobileNumberIdentified !== NULL) {
			$ret['IDENTIFICATION_VALUE'] = (string) $xmlObject->$networkType->mobileNumberIdentified->identificationValue;
			$ret['IDENTIFICATION_VALUE_2ND'] = (string) $xmlObject->$networkType->mobileNumberIdentified->identificationValue2nd;
			$ret['IDENTIFICATION_VALUE_3RD'] = (string) $xmlObject->$networkType->mobileNumberIdentified->identificationValue3rd;
			$ret['NUMBER_TYPE'] = (string) $xmlObject->$networkType->mobileNumberIdentified->numberType;
			$ret['PHONE_NUMBER'] = (string) $xmlObject->$networkType->mobileNumberIdentified->number;
		} else {
			$ret['NUMBER_TYPE'] = (string) $xmlObject->$networkType->mobileNumberUnidentified->numberType;
			$ret['PHONE_NUMBER'] = (string) $xmlObject->$networkType->mobileNumberUnidentified->number;
		}
		return $ret;
	}

}
