<?php

/**
 * KDupdate Class File
 * 
 * 
 * @package Np_Method
 * @subpackage Np_Method_KDUpdate
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * KDupdate Class definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_KDupdate
 */
class Np_Method_KDUpdate extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct function 
	 * accordingly  and sets parent's type property to KDupdate
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Network_type":
				case "Number_type":
				case "Number":
				case "From_number":
				case "To_number":
				case "KD_Update_type"://REQUIER
				case "Identification_value":
				case "Identification_value_2nd":
				case "Identification_value_3rd":
				case "Port_time":
				case "Remark":
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
				(
				(
				(
						
				$request->last_transaction == "Request_response" ||
				$request->last_transaction == "Update_response"
				) &&
				1 == $request->status)
				||
				$request->last_transaction == "Cancel_response"
				)
		) {
			return true;
		}
		return false;
	}

	/**
	 * overridden from np_method , updates requests table row by 
	 * request_id  and last transaction msg_type
	 * 
	 * 
	 * @return int number of affected rows
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

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$networkType = Application_Model_General::getSettings("NetworkType");
		$numberType = $this->getBodyField('NUMBER_TYPE');
		$requestId = $this->getHeaderField('REQUEST_ID');
		$requestRecord = Application_Model_General::getFieldFromRequests(array('flags', 'transfer_time'), $requestId, 'request_id');
		$number = $this->getBodyField('NUMBER');

		$flags = json_decode($requestRecord['flags'], true);
		$portTime = Application_Model_General::getDateIso($requestRecord['transfer_time']);
		$process_type= $this->getHeaderField('PROCESS_TYPE');
		if ($process_type=="PORT") {
			$path = &$xml->$msgType->updateRequestType;
			$path->updateType = "REQUEST";
			$path->portingDateTime = $portTime;
		} else if ($process_type=="CANCEL") {
			$path = &$xml->$msgType->updateCancelTypeType;
			$path->updateType = "CANCEL";
			$path->portingDateTime = $portTime;
		} else if ($process_type=="UPDATE") {
			$path = &$xml->$msgType->updateUpdateTypeType;
			$path->updateType = "UPDATE";
			$path->portingDateTime = $portTime;
		}

		if ($networkType === "M") {
			$networkType = "mobile";
		} else {
			$networkType = "fixed";
		}
		$path->$networkType;
		$path->$networkType->networkType = "M";

		if ($numberType === "I") {
			$path->$networkType->mobileNumberIdentified;
			$path->$networkType->mobileNumberIdentified->numberType = $numberType;
			$path->$networkType->mobileNumberIdentified->identificationValue = isset($flags['identification_value'])?$flags['identification_value']:'';
			$path->$networkType->mobileNumberIdentified->identificationValue2nd = 'default';
			$path->$networkType->mobileNumberIdentified->identificationValue3rd = 'default';
			$path->$networkType->mobileNumberIdentified->number = $number;
		} else {
			$path->$networkType->mobileNumberUnidentified;
			$path->$networkType->mobileNumberUnidentified->numberType = $numberType;
			$path->$networkType->mobileNumberUnidentified->number = $number;
		}

		return $xml;
	}
}
