<?php

/**
 * Np_Method_Return File
 * 
 * @package         Np_Method
 * @subpackage      Np_Method_Return
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Return Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Return
 */
class Np_Method_Return extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "Return"
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
					$this->setBodyField($key, $value);
					break;
				case "Phone_number":
					$this->setBodyField('Number', $value);
					break;
			}
		}
	}

	public function PostValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));
		//first step is GEN
		if (!$this->checkDirection()) {
			return "Gen04";
		}
		//HOW TO CHECK Gen05
//		if (!$this->ValidateDB()) {
//			return "Gen07";
//		}
		if (($timer_ack = Np_Timers::validate($this)) !== TRUE) {
			return $timer_ack;
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
		//INSERT into Requests

		if ($this->getHeaderField("TO") == Application_Model_General::getSettings('InternalProvider')) {
			// if the check received from external provider create request
			// request id already exists
			//else - it's from internal - already INSERT into Requests
			$data = array(
				'request_id' => $this->getHeaderField("REQUEST_ID"),
				'from_provider' => $this->getHeaderField("FROM"),
				'to_provider' => $this->getHeaderField("TO"),
				'status' => 1,
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
				'phone_number' => $this->getBodyField("NUMBER"),
			);
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			return $tbl->insert($data);
		}
	}

	/**
	 * method to create xml from the request
	 * 
	 * @return SimpleXml xml object
	 */
	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$numberType = $this->getBodyField('NUMBER_TYPE');
		$xml->$msgType->number = $this->getBodyField('NUMBER');
		if (Application_Model_General::getSettings("NetworkType") === "M") {
			$xml->$msgType->mobile;
			$xml->$msgType->mobile->networkType = "M";
			$xml->$msgType->mobile->numberType = $numberType;
		} else {
			$xml->$msgType->fixed->networkType = "M";
			$xml->$msgType->fixed->numberType = $numberType;
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
		$ret = array('NUMBER' => (string) $xmlObject->number);
		if (isset($xmlObject->mobile)) {
			$ret['NETWORK_TYPE'] = (string) $xmlObject->mobile->networkType;
			$ret['NUMBER_TYPE'] = (string) $xmlObject->mobile->numberType;
		} else {
			$ret['NETWORK_TYPE'] = (string) $xmlObject->fixed->networkType;
			$ret['NUMBER_TYPE'] = (string) $xmlObject->fixed->numberType;
		}
		return $ret;
	}


}
