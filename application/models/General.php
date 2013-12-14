<?php

/**
 * General Model
 * Model for various Site-wide operations.
 * 
 * @package ApplicationModel
 * @subpackage GeneralModel
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * General Object
 * 
 * @package ApplicationModel
 * @subpackage GeneralModel
 */
class Application_Model_General {

	private static $processType = array(
		"PORT" => array('Check', 'Check_response', 'Request', 'Request_response', 'Update', 'Update_response',
			'Cancel', 'Cancel_response', 'Execute', 'Execute_response', 'KD_update', 'KD_update_response',
			'Publish', 'Publish_response', 'Cancel_publish', 'Cancel_publish_response'),
		"RETURN" => array("Return", "Return_response"),
		"QUERY" => array("Inquire_number"),
		"MAINT" => array("Down_system", "Up_system"),
	);
	static public $inProcess = Array("Request", "Request_response",
		"Update", "Update_response",
		"KD_update", "KD_update_response",
		"Execute", "Execute_response",
		"Publish",
	);
	
	// move to view
	static public $menu = "<div>MENU -> <a href='/np/debug/logger'>Logger</a> / <a href='/np/reports'>Reports</a> / <a href='/np/emailsettings'>Email Settings</a></div><br/>";

	/**
	 * 
	 * @var string $baseurl 
	 */
	private static $baseUrl;

	/**
	 * 
	 * @var string $wsdl the wsdl adress
	 */
	private static $wsdl;

	/**
	 *
	 * @return string $baseUrl 
	 */
	public static function getBaseUrl() {
		if (!self::$baseUrl) {
			$uri = new Zend_Controller_Request_Http();
			self::$baseUrl = $uri->getScheme() . '://' . $uri->getHttpHost() . $uri->getBaseUrl();
		}
		return self::$baseUrl;
	}

	/**
	 * gets some data from ini file
	 * 
	 * @return string 
	 */
	public static function getSettings($key) {
		static $settings;
		if (!is_array($settings)) {
			$settings = array();
		}
		if (!array_key_exists($key, $settings)) {
			$settings[$key] = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption($key);
		}
		return $settings[$key];
	}

	/**
	 * gets params from GET
	 * 
	 * @return array
	 */
	public static function getParamsArray($params) {

		$result = array();

		foreach ($params as $key => $value) {
			switch ($key) {
				case 'controller':
				case 'action':
				case 'module':
					//unset
					break;
				case 'source':
					//?? $result['TO'] = $value;
					break;
				case 'provider':
					$result['TO'] = $value;
					break;
				case 'transfer_time':
					$result['PORT_TIME'] = $value;
					break;
				default:
					$result[strtoupper($key)] = $value;
					break;
			}
		}
		return $result;
	}

	/**
	 * getProcessType
	 * 
	 * @return mixed process type string or null
	 */
	public static function getProcessType($msg_type) {

		foreach (self::$processType as $type => $arr) {
			if (in_array($msg_type, $arr)) {
				return $type;
			}
		}
		return null;
	}

	/**
	 * updates ack code after soap is sent.
	 */
	public static function updateSoapAckCode($ackCode, $requestId, $lastTransaction, $trx_no = null) {
		if (empty($ackCode)) {
			return FALSE;
		}
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::master());
		$update_arr = array('ack_code' => $ackCode);
		$where_arr = array(
			'request_id =?' => $requestId,
			'message_type=?' => $lastTransaction,
		);
		if (!empty($trx_no)) {
			$where_arr['trx_no'] = $trx_no;
		}
		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	/**
	 * writes data to Logs table
	 * 
	 * @param array $request
	 * @return Bool DB Response 
	 */
	public static function writeToLog($request) {
		try {
			$row = array();
			$row['process_type'] = isset($request['PROCESS_TYPE']) ? $request['PROCESS_TYPE'] : NULL;
			$row['msg_type'] = isset($request['MSG_TYPE']) ? $request['MSG_TYPE'] : NULL;
			$row['number'] = isset($request['NUMBER']) ? $request['NUMBER'] : NULL;
			$row['from'] = isset($request['FROM']) ? $request['FROM'] : NULL;
			$row['to'] = isset($request['TO']) ? $request['TO'] : NULL;
			if (isset($request['FORK'])) {
				$row['additional'] = "FORK";
			}

			$tbl = new Application_Model_DbTable_Logs(Np_Db::master());
			$res = $tbl->insert($row);
		} catch (exception $e) {
			error_log('Caught Exception	' . $e->getMessage() . "\n");
			$res = FALSE;
		}
		return $res;
	}

