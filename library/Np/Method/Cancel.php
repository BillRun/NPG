<?php

/**
 * Np_Method_Cancel File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Cancel
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Check Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Cancel
 */
class Np_Method_Cancel extends Np_Method {

	/**
	 * Constructor
	 * 
	 * calls parent constructor , sets type "cancel" 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY - there is no Body in addition to Method
	}

	/**
	 * extended function from parent Np_Method
	 * checks if db object exists and last transaction is Request_response or
	 * Update_response
	 * 
	 * @return bool 
	 */
	public function RequestValidateDB($request) {
		if (parent::RequestValidateDB($request) &&
				($request->last_transaction == "Cancel" ||
				$request->last_transaction == "Cancel_response" ||
				$request->last_transaction == "Request_response" ||
				$request->last_transaction == "Update_response" ||
				$request->last_transaction == "KD_update" ||
				$request->last_transaction == "‫‪KD_update_response‬‬" )
		) {
			return true;
		}
		return false;
	}

	/**
	 * validation for requests from internal
	 * 
	 * @return bool 
	 */
	public function InternalPostValidate() {

		return TRUE;
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
		if (!$this->ValidateDB()) {
			return "Gen07";
		}
		if (($timer_ack = Np_Timers::validate($this)) !== TRUE) {

			return $timer_ack;
		}

		return true;
	}

	/**
	 * override saveToDB to make another
	 * @return type 
	 */
	public function saveToDB() {
		if (parent::saveToDB() === TRUE) {
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
	
	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		// TODO: check with spec
		$xml->$msgType = "";
		return $xml;
	}


}
