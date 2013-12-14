<?php

/**
 * Np_Method_PublishResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_PublishResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_PublishResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
class Np_Method_PublishResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "PublishResponse"
	 * 
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
	 * overridden from parent Np_Method
	 * updates status and last transaction in requests table 
	 * where request_id 
	 * 
	 * 
	 * @return bool 
	 */
	public function saveToDB() {
		if ($this->getHeaderField("FROM") == Application_Model_General::getSettings('InternalProvider')) {
			//send request response from internal to provider - update DB
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$updateArray = array(
				'status' => 0,
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
			);
			$whereArray = array(
				'request_id =?' => $this->getHeaderField("REQUEST_ID"),
			);
			return $tbl->update($updateArray, $whereArray);
		}
		//else //cron will take care of it. save only in transaction
	}

}
