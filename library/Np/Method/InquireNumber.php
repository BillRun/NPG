<?php

/**
 * Np_Method_Return File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Return
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Return Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Return
 */
class Np_Method_InquireNumber extends Np_Method {

	var $type = "InquireNumber";

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "Return"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Number":
					$this->setBodyField($key, $value);
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
			Application_Model_General::writeToTimersActivity($this->getHeaders(), $timer_ack);
			return $timer_ack;
		}
		return true;
	}
	
	public function saveToDB() {
		if ($this->getHeaderField("TRX_NO")) {
			//this is a request from provider!
			//save a new row in Requests DB
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());

			$data = array(
				'status' => 1,
				'request_id' => $this->getHeaderField("REQUEST_ID"),
				'number' => $this->getBodyField("NUMBER"),
				'from_provider' => $this->getHeaderField("TO"),
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
				'to_provider' => $this->getHeaderField("FROM")
			);
			return $tbl->insert($data);
		}
		//else //this request is from cron! internal is sending to all providers
		//don't save in Requests DB
	}
	
	/**
	 * method to create xml from the request
	 * 
	 * @return SimpleXml xml object
	 */
	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$xml->$msgType->number = $this->getBodyField('NUMBER');
		return $xml;
	}

}
