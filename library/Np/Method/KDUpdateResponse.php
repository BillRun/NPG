<?php

/**
 * Np_Method_KDUpdateResponse File
 * Model for Number Transaction operations.
 * 
 * @package Np_Method
 * @subpackage Np_Method_KDUpdateResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_KDupdateResponse Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_KDUpdateResponse
 */
class Np_Method_KDUpdateResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "KDupdateResponse"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "KD_update_type":
				case "Request_trx_no":
				case "Approval_ind":
				case "Reject_reason_code":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * overridden function from parent Np_Method
	 * checks if db object exists and last transaction is request_response,
	 * update_response or cancel_response
	 * 
	 * @return bool 
	 */
	protected function RequestValidateDB($request) {
		if (is_object($request) && property_exists($request, "status") &&
			property_exists($request, "last_transaction") &&
			$request->last_transaction == "KD_update"
		) {
			$db = Np_Db::slave();
			$select = $db->select()->from("Transactions")
				->where("request_id = ?", $this->getHeaderField("REQUEST_ID"))
				->where('message_type IN (?)', array("Request_response", "Update_response", "Cancel_response"))
				->where('reject_reason_code IS NULL')
				->order('id DESC');
			$result = $select->query()->fetchObject();
			if ($result === FALSE) {
				return FALSE;
			}
			if ($result->last_transaction != "Cancel_response" &&
				0 === $request->status) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * overridden from np_method 
	 * 
	 * 
	 * @return bool true if transaction recipient is not internal
	 */
	public function saveToDB() {
		$updateArray = array(
			'last_transaction' => $this->getHeaderField("MSG_TYPE"),
		);
		$whereArray = array(
			'request_id =?' => $this->getHeaderField("REQUEST_ID"),
		);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		return $tbl->update($updateArray, $whereArray);
	}

}
