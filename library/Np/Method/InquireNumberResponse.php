<?php

/**
 * Np_Method_Return File
 * 
 * @package         Np_Method
 * @subpackage      Np_Method_Return
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Return Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Return
 */
class Np_Method_InquireNumberResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "Return"
	 * 
	 * @param array $options 
	 */
	protected function __construct(&$options) {
		parent::__construct($options);
		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "‫‪Request_trx_no‬‬":
				case "Current_operator":
				case "Number":
					$this->setBodyField($key, $value);
					break;
				case "Phone_number":
					$this->setBodyField("Number", $value);
					break;

			}
		}
	}

	protected function ValidateDB() {
		return TRUE;
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
			return $timer_ack;
		}
		return true;
	}

	public function saveToDB() {

		if (parent::saveToDB()) {
			//this is a request from provider!
			//save a new row in Requests DB

			$flags = new stdClass();
			$flags->inquire = $this->getBodyField("CURRENT_OPERATOR");

			$data = array(
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
				'status' => 0,
				'flags' => json_encode($flags),
			);

			$whereArray = array(
				'request_id =?' => $this->getHeaderField("REQUEST_ID"),
			);

			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$ret = $tbl->update($data, $whereArray);

			return $ret;
		}
		return false;
		//else //this request is from cron! internal is sending to all providers
		//don't save in Requests DB
	}
	
	protected function addApprovalXml(&$xml, $msgType) {
		if ($this->checkApprove()) {
			$xml->$msgType->positiveApproval;
			$xml->$msgType->positiveApproval->approvalInd = "Y";
			$xml->$msgType->positiveApproval->currentOperator = $this->getBodyField('CURRENT_OPERATOR');
		} else {
			$xml->$msgType->negativeApproval;
			$xml->$msgType->negativeApproval->approvalInd = "N";
			$rejectReasonCode = $this->getBodyField('REJECT_REASON_CODE');
			$xml->$msgType->negativeApproval->rejectReasonCode = ($rejectReasonCode !== NULL) ? $rejectReasonCode : '';
		}
	}
	
	protected function addTrxNoXml(&$xml, $msgType) {
		$xml->$msgType->requestTrxNo = $this->getBodyField('REQUEST_TRX_NO');
	}

	
	/**
	 * convert Xml data to associative array
	 * 
	 * @param simple_xml $xmlObject simple xml object
	 * 
	 * @return array converted data from hierarchical xml to flat array
	 */
	public function convertArray($xmlObject) {
		$ret = parent::convertArray($xmlObject);
		if (isset($xmlObject->positiveApproval) && isset($xmlObject->positiveApproval->currentOperator)) {
			$ret['CURRENT_OPERATOR'] = (string) $xmlObject->positiveApproval->currentOperator;
		}
		return $ret;
	}
	
}
