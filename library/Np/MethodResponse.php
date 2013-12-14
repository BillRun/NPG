<?php

/**
 * Np_Method Class
 * Model for Number Transaction operations.
 * 
 * @package Np_Method
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
abstract class Np_MethodResponse extends Np_Method {

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
				case "Request_trx_no":
				case "Approval_ind":
				case "Reject_reason_code":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	protected function checkApprove() {
		if ($this->getBodyField("APPROVAL_IND") === "Y") {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * override saveToDB to make another
	 * @return type 
	 */
	public function saveToDB() {
		if ($this->checkApprove()) {
			return parent::saveToDB();
		}
		return FALSE;
	}

	public function createXml() {
		$xml = parent::createXml();

		$msgType = $this->getHeaderField('MSG_TYPE');

		if ($this->request->getBodyField("APPROVAL_IND") === "Y") {
			$xml->$msgType->positiveApproval;
			$xml->$msgType->positiveApproval->approvalInd = "Y";
		} else {
			$xml->$msgType->negativeApproval;
			$xml->$msgType->negativeApproval->approvalInd = "N";
			$rejectReasonCode = $this->getFieldIfExists('REJECT_REASON_CODE');
			$xml->$msgType->negativeApproval->rejectReasonCode = ($rejectReasonCode !== NULL)?$rejectReasonCode:'';
		}
		$xml->$msgType->requestTrxNo = $this->getFieldIfExists('REQUEST_TRX_NO');
		$xml->$msgType->requestRetryDate = $this->getFieldIfExists('REQUEST_RETRY_DATE');

		return $xml;
	}

}