	/**
	 * Update Requests Table according to Request ID and last Transaction
	 * 
	 * @param string $reqID
	 * @param string $lastT
	 * @return int Rows Affected
	 */
	public static function updateRequests($reqID, $lastT, $status = 1) {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$update_arr = array('status' => $status);
		$where_arr = array(
			'request_id =?' => $reqID,
			'last_transaction=?' => $lastT,
		);
		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	/**
	 *
	 * @param type $reqID the request id 
	 * @param type $requestedTransferTime the requested port time
	 * @param type $msgType the transaction message type
	 * @return type Number of rows affected
	 */
	public static function updateTransactions($reqID, $requestedTransferTime, $msgType) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::master());
		$update_arr = array('requested_transfer_time' => Application_Model_General::getTimeInSqlFormat($requestedTransferTime));
		$where_arr = array(
			'request_id =?' => $reqID,
			'message_type =?' => $msgType
		);
		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	/**
	 * 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getTimeInSqlFormat($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yy-MM-dd HH:mm:ss');
	}

	/**
	 * 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getTimeInSqlFormatFlip($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yy-MM-dd HH:mm:ss');
	}

	/**
	 * 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getTimeInSqlFormatAgg($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yy-MM-dd HH:mm:ss');
	}

	/**
	 * dd-mm 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getTimeInSqlFormatForAgg($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yyyy-dd-MM HH:mm:ss');
	}

	public static function getRealTimeInSqlFormatForAgg($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yyyy-MM-dd');
	}

	/**
	 * 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getDateTimeInSqlFormat($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->toString('yyyy-MM-ddTHH:mm:ss');
	}

	/**
	 * 
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getTimeStampInSqlFormat($strTime) {
		$time = new Zend_Date($strTime, null, new Zend_Locale('he_IL'));
		return $time->getTimestamp();
	}

	/**
	 * 
	 * @return string WSDL URL 
	 */
	public static function getWsdl() {

		if (!self::$wsdl) {
			$position = Application_Model_General::getSettings('NpWsdlPosition');
			if ($position === "relative") {
				self::$wsdl = Application_Model_General::getBaseUrl() .
						Application_Model_General::getSettings('NpWsdl');
			} else {
				return Application_Model_General::getSettings('NpWsdl');
			}
		}
		return self::$wsdl;
	}

	/**
	 * method to retreive date in ISO-8601 format
	 * if date is not set retreive the current date
	 * 
	 * @param Zend_Date Zend date object
	 * 
	 * @return string Date in ISO-8601 format
	 */
	public static function getDateIso($date = null) {
		$date = new Zend_Date($date, null, new Zend_Locale('he_IL'));
		$iso = $date->getIso();
//		$brokenIso = explode("+", $iso);
//		$brokenIso[0] = $brokenIso[0] . ".00";
//		$iso = implode("+", $brokenIso);
		return $iso;
	}

	/**
	 * method to fork process of PHP
	 * 
	 * @param String $url the url to open
	 * @param Array $params data sending to the new process
	 * 
	 * @return Boolean true on success else FALSE
	 */
	public static function forkProcess($url, $params) {
		$params['fork'] = 1;
		$forkUrl = self::getForkUrl();
		$querystring = http_build_query($params);
		$cmd = "wget -O /dev/null '" . $forkUrl . $url . "?" . $querystring .
				"' > /dev/null & ";
		if (system($cmd) === FALSE) {
			error_log("Can't fork PHP process");
			return false;
		}
		usleep(500000);
		return true;
	}

	/**
	 * method to retreive specific timer
	 * 
	 * @staticvar Array $timers timers array
	 * @param String $timer timer to find
	 * @param String $subitem if timer divided to item types, can supply the item
	 * @return Int the timer requested. If error occurred return false
	 */
	public static function getTimer($timer) {
		$timers = self::getSettings("timers");
		if (!is_array($timers) || !count($timers)) {
			error_log("Can't load timers");
			return FALSE;
		} else if (!isset($timers[$timer])) {
			error_log("Can't found timer: " . $timer);
			return FALSE;
		}
		$ret = $timers[$timer];
		if (is_array($ret)) {
			$networkType = self::getSettings("NetworkType");
			if (!isset($ret[$networkType])) {
				error_log("Can't find timer for the currect network type");
				return FALSE;
			}
			return $ret[$networkType];
		}
		return $ret;
	}

