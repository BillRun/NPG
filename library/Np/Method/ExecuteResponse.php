<?php

/**
 * Np_Method_ExecuteResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_ExecuteResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_ExecuteResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_ExecuteResponse
 */
class Np_Method_ExecuteResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array accordingly
	 * sets parent's $type to "ExecuteResponse"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {

				case "Disconnect_time":
				case "Request_retry_date":
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
	 * updates status and connect time in requests by request_id and last transaction
	 * 
	 * @param string $ack
	 * @return bool 
	 */
	public function updateDB_ack($ack) {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$update_arr = array('status' => 1, 'connect_time' => new Zend_Db_Expr('NOW()')); //$ack->connect_time);
		$where_arr = array(
			'request_id =?' => $this->getHeaderField('REQUEST_ID'),
			'last_transaction=?' => $this->getHeaderField('MSG_TYPE'),
		);
		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	/**
	 * updates status , last transaction and disconnect time in requests table 
	 * where request_id 
	 * 
	 * overridden from parent Np_Method
	 * 
	 * @return bool 
	 */
	public function saveToDB() {
		if (parent::saveToDB() === FALSE) {
			return FALSE;
		}
		$updateArray = array(
			'status' => 1,
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
			'disconnect_time' => Application_Model_General::getTimeInSqlFormat($this->getBodyField("DISCONNECT_TIME")),
		);
		// if it's execute_response that leaves, status => 0 (no more actions)
		if ($this->getHeaderField("FROM") == Application_Model_General::getSettings('InternalProvider')) {
			$updateArray['status'] = 0;
		}
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		return $tbl->update($updateArray, $whereArray);
	}

}
