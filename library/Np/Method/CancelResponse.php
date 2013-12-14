<?php

/**
 * Np_Method_CancelResponse File
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_CancelResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelResponse
 */
class Np_Method_CancelResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * calls parent constructor , sets type "CancelResponse" 
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
	 * checks if db object exists and last transaction is Cancel
	 * 
	 * @return bool 
	 */
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
