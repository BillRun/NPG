<?php

/**
 * Np_Method_Up_System File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Up_System
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Up_System Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Up_System
 */
class Np_Method_UpSystem extends Np_Method {

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
	
	}

	/**
	 * overridden function from parent Np_Method
	 * 
	 * inserts row to requests table
	 * 
	 * @return bool 
	 */
	public function saveToDB() {
		//INSERT into Requests

		if ($this->getHeaderField("TO") == Application_Model_General::getSettings('InternalProvider')) {
			// if the check received from external provider create request
			// request id already exists
			//else - it's from internal - already INSERT into Requests
			$data = array(
				'request_id' => $this->getHeaderField("REQUEST_ID"),
				'from_provider' => $this->getHeaderField("FROM"),
				'to_provider' => $this->getHeaderField("TO"), // ב"ר קולט שולח הודעה
				'status' => 0,
				'last_transaction' => $this->getHeaderField("MSG_TYPE"),
				'number' => $this->getBodyField("NUMBER"),
			);
			$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			return $tbl->insert($data);
		}
	}

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$xml->$msgType = "";
		return $xml;
	}

}
