<?php

/**
 * Np_Method_RequestResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_RequestResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_RequestResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
class Np_Method_RequestResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's constructor 
	 * accordingly sets parent's $type to "RequestResponse"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Request_retry_date":
				case "Request_trx_no":
				case "Approval_ind":
				case "Reject_reason_code":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * checks if db response exists and if property request exists on it
	 * 
	 * @param object $request
	 * @return bool 
	 */
	public function RequestValidateDB($request) {
		if (parent::RequestValidateDB($request) &&
				$request->last_transaction == "KD_update_response" || $request->last_transaction == "KD_update" ||
				$request->last_transaction == "Request" || $request->last_transaction == "Request_response") {
			return true;
		}
		return false;
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
//		if (!$this->ValidateDB()) {
//			return "Gen07";
//		}
		if (($timer_ack = Np_Timers::validate($this)) !== TRUE) {
			Application_Model_General::writeToTimersActivity($this->getHeaders(), $timer_ack);
			
			return $timer_ack;
		}
		return true;
	}
	
	/**
	 * overridden from np_method ,updates requests , sets status to 1 ,
	 * last transaction to msg_type and requested transfer time
	 * 
	 * @return int number of affected rows
	 */
	public function saveToDB() {
		if ($this->checkApprove() === FALSE) {
			return FALSE;
		}
		
		$updateArray = array(
			'status' => 1,
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
			'transfer_time' => Application_Model_General::getTrxPortTime($this->getBodyField("REQUEST_TRX_NO")),
		);
		
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		return $tbl->update($updateArray, $whereArray);
	}

}
