<?php

/**
 * Np_Method_CancelPublishResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelPublishResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_CancelPublishResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelPublishResponse
 */
class Np_Method_CancelPublishResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * calls parent constructor , sets type "CancelPublishResponse"
	 * and places params in  body fields 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Route_time":
				case "Request_trx_no":
				case "Approval_ind":
				case "Reject_reason_code":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * extended function from parent Np_Method
	 * checks if db object exists and last transaction is Cancel_publish
	 * 
	 * @return bool 
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
        
        
//	public function RequestValidateDB($request) {
//		if (parent::RequestValidateDB($request) &&
//				$request->last_transaction == "Cancel_publish") {
//			return true;
//		}
//		return false;
//	}
	
	
	public function saveToDB() {
		if ($this->checkApprove() === TRUE) {
			$updateArray = array(
				'status' => 0,
				'last_transaction' => $this->getHeaderField("MSG_TYPE")
			);
			$whereArray = array(
				'request_id =?' => $this->getHeaderField("REQUEST_ID"),
			);
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			return $tbl->update($updateArray, $whereArray);
		}
		return FALSE;
	}

}
