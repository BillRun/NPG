<?php

/**
 * Np_Method_Update File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Update
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Update Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Update
 */
class Np_Method_Update extends Np_Method {

    /**
     * Constructor
     * 
     * receives options array and sets into body array and parent's construct
     * accordingly sets parent's $type to "Update"
     * 
     * @param array $options 
     */
    protected function __construct($options) {
        parent::__construct($options);

        //SET BODY 
        foreach ($options as $key => $value) {
            switch (ucwords(strtolower($key))) {
                case "Port_time":
                    $this->setBodyField($key, $value);
                    break;
            }
        }
    }

    /**
     * overridden from parent , validates last transaction
     * 
     * @param type $request the request parameters
     * @return bool  
     */
    public function RequestValidateDB($request) {
        if (parent::RequestValidateDB($request) &&
                ($request->last_transaction == "Request_response" ||
                $request->last_transaction == "Update" ||
                $request->last_transaction == "Update_response" ||
                $request->last_transaction == "KD_update" ||
                $request->last_transaction == "‫‪KD_update_response‬‬")) {
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

    public function saveToDB() {
//		if ($this->checkApprove() === FALSE) {
//			return FALSE;
//		}
//        $transfer_time = $this->getBodyField("PORT_TIME");


        $msg_type = $this->getHeaderField("MSG_TYPE");
        $updateArray = array(
            'status' => 1,
            'last_transaction' => $msg_type,
        );
        $whereArray = array(
            'request_id =?' => $this->getHeaderField("REQUEST_ID"),
        );
        $tbl = new Application_Model_DbTable_Requests(Np_Db::master());
        $ret = $tbl->update($updateArray, $whereArray);

        return $ret;
    }
	
	/**
	 * method to create xml from the request
	 * 
	 * @return SimpleXml xml object
	 */
	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$xml->$msgType->portingDateTime = Application_Model_General::getDateIso($this->getBodyField('PORT_TIME'));;
		return $xml;
	}

}
