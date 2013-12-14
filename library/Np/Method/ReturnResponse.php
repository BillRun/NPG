<?php

/**
 * Np_Method_ReturnResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_ReturnResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_ReturnResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
class Np_Method_ReturnResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "Return Response"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Request_trx_no":
				case "Approval_ind":
				case "Reject_reason_code":
					$this->setBodyField($key, $value);
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
			Application_Model_General::writeToTimersActivity($this->getHeaders(), $timer_ack);
			
			return $timer_ack;
		}

		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$updateArray = array(
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
		);
		
		// if we received success return_response and we are not the initiator we need to publish it after (so leave it with status on)
		if ($this->getHeaderField("TO") == Application_Model_General::getSettings('InternalProvider')) {
			$updateArray['status'] = 0;
		} else {
			$updateArray['status'] = 1;
		}
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		
		if (!$tbl->update($updateArray, $whereArray)) {
			return false;
		}
		return true;
	}
	

}
