<?php

/**
 * Np_Method_Request File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Request
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Request Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Request
 */
class Np_Method_Request extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "request"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);
//		Application_Model_General::checkIfRetry($requestId,$lastTransaction);
		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Port_time":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * extended function from parent Np_Method
	 * checks if db object exists and last transaction is check_response
	 * 
	 * @return bool 
	 */
	public function RequestValidateDB($request) {
		if (parent::RequestValidateDB($request) &&
				($request->last_transaction == "Request" || $request->last_transaction == "Request_response" ||
				$request->last_transaction == "Check_response")) {
			return true;
		}
		return false;
	}

	
	public function PostValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));
		//first step is GEN
		if (!$this->checkDirection()) {
			return "Gen04";
		}
		//HOW TO CHECK Gen05
		if (!$this->ValidateDB()) {
			return "Gen07";
		}
		if (($timer_ack = Np_Timers::validate($this)) !== TRUE) {
			Application_Model_General::writeToTimersActivity($this->getHeaders(), $timer_ack);
			
			return $timer_ack;
		}
		return true;
	}
	
	
	/**
	 * overridden from parent
	 * 
	 * update status , last_transaction and transfer time  where request id
	 * 
	 * @return bool 
	 */
	public function saveToDB() {
		$updateArray = array(
			'status' => 1,
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
//			'transfer_time' => Application_Model_General::getTimeInSqlFormatFlip($this->getBodyField("PORT_TIME"))
		);
		$whereArray = array(
			'request_id = ?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		return $tbl->update($updateArray, $whereArray);
	}
	
	/**
	 * method to create xml from the request
	 * 
	 * @return SimpleXml xml object
	 */
	public function createXml() {
		$xml = parent::createXml();
		
		$msgType = $this->getHeaderField('MSG_TYPE');
		
		$xml->$msgType->portingDateTime = Application_Model_General::getDateIso($this->getBodyField('PORT_TIME'));;
		
		return $xml;
	}


}
