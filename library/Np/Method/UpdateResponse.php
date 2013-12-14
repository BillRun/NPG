<?php

/**
 * Np_Method_UpdateResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_UpdateResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_UpdateResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
class Np_Method_UpdateResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "UpdateResponse"
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
	 * extended function from parent Np_Method
	 * checks if db object exists and last transaction is Update
	 * 
	 * @return bool 
	 */
	public function RequestValidateDB($request) {
		if (isset($request->last_transaction) &&
				($request->last_transaction == "Request_response" || $request->last_transaction == "Update" ||
				$request->last_transaction == "Update_response" ||
				$request->last_transaction == "KD_update" ||
				$request->last_transaction == "‫‪KD_update_response‬‬")) {
			return parent::RequestValidateDB($request);
		}
		return false;
	}

	/**
	 * overridden from parent
	 * 
	 * update status,last_transaction and transfer_time in requests table by 
	 * request_id
	 * 
	 * @return bool
	 */
	public function saveToDB() {
		if ($this->checkApprove() === FALSE) {
			return FALSE;
		}
		
		$trxno = $this->getBodyField("REQUEST_TRX_NO");
		$msg_type = $this->getHeaderField("MSG_TYPE");
		$updateArray = array(
			'status' => 1,
			'last_transaction' => $msg_type,
			'transfer_time' => application_model_general::getTrxPortTime($trxno),
		);
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$ret = $tbl->update($updateArray, $whereArray);

		return $ret;
	}

}
