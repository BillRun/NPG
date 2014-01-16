<?php

/**
 * Np_Method_CheckResponse File
 * 
 * @package         Np_Method
 * @subpackage      Np_Method_CheckResponse
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Check Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_MethodResponse
 */
class Np_Method_CheckResponse extends Np_MethodResponse {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array accordingly
	 * 
	 * @param array $options 
	 */
	protected function __construct(&$options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Essential_info_1":
				case "Essential_info_2":
				case "Essential_info_3":
				case "Essential_info_4":
				case "Essential_info_5":
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
	 * checks if db object exists and last transaction is check
	 * 
	 * @return bool 
	 */
	public function RequestValidateDB($request) {
		if (parent::RequestValidateDB($request) &&
			$request->last_transaction == "Check" || $request->last_transaction == "Check_response") {
			return true;
		}
		return false;
	}

	protected function checkApprove() {
		// reset request id if check response not succeed
		if (!parent::checkApprove()) {
			$updateArray = array(
				'status' => 0,
			);
			$whereArray = array(
				'request_id =?' => $this->getHeaderField("REQUEST_ID"),
			);
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$tbl->update($updateArray, $whereArray);
			return false;
		}
		return true;
	}

	public function createXml() {
		$xml = new SimpleXMLElement('<npMessageBody xmlns=""></npMessageBody>');
		$msgType = $this->getHeaderField('MSG_TYPE');
		$this->addTrxNoXml($xml, $msgType);
		$this->addApprovalXml($xml, $msgType);
		$xml->$msgType->essentialInfo1;
		$xml->$msgType->essentialInfo2;
		$xml->$msgType->essentialInfo3;
		$xml->$msgType->essentialInfo4;
		$xml->$msgType->essentialInfo5;

		return $xml;
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
		$ret['ESSENTIAL_INFO_1'] = (string) $xmlObject->essentialInfo1;
		$ret['ESSENTIAL_INFO_2'] = (string) $xmlObject->essentialInfo2;
		$ret['ESSENTIAL_INFO_3'] = (string) $xmlObject->essentialInfo3;
		$ret['ESSENTIAL_INFO_4'] = (string) $xmlObject->essentialInfo4;
		$ret['ESSENTIAL_INFO_5'] = (string) $xmlObject->essentialInfo5;
		return $ret;
	}

}
