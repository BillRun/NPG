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
	static protected $locales = array();

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
	 * get the base url
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
	public static function getSettings($key, $default = null) {
		static $settings;
		if (!is_array($settings)) {
			$settings = array();
		}
		if (!array_key_exists($key, $settings)) {
			$settings[$key] = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption($key);
		}
		if (is_null($settings[$key]) && !is_null($default)) {
			return $default;
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
			'request_id = ?' => $requestId,
			'message_type = ?' => $lastTransaction,
		);
		if (!empty($trx_no)) {
			$where_arr['trx_no = ?'] = $trx_no;
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
			$row['phone_number'] = isset($request['PHONE_NUMBER']) ? $request['PHONE_NUMBER'] : NULL;
			$row['from_provider'] = isset($request['FROM_PROVIDER']) ? $request['FROM_PROVIDER'] : (isset($request['FROM']) ? $request['FROM'] : NULL);
			$row['to_provider'] = isset($request['TO_PROVIDER']) ? $request['TO_PROVIDER'] : (isset($request['TO']) ? $request['TO'] : NULL);
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
		$update_arr = array('requested_transfer_time' => Application_Model_General::getDateTimeInSqlFormat($requestedTransferTime));
		$where_arr = array(
			'request_id =?' => $reqID,
			'message_type =?' => $msgType
		);
		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	/**
	 * get sql format of date time
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getDateTimeInSqlFormat($strTime = null) {
		$time = new Zend_Date($strTime, null, Application_Model_General::getLocale(null, true));
		return $time->toString('yyyy-MM-ddTHH:mm:ss');
	}

	/**
	 * get sql format of date time
	 * 
	 * @param type $strTime
	 * @return string Date in SQL Format 
	 */
	public static function getDateTimeInTimeStamp($strTime) {
		$time = new Zend_Date($strTime, null, Application_Model_General::getLocale(null, true));
		return $time->getTimestamp();
	}

	/**
	 * get the NPG wsdl
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
	public static function getDateTimeIso($date = null) {
		$date = new Zend_Date($date, null, Application_Model_General::getLocale(null, true));
		return $date->getIso();
	}

	/**
	 * method to fork process of PHP
	 * 
	 * @param String $url the url to open
	 * @param Array $params data sending to the new process
	 * @params Boolean $post use POST to query string else use GET
	 * 
	 * @return Boolean true on success else FALSE
	 */
	public static function forkProcess($url, $params, $post = false, $sleep = 0) {
		$params['fork'] = 1;
		if ($sleep) {
			$params['SLEEP'] = (int) $sleep;
		}
		$forkUrl = self::getForkUrl();
		$querystring = http_build_query($params);
		if (!$post) {
			$cmd = "wget -O /dev/null '" . $forkUrl . $url . "?" . $querystring .
				"' > /dev/null & ";
		} else {
			$cmd = "wget -O /dev/null '" . $forkUrl . $url . "' --post-data '" . $querystring .
				"' > /dev/null & ";
		}

//		echo $cmd . "<br />" . PHP_EOL;
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
			if (isset($request['RETRY_DATE'])) {
//				$row['transaction_time'] = self::getDateTimeInSqlFormat($request['RETRY_DATE']);
			}

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
			$filterField = (($fields !== 'phone_number') ? 'phone_number' : 'request_id');
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

	/**
	 * get transaction port time
	 * 
	 * @param string $trxno transaction id
	 * 
	 * @return transfer time (unix timestamp) if available, else null
	 */
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

	/**
	 * get the last port time of the request
	 * 
	 * @param string $reqId
	 * 
	 * @return transfer time (unix timestamp) if available, else null
	 */
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
	 * @param string $reqId the request id
	 * @param string $transcation the transaction that happened before (default: Check)
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
				$datetime = new Zend_Date($result->requested_transfer_time, null, Application_Model_General::getLocale(null, true));
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
		return Application_Model_General::getSettings('ForkUrl', self::getBaseUrl());
	}

	/**
	 * check if we sent check already
	 * 
	 * @param long $phone_number phone number to check
	 * 
	 * @return true if we sent already check
	 */
	static public function previousCheck($phone_number) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()
			->where('phone_number=?', $phone_number)
			->where('status=?', "1")
			->order('id DESC');
		$result = $select->query()->fetch();
		if (!empty($result) && $result !== FALSE) {
			return false;
		}
		return true;
	}

	/**
	 * check if we try this message before
	 * 
	 * @param string $requestId the request id to check
	 * @param string $msgtype which message to search for previous check
	 * 
	 * @return int the phone number of retries we try to send this message
	 */
	static public function checkIfRetry($request_id, $msg_type) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select()
			->where('request_id=?', $request_id)
			->where('message_type=?', $msg_type);
		$result = $select->query()->fetchAll();

		if (isset($result)) {
			foreach ($result as $row => $value) {
				$trycount = count($result);
				return $trycount;
			}
		} else {
			return 0;
		}
	}

	/**
	 * check if message type is response
	 * 
	 * @param string $msgType the message type to check
	 * @return boolean true if the message type is response else false
	 */
	static public function isMsgResponse($msgType) {

		$msgTypeParts = explode('_', strtolower($msgType));
		if (in_array('response', $msgTypeParts)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * get list of available providers from the configuration
	 * @return type
	 */
	public static function getProviderArray() {
		$providers = array_keys(Application_Model_General::getSettings('provider'));
		$InternalProvider = Application_Model_General::getSettings('InternalProvider');
		if (!in_array($InternalProvider, $providers)) {
			$providers[] = $InternalProvider;
		}
		return $providers;
	}

	/**
	 * get the logging request directory path (for logging purpose)
	 * 
	 * @param string $request_id request id
	 * @param boolean $force_create should the method create the folder if not exists
	 * 
	 * @return string directory path
	 */
	public static function getRequestDirPath($request_id, $force_create = false) {
		$ar = str_split($request_id, 2);
		if ($ar && count($ar) > 2) {
			$relative_path = preg_replace("/[^0-9a-zA-Z]/i", '', $ar[1]) . '/' . preg_replace("/[^0-9a-zA-Z]/i", '', $ar[2]) . '/';
		} else {
			$relative_path = './';
		}
		// TODO oc666: move this to config
		$base_path = APPLICATION_PATH . '/../logs/' . Application_Model_General::getSettings('InternalProvider') . '/';
		$path = $base_path . $relative_path;
//		error_log($path);
		if ($force_create && !file_exists($path)) {
			if (!@mkdir($path, 0777, true)) {
				// if cannot create relative path, return the logs root path
				$path = $base_path;
			}
		}
		return $path;
	}

	/**
	 * get the logging request file path (for logging purpose)
	 * 
	 * @param string $request_id request id
	 * @param boolean $force_create should the method create the folder if not exists
	 * 
	 * @return string full file path
	 */
	public static function getRequestFilePath($request_id, $force_create = false) {
		$file_path = self::getRequestDirPath($request_id, $force_create) . $request_id . ".log";
		if ($force_create && !file_exists($file_path)) {
			touch($file_path);
		}
		return $file_path;
	}

	/**
	 * method to log soap requests
	 * 
	 * @param string $content the content to output
	 * @param string $request_id the request id (will be the file name)
	 * 
	 * @return void
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

	/**
	 * method to log request and its response
	 * 
	 * @param mixed $request the request to log
	 * @param mixed $response the response to log
	 * @param string $request_id the request id
	 * @param string $prefix prefix to add before the output
	 * 
	 * @return void
	 */
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
	 * method to create random key in specific length
	 * @param int $length the length of the generated key
	 * 
	 * @return string random key
	 */
	public static function createRandKey($length = 32) {
		return substr(md5(microtime()), 0, $length);
	}

	public static function isProd() {
		return strpos(APPLICATION_ENV, 'prod') !== FALSE;
	}

	/**
	 * make sleep on non production environments
	 * useful when the log order is not correct in dev or testing environments
	 * 
	 * @param int $sleepTime time to sleep in microseconds. if not supply set by config or default (0.1 seconds)
	 * 
	 * @return void
	 */
	public static function virtualSleep($sleepTime = null) {
		if (!self::isProd()) {
			if (is_null($sleepTime)) {
				$sleepTime = self::getSettings('testing-sleep', 100000); // microseconds
			}
			usleep((int) $sleepTime);
		}
	}

	/**
	 * method to get locale, default to config locale
	 * 
	 * @param string $locale the locale to bring if null get locale from settings
	 * @param boolean $instance if true return zend locale instance
	 * 
	 * @return mixed if instance is true return zend locale, else return string
	 */
	public static function getLocale($locale = null, $instance = true) {
		if (is_null($locale)) {
			$locale = self::getSettings('locale', 'he_IL');
		}
		$stamp = md5(serialize(array($locale, $instance)));
		if (!isset(self::$locales[$stamp])) {
			if ($instance) {
				self::$locales[$stamp] = new Zend_Locale($locale);
			} else {
				self::$locales[$stamp] = $locale;
			}
		}
		return self::$locales[$stamp];
	}
	
	/**
	 * retrieve all providers respond to publish
	 * 
	 * @param string $request_id the request id that need to be checked
	 * 
	 * @return array of providers
	 */
	public static function getProvidersRequestWithPublishResponse($request_id) {
		$db = Np_Db::slave();
		$select = $db->select();
		$internalProvider = Application_Model_General::getSettings('InternalProvider');
		$select->from('Transactions'); //, array('provider' => new Zend_Db_Expr('SUBSTR(trx_no,1,2)') ,'request_id')); //THERE IS NO FROM FIELD!!
		$select->where('request_id = ?', $request_id)
			->where('message_type = ?', 'Publish_response')
			->where('target = ?', $internalProvider)
			->where('reject_reason_code is NULL');
		$result = $db->query($select);
		$rows = $result->fetchAll();
		$publish_response_providers = array();
		foreach ($rows as $row) {
			$publish_response_providers[] = substr($row['trx_no'], 0, 2);
		}
		return $publish_response_providers;
	}
	
	/**
	 * retrieve all providers not respond to publish
	 * 
	 * @param string $request_id the request id that need to be checked
	 * @param string $from the source provider
	 * @param string $to the destination provider
	 * 
	 * @return array of all providers without publish response
	 */
	public static function getProvidersRequestWithoutPublishResponse($request_id, $from, $to) {
		$publish_response_providers = self::getProvidersRequestWithPublishResponse($request_id);
		$providerArray = array_keys(Application_Model_General::getSettings('provider', array()));
		//remove publish response, source & destination provider
		$problem_providers = array_diff($providerArray, array_merge($publish_response_providers, array($from, $to)));
		return $problem_providers;
	}

}