	/**
	 * writes to Timers_Activity table 
	 * 
	 * @param array $request
	 * @return Bool DB Response 
	 */
	public static function writeToTimersActivity($request, $timer) {
		try {
			$row = array();
			$row['request_id'] = isset($request['REQUEST_ID']) ? $request['REQUEST_ID'] : NULL;
			$row['timer'] = isset($timer) ? $timer : NULL;
			$row['transaction_time'] = isset($request['RETRY_DATE']) ? $request['RETRY_DATE'] : NULL;
			$row['network_type'] = isset($request['PROCESS_TYPE']) ? $request['PROCESS_TYPE'] : NULL;
			$tbl = new Application_Model_DbTable_ActivityTimers(Np_Db::master());
			$res = $tbl->insert($row);
			return $res;
		} catch (exception $e) {
			error_log('Caught Exception	' . $e->getMessage() . "\n");
			$res = FALSE;
		}
	}

	/**
	 * method to update transactions table after ack or reject reason code retreive
	 * 
	 * @return bool true if success to update else false 
	 */
	public static function updateTransactionsAck($trx_no, $ack, $reject_reason_code = null) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::master());
		$update_arr = array();
		if (!empty($ack)) {
			$update_arr['ack_code'] = $ack;
		}
		if (!empty($reject_reason_code)) {
			$update_arr['reject_reason_code'] = $reject_reason_code;
		}
		// if there is data to update
		if (count($update_arr)) {
			$where_arr = array('trx_no =?' => $trx_no/* $this->request->getHeaderField("TRX_NO") */);
			$ret = $tbl->update($update_arr, $where_arr);
			return $ret;
		}
		return FALSE;
	}

	/**
	 * method to retrieve field from Requests table with filtering of specific value
	 * if number is filtering retreive the last request of this number
	 * 
	 * @param Mixed $field the field of Request to request (can be multiple fields if is array)
	 * @param Mixed $value the value to filter by (number of string)
	 * @return String the value of the field if success, else null
	 */
	static public function getFieldFromRequests($fields, $value, $filterField = null) {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());

		if (is_null($filterField)) {
			$filterField = (($fields !== 'number') ? 'number' : 'request_id');
		}
		$select = $tbl->select();
		$select->where($filterField . ' = ?', $value)->order('id DESC')->limit(1);
		$result = $select->query()->fetchObject();   //take the last one
		if ($result) {
			if (!is_array($fields)) {
				return $result->$fields;
			} else {
				foreach ($result as $key => $value) {
					if (!in_array($key, $fields)) {
						unset($result->$key);
					}
				}
				settype($result, 'array');
				return $result;
			}
		}
		return null;
	}

	static public function getRouteTimeByReqId($requestID) {

		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());

		$select = $tbl->select();

		$select->where('request_id =?', $requestID)->order('id DESC');

		$result = $select->query()->fetchObject();   //take the last one

		if ($result) {

			return $result->connect_time;
		}

		return FALSE;
	}

	static public function getTrxPortTime($trxno) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		$select->where('trx_no = ?', $trxno)
				->order('id DESC');
		$result = $select->query()->fetchObject();   //take the last one
		if ($result) {
			return $result->requested_transfer_time;
		}
		return null;
	}

	static public function getLastPortTimeByReqId($reqId) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		$select->where('request_id = ?', $reqId)
				->where('message_type = ?', "Request")
				->order('id DESC');
		$result = $select->query()->fetchObject();   //take the last one
		if ($result) {
			return $result->requested_transfer_time;
		}
		return null;
	}

	/**
	 * checks if request is auto request  
	 * 
	 * @param type $reqId
	 * @param type $transcation
	 * @return type 
	 */
	static public function isAutoCheck($reqId, $transcation = "Check") {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->where('request_id=?', $reqId)->order('id DESC');
		$result = $select->query()->fetchObject();
		if ($result && $result->auto_check) {
			$db = Np_Db::slave();
			$select = $db->select()->from("Transactions")
					->where('request_id=?', $reqId)
					->where('message_type=?', $transcation)
					->where('requested_transfer_time IS NOT NULL')
					->order('id DESC');
			$result = $select->query()->fetchObject();
			if ($result !== FALSE && isset($result->requested_transfer_time)) {
				$datetime = new Zend_Date($result->requested_transfer_time, 'yyyy-MM-dd HH:mm:ss', new Zend_Locale('he_IL'));
				return $datetime->getTimestamp();
			} else {
				return TRUE;
			}
		}
		return false;
	}

	/**
	 * method to receive donor code by requst id
	 * 
	 * @param string $requestID
	 * @return string the donor
	 */
	static public function getDonorByReqId($requestID) {
		$result = substr($requestID, 4, 2);
		return $result;
	}

	/**
	 * get ForkUrl Setting from application ini
	 * 
	 * @return type string or NULL
	 */
	static public function getForkUrl() {

		$forkSetting = Application_Model_General::getSettings('ForkUrl');
		if (!empty($forkSetting)) {
			$forkUrl = $forkSetting;
		} else {
			$forkUrl = self::getBaseUrl();
		}
		return $forkUrl;
	}

	static public function previousCheck($number) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()
				->where('number=?', $number)
				->where('status=?', "1")
				->order('id DESC');
		$result = $select->query()->fetch();
		if (!empty($result) && $result !== FALSE) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param type $request_id 
	 */
	static public function checkIfRetry($request_id, $msg_type) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select()
				->where('request_id=?', $request_id)
				->where('message_type=?', $msg_type);
		$result = $select->query()->fetchAll();

		if (isset($result)) {
			foreach ($result as $row => $value) {

//					$trycount = $trycount + 1;


				$trycount = count($result);


				return $trycount;
			}
		} else {
			return FALSE;
		}
	}

	static public function checkIfPreviousTry($requestId, $msgtype) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select()
				->where('request_id=?', $requestId)
				->where('message_type=?', $msgtype)
				->order('id DESC');
		$result = $select->query()->fetch();
		$Retrynum = 1;
		if (!empty($result)) {
			$Retrynum = 0;
			foreach ($result as $rows) {
				$Retrynum++;
			}
		} else {
			$Retrynum = 1;
		}
		return $Retrynum;
	}

	static public function isMsgResponse($msgtype) {

		$msg_type = explode('_', strtoupper($msgtype));
		if ((isset($msg_type[1]) && strtoupper($msg_type[1]) == "RESPONSE") || (isset($msg_type[2]) && strtoupper($msg_type[2]) == "RESPONSE")) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	static public function saveShutDownDetails($provider, $type = "DOWN") {

		try {
			$row = array();
			$row['provider'] = $provider; //the provider
			$row['type'] = $type; //up or down
			if (strtoupper($type) == "UP") {
				$duration = Application_Model_General::getShutDownDuration();
			} else {
				$duration = NULL;
			}
			$row['duration'] = $duration; //duration if up
			$tbl = new Application_Model_DbTable_Shutdown(Np_Db::master());
			$res = $tbl->insert($row);
			return $res;
		} catch (exception $e) {
			$res = FALSE;
		}
	}

	static public function getShutDownDuration() {
		$tbl = new Application_Model_DbTable_Shutdown(Np_Db::slave());
		$select = $tbl->select();
		$select->where('type = ?', "DOWN")->order('date DESC');
		$result = $select->query()->fetchObject();   //take the last one
		if ($result) {
			$old_date = Application_Model_General::getTimeStampInSqlFormat($result->date);
			$calculate_duration = time() - $old_date;
			return $calculate_duration;
		}
		return null;
	}

	static public function getShutDowns() {
		$tbl = new Application_Model_DbTable_Shutdown(Np_Db::slave());
		$select = $tbl->select();
		$result = $select->query()->fetchAll();   //take the last one
		if ($result) {

			return $result;
		}
		return null;
	}

	public static function getAvailableAggDates() {

		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::slave());

		$select =
						$tbl->select()->from(array('ap' => 'Activity_Process'), array('agg_date'))
						->distinct()->order('agg_date DESC');

//		$select->where('request_id =?', $requestID)->order('id DESC');

		$results = $select->query()->fetchAll();   //take the last one

		foreach ($results as $result => $val) {
			$date_array[$val['agg_date']] = $val['agg_date'];
		}
		if (isset($date_array) && $date_array) {

			return $date_array;
		}

		return array();
	}

	public static function getAllAvailableStatisticsTimes() {
		$timers_array = Application_Model_General::getAvailableAggDatesTimers();
		$processes_array = Application_Model_General::getAvailableAggDates();
		$sorted_array = array_merge($timers_array, $processes_array);
		return $sorted_array;
	}

	public static function getAvailableAggDatesTimers() {

		$tbl = new Application_Model_DbTable_ActivityTimers(Np_Db::slave());

		$select =
						$tbl->select()->from(array('at' => 'Activity_Timers'), array('transaction_time'))
						->distinct()->order('transaction_time DESC');

//		$select->where('request_id =?', $requestID)->order('id DESC');

		$results = $select->query()->fetchAll();   //take the last one

		foreach ($results as $result => $val) {

			$date_array[$val['transaction_time']] = $val['transaction_time'];
		}
		if (isset($date_array) && $date_array) {

			return $date_array;
		}

		return array();
	}

	public static function getProviderArray() {
		$providers = array_keys(Application_Model_General::getSettings('provider'));
		$InternalProvider = Application_Model_General::getSettings('InternalProvider');
		if (!in_array($InternalProvider, $providers)) {
			$providers[] = $InternalProvider;
		}
		return $providers;
	}

	public static function getRequestDirPath($request_id, $force_create = false) {
		$ar = str_split($request_id, 2);
		if ($ar && count($ar) > 2) {
			$relative_path = preg_replace("/[^0-9a-zA-Z]/i", '', $ar[1]) . '/' . preg_replace("/[^0-9a-zA-Z]/i", '', $ar[2]) . '/';
		} else {
			$relative_path = './';
		}
		// TODO oc666: move this to config
		$base_path = APPLICATION_PATH . '/../logs/';
		$path =  $base_path . $relative_path;
		if ($force_create && !file_exists($path)) {
			if (!@mkdir($path, 0777, true)) {
				$path = $base_path;
			}
		}
		return $path;
	}
	
	public static function getRequestFilePath($request_id, $force_create = false) {
		$file_path = self::getRequestDirPath($request_id, $force_create) . $request_id . ".log";
		if ($force_create && !file_exists($file_path)) {
			touch($file_path);
		}
		return $file_path;
	}

	/**
	 * method to log soap requests
	 */
	public static function logRequest($content, $request_id) {
		try {
			$logFilePath = self::getRequestFilePath($request_id, true);
			
			if (!file_exists($logFilePath)) {
				mkdir($logFilePath);
			}
			file_put_contents($logFilePath, $content, FILE_APPEND);
		} catch (Exception $e) {
			error_log("logRequest::Can't log request");
		}
	}

	public static function logRequestResponse($request, $response, $request_id, $prefix = '') {
		$InternalProvider = Application_Model_General::getSettings('InternalProvider');
		self::logRequest($prefix . $InternalProvider . " Request: " . print_R($request, 1) . PHP_EOL, $request_id);
		self::logRequest($prefix . $InternalProvider . " Receive Response: " . print_R($response, 1) . PHP_EOL, $request_id);
	}
	/**
	 * search transaction by request id and stage ()
	 * @param string $request_id the id of the request
	 * @param string $stage check, request, execute, etc
	 * @param string $reject_reason_code
	 */
	public static function getTransactions($request_id, $stage = null, $reject_reason_code = null) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());

		$select = $tbl->select();
		$select->where("request_id = ? ", (string) $request_id);
		if ($stage) {
			$select->where("message_type = ?", (string) $stage);
		}
		if ($reject_reason_code === 'null') {
			$select->where("reject_reason_code IS NULL OR reject_reason_code =''");
		} else if (!empty($reject_reason_code)) {
			$select->where("reject_reason_code = ?", (string) $reject_reason_code);
		}

		$results = $select->order('id DESC')->limit(1000)->query()->fetchAll();
		return $results;
	}

	/**
	 * Get Provider RPC URL via "TO" field
	 * 
	 * @return string URL 
	 */
