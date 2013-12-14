<?php

/**
 * This helper class take simple post array and parse it to xml
 * 
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Helper_Debug extends Np_Method {

	Protected $options;
	Protected $cA;

	/**
	 * @method __construct
	 * @param $options - a data array from the debug form.
	 * the constructor receives the $options array
	 * and checks to see whom it was intended for.
	 * it then proceeds to call the relevant function.
	 * (if sent to internal ->sendInternalSoap() or if sent to provider using 
	 * sendProviderData()) the result ACK CODE is stored into 
	 * $this->cA.
	 */
	function __construct($options) {

		$this->options = $options;
		if ($this->options['TO'] == Application_Model_General::getSettings('InternalProvider')) { // if our request is targeted at internal
			$this->cA = (array) $this->sendInternalSoap();

			if (isset($options['Header'])) {
				parent::__construct($options['Header']);
			}
		} else {


			$this->cA = $this->sendProviderData(); // if our request is targeted at the provider
		}
	}

	/**
	 * @method getAck
	 * returns the ACKCODE stored in $this->cA property	
	 */
	public function getAck() {

		return $this->cA;
	}

	/**
	 * @method getQaSoap
	 * gets submitted data from form and rearranges it into the 
	 * soap format defined by the wsdl file. additionally, 
	 * it adds missing data for diffrent transactions using 
	 * the switch case. uses hardcoded values .
	 */
	public function getQaSoap() {

		$result = $this->options;

		if (isset($result['submit'])) {
			unset($result['submit']);
		}
		$headerFieldArray = array('MSG_TYPE' => 'MSG_TYPE',
			'PROCESS_TYPE' => 'PROCESS_TYPE',
			'REQUEST_ID' => 'REQUEST_ID',
			'TRX_NO' => 'TRX_NO',
			'VERSION_NO' => 'VERSION_NO',
			'RETRY_NO' => 'RETRY_NO',
			'RETRY_DATE' => 'RETRY_DATE',
			'FROM' => 'FROM',
			'TO' => 'TO'
			
			
		);
		$xml = new SimpleXMLElement("<NETWORK_TYPE></NETWORK_TYPE>");
		$soapParams = array();
		foreach ($result as $key => $val) {
			if (in_array($key, $headerFieldArray)) {
				$soapParams['HEADER'][$key] = $result[$key];
			} else {
				$xml->$key = (string) $val;
			}
		}
		$soapParams['HEADER']['REQUEST_ID'] = $this->getReqIdByNum($result['NUMBER']);
		$approval = 'Y';
		switch ($result['MSG_TYPE']) {
			Case 'Check':
				$xml->NETWORK_TYPE = $this->getNetworkType();
				$xml->NUMBER_TYPE = $this->getNumberType();
				$soapParams['HEADER']['REQUEST_ID'] = $this->getRequestID();
				break;
			Case 'Request_response':
			Case 'Check_response':
			Case 'Update_response':
			Case 'Cancel_response':
				$xml->REQUEST_TRX_NO = $this->getReqIdByNum($result['NUMBER']);
				$xml->APPROVAL_IND = $approval;
				$xml->REQUEST_RETRY_DATE = $this->getRequest_Retry_Date();
				break;
			Case 'Execute_response':
			Case 'Publish_response':
			Case 'Cancel_Publish_response':
			Case 'Inquire_number_response':
			Case 'Return_response':
				$xml->REQUEST_TRX_NO = $this->getReqIdByNum($result['NUMBER']);
				$xml->APPROVAL_IND = $approval;
				break;
			Case 'DB_synch_response':
				$xml->APPROVAL_IND = $approval;
				break;
			Case 'KD_update_response':
				$xml->REQUEST_TRX_NO = $this->getReqIdByNum($result['NUMBER']);
				$xml->APPROVAL_IND = $approval;
				$xml->KD_UPDATE_TYPE = $this->getRequest_Retry_Date();
				break;
			Case 'Return':
			Case 'Inquire_number':
				$xml->NUMBER = $result['NUMBER'];
				break;
			Case 'DB_synch_request':
				$xml->NUMBER = $this->getRequest_Retry_Date();
				$xml->ACK_DATE = $this->getAck_Date();
				$xml->SYNCH_TYPE = $this->getAck_Date();
				break;
			Case 'Request':
			Case 'Update':
				$xml->PORT_TIME = $this->getPortTime();
				$xml->ACK_CODE = $this->getAck_Code();
				$xml->ACK_DATE = $this->getAck_Date();
				break;
			Case 'KD_update':
				$xml->KD_UPDATE_TYPE = $approval;
				break;
			Case 'Cancel_Publish':
				$xml->DONOR = $this->getDonor();
				break;
			Case 'Publish':
				$xml->DONOR = $this->getDonor();
				$xml->CONNECT_TIME = $this->getConnectTime();
				$xml->PUBLISH_TYPE = $this->getPublishType();
				$xml->DISCONNECT_TIME = $this->getDisconnectTime();
				break;
		}

		$soapParams['HEADER']['TRX_NO'] = $this->makeTrxNum();
		$soapParams['HEADER']['VERSION_NO'] = $this->makeVerNum();
		$soapParams['HEADER']['RETRY_NO'] = $this->makeVerNum();
		$soapParams['HEADER']['RETRY_DATE'] = Application_Model_General::getDateIso();;
		$soapParams['BODY'] = $xml->asXML();
		$NP_MESSAGE['NP_MESSAGE'] = $soapParams;
		print_r($NP_MESSAGE);
		die;
		return $NP_MESSAGE;
	}

	/**
	 * @method getQaDataArray
	 * gets submitted data from form and rearranges it into asimple array 
	 * additionally, it adds missing data for diffrent transactions using 
	 * the switch case.and functions that use hardcoded values .
	 */
	public function getQaDataArray() {
		$result = $this->options;
		if (isset($result['submit'])) {
			unset($result['submit']);
		}
		$headerFieldArray = array('MSG_TYPE' => 'MSG_TYPE',
			'PROCESS_TYPE' => 'PROCESS_TYPE',
			'REQUEST_ID' => 'REQUEST_ID',
			'TRX_NO' => 'TRX_NO',
			'VERSION_NO' => 'VERSION_NO',
			'RETRY_NO' => 'RETRY_NO',
			'RETRY_DATE' => 'RETRY_DATE',
			'FROM' => 'FROM',
			'TO' => 'TO'
		);
		$xml = new SimpleXMLElement("<NETWORK_TYPE></NETWORK_TYPE>");
		$soapParams = array();
		$soapParams['REQUEST_ID'] = $this->getReqIdByNum($result['NUMBER']);
		$approval = 'Y';
		switch ($result['MSG_TYPE']) {
			Case 'Check':
				$result['networkType'] = $this->getNetworkType();
				$result['numberType'] = $this->getNumberType();
				$result['REQUEST_ID'] = $this->getRequestID();
				break;
			Case 'Request_response':
			Case 'Check_response':
			Case 'Update_response':
			Case 'Cancel_response':
				$result['REQUEST_TRX_NO'] = $this->getLastRequestTimeByNumber($result['NUMBER']);
				$result['APPROVAL_IND'] = $approval;
				$result['REQUEST_RETRY_DATE'] = $this->getRequest_Retry_Date();
				break;
			Case 'Execute_response':
			Case 'Publish_response':
			Case 'Cancel_Publish_response':
			Case 'Inquire_number_response':
			Case 'Return_response':
				$result['REQUEST_TRX_NO'] = $this->getLastRequestTimeByNumber($result['NUMBER']);
				$result['APPROVAL_IND'] = $approval;
				break;
			Case 'DB_synch_response':
				$result['APPROVAL_IND'] = $approval;
				break;
			Case 'KD_update_response':
				$result['REQUEST_TRX_NO'] = $this->getLastRequestTimeByNumber($result['NUMBER']);
				$result['APPROVAL_IND'] = $approval;
				$result['KD_UPDATE_TYPE'] = $this->getRequest_Retry_Date();
				break;
			Case 'Return':
			Case 'Inquire_number':
				$result['NUMBER'] = $this->getRequest_Retry_Date();
				break;
			Case 'DB_synch_request':
				$result['NUMBER'] = $this->getRequest_Retry_Date();
				$result['ACK_DATE'] = $this->getAck_Date();
				$result['SYNCH_TYPE'] = $this->getAck_Date();
				break;
			Case 'Request':
			Case 'Update':
				$result['PORT_TIME'] = $this->getPortTime();
				$result['ACK_CODE'] = $this->getAck_Code();
				$result['ACK_DATE'] = $this->getAck_Date();
				break;
			Case 'KD_update':
				$result['KD_UPDATE_TYPE'] = $approval;
				break;
			Case 'Cancel_Publish':
				$result['DONOR'] = $this->getDonor();
				break;
			Case 'Publish':
				$result['DONOR'] = $this->getDonor();
				$result['CONNECT_TIME'] = $this->getConnectTime();
				$result['PUBLISH_TYPE'] = $this->getPublishType();
				$result['DISCONNECT_TIME'] = $this->getDisconnectTime();
				break;
		}
		foreach ($result as $key => $val) {
			$soapParams[$key] = $result[$key];
		}

		return $soapParams;
	}

	/**
	 * @method getRequestID
	 * generates a request_id for new transaction (whenever check is sent)
	 */
	public function getRequestID() { // generates request id for test
		$idparts = array();
		$idparts['protocol'] = 'NP';
		$idparts['provider'] = $this->options['FROM'];
		$idparts['recipient'] = $this->options['TO'];
		$id = implode('', $idparts);
		$numberOfRequestsMade = $this->getNumOfRequests();
		$requestQuantity = $this->getRequestQuantity();
		$rand = '';
		$id .= date("ymd") . $numberOfRequestsMade . $requestQuantity; //date("ymd")*/
		return $id;
	}

	/**
	 * @method getNetworkType()
	 * returns network type when its missing , always returns M 
	 * (which stands for Mobile number)
	 */
	public function getNetworkType() {
		$netType = 'M';
		return $netType;
	}

	/**
	 * @method getNumberType()
	 * returns Number type when its missing , always returns I 
	 * (which stands for single number)
	 */
	public function getNumberType() {
		$numType = 'I';
		return $numType;
	}

	/**
	 * @method makeTrxNum()
	 * returns TRX_NO when its missing . 
	 * generates random 12 digit number 
	 * returns TRX_NO
	 */
	protected function makeTrxNum() { // returns hardcoded trx num for request id
		$trx_no = $this->options['FROM'];
		$number = '';
		for ($counter = 0; $counter < 12; $counter++) {
			$number .= mt_rand(0, 9);
		}
		$trx_no .= $number;
		return $trx_no;
	}

	/**
	 * @method makeVerNum()
	 * returns VERSION_NO when its missing .
	 * always returns "123"
	 */
	protected function makeVerNum() { // returns hardcoded Ver_no for request id
		$ver = Application_Model_General::getSettings("VersionNo");
		return $ver;
	}

	/**
	 * @method sendInternalSoap()
	 * functio for sending soap messages to internal.
	 * gets baseurl from config.ini
	 * uses the wsdl for soap transactions (gets it from ini using $this->getwsdl)
	 * uses Zend_Soap_Client for sending the soap message . Zend_Soap_Client
	 * gets the wsdl and an options array. in the array are the soap version used.
	 * the location of the soap server handle.
	 * and the classmap that tells soap where to get the code for the function
	 * soap wants to call. the return values are also defined in the wsdl.
	 * in our case an std object with ack date and code.
	 */
	public function sendInternalSoap() {

		$url = $this->getRecipientUrl();
		$baseUrl = Application_Model_General::getBaseUrl();
		$client = new Zend_Soap_Client(Application_Model_General::getWsdl(), array(
					'location' => $baseUrl . '/provider',
					'soap_version' => SOAP_1_1,
					'classmap' => array('sendMessage' => "Np_Soap_Handler")
				));

		$thesoaparray = $this->getQaSoap();
		$ret = $client->sendMessage($thesoaparray);
		return $ret;
	}

	/**
	 * @method sendProviderData()
	 * MAKES Array FOR Request FROM INTERNAL and send via POST http request 
	 */
	public function sendProviderData() {
		$toGET = $this->getQaDataArray();

//		$client = new Zend_Http_Client();
//		$client->setUri('/internal');
		
		
		$client = new Zend_Http_Client();
		$client->setUri("http://localhost:18112/np/Internal");
		$client->setParameterPost($toGET);
		$client->setMethod(Zend_Http_Client::POST);
		$response = $client->request();
		return $response->getBody();
	}

	/**
	 * @method getNumOfRequests()
	 * if the value is missing , returns hardcoded "00001" which represents the number
	 * of phone numbers in a range of requests.
	 * (usually really is 1).
	 */
	public function getNumOfRequests() {
		return '00001';
	}

	/**
	 * @method getRequestQuantity()
	 * if the value is missing , returns a random 4 digit number 
	 * returns RequestQuantity, for now default value
	 */
	public function getRequestQuantity() {
		$rand = rand(1, 9999);
		$hefresh = strlen($rand);
		if ($hefresh < 4) {
			$sum_total = 4 - $hefresh;
			$rand = ($sum_total * $hefresh) . $rand;
		}
		return $rand;
	}

	/**
	 * @method getIdValue()
	 * if the value is missing , returns a random 4 digit number 
	 * returns IdValue, for now default value
	 */
	public function getIdValue() {
		return '0001';
	}

	/**
	 * @method getIdValue2nd()
	 * if the value is missing , returns a hardcoded '0001' value.
	 * returns IdValue2nd, for now default value
	 */
	public function getIdValue2nd() {
		return '0001';
	}

	/**
	 * @method getIdValue3rd()
	 * if the value is missing , returns a hardcoded '0001' value.
	 * returns IdValue3rd, for now default value
	 */
	public function getIdValue3rd() {
		return '0001';
	}

	/**
	 * @method getRecipientUrl()
	 * returns the url of the transaction recipient for http request
	 */
	protected function getRecipientUrl() {
		$providers = Application_Model_General::getSettings('provider');
		$key = $this->options['TO'];
		return $providers[$key];
	}

	/**
	 * @method getReqIdByNum()
	 * if reqid missing.
	 * returns the request id of the desired number from the requests db.
	 */
	protected function getReqIdByNum($number) {
		$table = new Application_Model_DbTable_Requests();
		$select = $table->select()->where('number = ?', $number)->order('id DESC')->limit(1, 0);
		$row = $table->fetchRow($select);
		if (!isset($row['request_id'])) {
			$row['request_id'] = $this->getRequestID();
			return $row['request_id'];
		}
		return $row['request_id'];
	}

	/**
	 * @method	- getLastRequestTimeByNumber()
	 * @param	- recieves number which is subject of the transaction.		
	 * returns  - the date and time of the last transaction made for the number 
	 * that was provided.
	 */
	public function getLastRequestTimeByNumber($number) {
		$table = new Application_Model_DbTable_Requests();
		$select = $table->select()->where('number = ?', $number);
		$row = $table->fetchRow($select);
		return $row['last_requests_time'];
	}

	/**
	 * @method	- getPortTime()
	 * generates porttime with data from form and in timestamp format.
	 */
	public function getPortTime() {

		$date = new Zend_Date();
		$date->addMinute((int) $this->options['porttime']);
		return $date->getIso();
	}

	/**
	 * @method	getAck_Code()
	 * if value is missing returns hardcoded "ACK00"
	 */
	public function getAck_Code() {
		return 'ACK00';
	}

	/**
	 * @method	getAck_Code()
	 * if value is missing returns hardcoded ACKDATE in time stamp format
	 */
	public function getAck_Date() {
		return Application_Model_General::getDateIso();
	}

	/**
	 * @method	getRequest_Retry_Date()
	 * if value is missing returns the datetime for the last request 
	 * transaction made
	 */
	public function getRequest_Retry_Date() {
		$table = new Application_Model_DbTable_Transactions();
		$select = $table->select()->where('request_id = "' . $this->getReqIdByNum($this->options['NUMBER']) . '" AND message_type = "Request"');
		$row = $table->fetchRow($select);
		return $row['last_transactions_time'];
	}

	/**
	 * @method	getDonor()
	 * if value is missing returns the abbrevation for the provider from whom
	 * the number is being transferred from (the abandoned provider)
	 */
	public function getDonor() {
		return $this->options['to'];
	}

	/**
	 * @method	getDisconnectTime()
	 * if value is missing returns NOW() in timestamp format
	 */
	public function getDisconnectTime() {
		return Application_Model_General::getDateIso();
	}

	/**
	 * @method	getConnectTime()
	 * if value is missing returns NOW() in timestamp format
	 */
	public function getConnectTime() {
		return Application_Model_General::getDateIso();
	}

	/**
	 * @method	getPublishType()
	 * if value is missing returns PublishType which matches the 
	 * current process type
	 */
	public function getPublishType() {
		switch ($this->options['PROCESS_TYPE']) {
			case 'PORT':
				return 'Port';
				break;
			case 'RETURN':
				return 'Rtrn';
				break;
			case 'QUERY':
			case 'MAINT':
				return 'FALSE';
				break;
		}
	}

}
