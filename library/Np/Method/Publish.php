<?php

/**
 * Np_Method_Publish File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Publish
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Publish Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Publish
 */
class Np_Method_Publish extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array accordingly
	 * sets parent's $type to "Publish"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {

		parent::__construct($options);
		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Donor":
				case "Network_type":
				case "Number":
				case "From_number":
				case "To_number":
				case "Disconnect_time":
				case "Connect_time":
				case "Publish_type":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	/**
	 * overrridden from np_method
	 * 
	 * @return TRUE 
	 */
	protected function ValidateDB() {
		return true;
	}

	/**
	 * overridden from np_method
	 * 
	 * @return mixed string Reject Reason Code or TRUE 
	 */
	public function PostValidate() {
		$this->setAck($this->validateParams($this->getHeaders()));

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
	 * overridden from parent
	 * 
	 * inserts row to database
	 * 
	 * @return type 
	 */
	public function saveToDB() {
		//this is a request from provider!
		//save a new row in Requests DB
		if ($this->getHeaderField("FROM") != Application_Model_General::getSettings('InternalProvider')) {
			$flags = new stdClass();
			$flags->publish_type = $this->getBodyField("PUBLISH_TYPE");
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$data = array(
				'request_id' => $this->getHeaderField("REQUEST_ID"),
				'from_provider' => $this->getHeaderField("TO"),
				'to_provider' => $this->getHeaderField("FROM"),
				'status' => 1,
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
				'number' => $this->getBodyField("NUMBER"),
				'disconnect_time' => $this->getBodyField("DISCONNECT_TIME"),
				'connect_time' => $this->getBodyField("CONNECT_TIME"),
				'flags' => json_encode($flags),
			);

			return $tbl->insert($data);
		}
		return TRUE;
	}

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$networkType = Application_Model_General::getSettings("NetworkType");
		$xml->$msgType->donor = $this->getBodyField('DONOR');
		$xml->$msgType->connectDateTime = Application_Model_General::getDateIso($this->getBodyField('CONNECT_TIME'));
		$xml->$msgType->publishType = $this->getBodyField('PUBLISH_TYPE');
		$xml->$msgType->disconnectDateTime = Application_Model_General::getDateIso($this->getBodyField('DISCONNECT_TIME'));
		if ($networkType === "M") {
			$xml->$msgType->mobile;
			$xml->$msgType->mobile->numberType = "I";
			$xml->$msgType->mobile->number = $this->request->getBodyField("NUMBER");
		} else {
			$xml->$msgType->fixed->fixedNumberSingle;
		}
		return $xml;
	}
}