//	public static function getProviderEmail($provider) {
//		$providers = Application_Model_General::getSettings('email');
//		$key = $provider;
//		if (isset($providers[$key])) {
//			return $providers[$key];
//		}
//		return FALSE;
//	}

	public static function getAllProviderEmails() {
		$tbl = new Application_Model_DbTable_EmailSettings(Np_Db::slave());
		$select = $tbl->select();

		$result = $select->query()->fetchAll();   //take the last one
//		var_dump($result);
//		die;
		if ($result) {

			return $result;
		}

		return array();
	}
	

//	public static function setProviderEmail($provider) {
//		$providers = Application_Model_General::getSettings('email');
//		$key = $provider;
//		if (isset($providers[$key])) {
//			return $providers[$key];
//		}
//		return FALSE;
//	}
	public static function setProviderEmail($provider=FALSE, $email=FALSE) {
//		var_dump($email);
//		die;
		$providers = Application_Model_General::getAllProviderEmails();
		
		foreach ($providers as $providerz => $vals) {
			$providers[] = $providers[$providerz]['providername'];
		}
		if ($providers != FALSE) {
			$provider_names = $providers;
		} else {
			$provider_names = array('asd' => 'asd');
		}
//		var_dump($provider);
//		die;
		if (!in_array($provider, $provider_names)) {
//			var_dump($provider);
//			die('ASDASDASD');

			$row['providername'] = $provider;
			$row['provideremailadress'] = $email;
			$tbl = new Application_Model_DbTable_EmailSettings(Np_Db::master());
//			$where[]="Where";
			$res = $tbl->insert($row);
		} else {
//			var_dump($provider);
//			die;
			$tbl = new Application_Model_DbTable_EmailSettings(Np_Db::master());
			$update_arr = array("provideremailadress" => $email);
			$where_arr = array(
				"providername = ?" => $provider
			);

			$res = $tbl->update($update_arr, $where_arr);
		}

		return $res;
	}

	Public static function checkIfEmailFormatEmpty() {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select();
		$result = $select->query()->fetchAll();   //take the last one

		if (count($result) >= 1) {

			return FALSE;
		}

		return TRUE;
	}

	public static function setEmailFormat($email_format) {
		$is_db_empty_check = Application_Model_General::checkIfEmailFormatEmpty();


		if ($is_db_empty_check == TRUE) {
			$row['email_format'] = $email_format;
//			$row['provideremailadress'] = $email;
			$tbl = new Application_Model_DbTable_EmailFormat(Np_Db::master());
			$res = $tbl->insert($row);
		} else {
			$tbl = new Application_Model_DbTable_EmailFormat(Np_Db::master());
			$update_arr = array('email_format' => $email_format);
			$res = $tbl->update($update_arr, FALSE);
			return $res;
		}

		return $res;
	}

	public static function getEmailFormat() {
		$tbl = new Application_Model_DbTable_EmailFormat(Np_Db::slave());
		$select = $tbl->select();
		$result = $select->query()->fetch();   //take the last one

		if ($result) {

			return $result['email_format'];
		}

		return FALSE;
	}

	public static function getProviderEmail($provider) {
		
		$tbl = new Application_Model_DbTable_EmailSettings(Np_Db::slave());
		$select = $tbl->select();
		$select->where("providername = ?", $provider);
		
		$result = $select->query()->fetch();   //take the last one
		
		if ($result) {

			return $result['provideremailadress'];
		}

//		var_dump($select->__toString());
		die("<h2>No Email Defined For Provider -> ".$provider."</h2><br><a href='/np/emailsettings'>Set One!</a>");
		return FALSE;
	}

	public static function getEmailFormatWithPlaceHolders($provider, $request_id) {

		$tbl = new Application_Model_DbTable_EmailFormat(Np_Db::slave());
		$select = $tbl->select();
		$result = $select->query()->fetch();   //take the last one

		if ($result) {
			$result['email_format'] = str_replace("*Provider_Name*", $provider, $result['email_format']);
			$result['email_format'] = str_replace("*Request_ID*", $request_id, $result['email_format']);
		}

		return $result['email_format'];
	}

	public static function modifySentRow($trx_no) {
//		var_dump($request_id);
//		die;
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::master());
		$update_arr = array('message_type' => "Message Sent");
		$where_arr = array('trx_no =?' => $trx_no);
		$res = $tbl->update($update_arr, FALSE);
		return $res;
	}

}
