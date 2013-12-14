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
class Application_Model_Agg {

	/**
	 * array with "message types" assigned to theyre Parent Process Type
	 * 
	 * @var array  
	 */
	public static $processType = array(
		"PORT" => array('Check', 'Check_response', 'Request', 'Request_response', 'Update', 'Update_response',
			'Cancel', 'Cancel_response', 'Execute', 'Execute_response', 'KD_update', 'KD_update_response',
			'Publish', 'Publish_response', 'Cancel_publish', 'Cancel_publish_response'),
		"RETURN" => array("Return", "Return_response"),
		"QUERY" => array("Inquire_number"),
		"MAINT" => array("Down_system", "Up_system"),
	);

	/**
	 * array of message types that indicate anumber is still in transfer process.
	 * 
	 * @var Array 
	 */
	static public $inProcess = Array("Request", "Request_response",
		"Update", "Update_response",
		"KD_update", "KD_update_response",
		"Execute", "Execute_response",
		"Publish",
	);

	/**
	 * array of cellular providers // move to config?
	 * 
	 * @var Array
	 */
	static public $providers = Array("GT", "PL",
		"PR", "PM",
		"CL", "CM",
		"MI", "BZ",
		"HT", "KD",
		"KZ", "BI",
		"NV", "EX",
		"OR", "HI"
	);

	/**
	 * array of "request" type transactions 
	 * 
	 * @var Array
	 */
	static public $requestTransactions = Array("Check", "Request",
		"Execute", "Update",
		"Cancel", "Publish",
		"KD_update"
	);

	/**
	 * array of "response" type transactions 
	 * 
	 * @var Array
	 */
	static public $responseTransactions = Array("Check_response", "Request_response",
		"Execute_response", "Update_response",
		"Cancel_response", "Publish_response",
		"KD_update_response"
	);

	/**
	 *
	 * @var array process Types array of possible Process Types
	 * 
	 */
	static public $processTypes = Array("PORT", "RETURN", "QUERY", "MAINT");

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
	 * gets reject reason code from transactions table by request id
	 * 
	 * @param type $reqID the request id 
	 * @return mixed string reject reason code or NULL 
	 */
	static public function getRejectReasonCodeByRequestID($reqID) {

		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());

		$select = $tbl->select('reject_reason_code')
				->order("id DESC")
				->limit(1);
		$select->where('request_id=?', $reqID);
		$result = $select->query()->fetchObject();   //take the last one

		if (isset($result->reject_reason_code)) {
			return $result->reject_reason_code;
		}
		return null;
	}

	/**
	 * checks if row exists in Activity_Process for today.
	 * 
	 * @param type $processType
	 * @param type $status
	 * @param type $from
	 * @param type $to
	 * @return BOOL do rows exist TRUE OR FALSE
	 */
	static public function validateProcessTypeReportsRow($processType, $status, $from, $to) {
		$strTime = date('Y-d-m');
		$date = application_model_general::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::slave());
		$select = $tbl->select();
//		die($date);
		$select->where('`process_type` = ?', $processType)
				->where('`process_parent_status` = ?', $status)
				->where('`agg_date` = ?', $date)
				->where('`from` = ?', $from)
				->where('`to` = ?', $to);
//		die($select->__toString());
		$result = $select->query()->fetchAll();   //take the last one
		error_log('$result : ' . print_r($result, 1));
		error_log('count($result) : ' . print_r(count($result), 1));

		if (count($result) == 0) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * updates a row in Activity_Process
	 * 
	 * @param type $processType
	 * @param type $status
	 * @param type $from
	 * @param type $to
	 * @return int rows affected  
	 */
	static public function UpdateProcessTypeReports($processType, $status, $from, $to) {
//		$dateStr = self::getLastAggDate();
//		if(!$dateStr || $dateStr==NULL){
		$date = date("Y-m-d") . " 00:00:00";
//		}
//		var_dump($dateStr);
//		die;
//		$date = Application_Model_General::getTimeInSqlFormatAgg($dateStr);
//		var_dump($date);
		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::master());
		$update_arr = array('count' => new Zend_Db_Expr('count + 1'));
		$where[] = "`process_type` = '" . $processType . "'";
		$where[] = "`process_parent_status` = '" . $status . "'";
		$where[] = "`from` = '" . $from . "'";
		$where[] = "`to` = '" . $to . "'";
		$where[] = "`agg_date` = '" . $date . "'";
//		die($date);
		error_log('$WHERE$WHERE$WHERE$WHERE : ' . print_r($where, 1));
		$res = $tbl->update($update_arr, $where);
		return $res;
	}

	/**
	 * inserts new row to Activity_Process
	 * 
	 * @param type $processType
	 * @param type $status
	 * @param type $from
	 * @param type $to
	 * @return object DB RESPONSE 
	 */
	static public function InsertToProcessTypeReports($processType, $status, $from, $to) {
//		$dateStr = self::getLastAggDate();
//		if(!$dateStr){
		$dateStr = date("Y-d-m");
//		}
		$date = Application_Model_General::getTimeInSqlFormatAgg($dateStr);
		$row = array();
		$row['process_type'] = $processType;
		$row['process_parent_status'] = $status;
		$row['from'] = $from;
		$row['to'] = $to;
		$row['agg_date'] = $date;
		$row['recipient'] = $to;
		$row['donor'] = $from;
		$row['count'] = 1;
		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::master());
		$res = $tbl->insert($row);
		return $res;
	}

	/**
	 * checks date for the last aggregation in ActivityProcess
	 * 
	 * @return mixed string date or FALSE
	 */
	static public function getLastAggDate() {
		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::slave());
		$select = $tbl->select()->order('id ASC');
		$result = $select->query()->fetch();
		if (FALSE !== ($result = $select->query()->fetch()) && isset($result['agg_date'])) {
			return $result['agg_date'];
		}

		return FALSE;
	}

	/**
	 * gets all data from requests if Activity_Process has no data
	 * if it does gets data starting from the last aggregation date
	 * 
	 * @return object DB RESPONSE
	 */
	static public function getParentProcessArray() {
		$strTime = date('Y-d-m');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
//		$date = self::getLastAggDate();
//		var_dump($date);
//		die;
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->where("last_requests_time > ?", $date);
		if (FALSE !== ($date)) {
			$select->where("last_requests_time > ?", $date);
		}
		$result = $select->query()->fetchAll();
//		 var_dump($result);
//                                    die;
		return $result;
	}

	/**
	 * gets Parent Process Status by MSG TYPE and REJECT REASON CODE
	 * 
	 * @param type $msgType
	 * @param type $rejectReasonCode
	 * @return string transaction process status 
	 */
	static public function getParentProcessStatus($msgType, $rejectReasonCode) {
		if (in_array($msgType, self::$requestTransactions) && $rejectReasonCode === NULL || !$rejectReasonCode) {
			return "INCOMPLETE";
		} elseif (in_array($msgType, self::$responseTransactions) && $rejectReasonCode === NULL || !$rejectReasonCode) {
			return "SUCCESS";
		} elseif (!empty($rejectReasonCode)) {
			return "FAILURE";
		}
	}

	/**
	 * gets all data from Activity_Process
	 * 
	 * @return object with db data 
	 */
	static public function getProcessTypeRows($dateFix = FALSE, $provider = FALSE, $recipient = FALSE, $start = FALSE, $end = FALSE) {
		$tbl = new Application_Model_DbTable_ActivityProcess(Np_Db::slave());
//		if(isset($dateFix) && $dateFix){
//                                    $dateFix = Application_Model_General::getRealTimeInSqlFormatForAgg($dateFix);
//                                    }
		//->limit($start, $end)
		$select = $tbl->select();
		
		if ( $provider != FALSE && !empty($provider)) {
			$select->where("`from` = ?", $provider);
		}
		
		if ( $recipient != FALSE || !empty($recipient) ) {
			$select->where("recipient = ?", $recipient);
		}
		if ( $dateFix == FALSE || empty($dateFix)) {
			$dateFix = "00-00-00 00:00:00";
		}
		else{
			$select->where("agg_date > ?", $dateFix);
		}

		$result = $select->query()->fetchAll();
                
				
		return $result;
	}

	/**
	 * gets all data from Activity_Timers
	 * 
	 * @return object with db data 
	 */
	static public function getTimersActivityRows($date = FALSE, $provider = FALSE, $recipient = FALSE, $limitstart=FALSE, $limit=FALSE) {
		if ($date != FALSE) {
			$dateFix = $date . " 00:00:00";
//                                    var_dump($dateFix);
//									die;	
		} else {
			$dateFix = 0;
		}

		$tbl = new Application_Model_DbTable_ActivityTimers(Np_Db::slave());
		if ($recipient && $dateFix && $provider) {
//                                    var_dump($dateFix);
			$select = $tbl->select()
//				->where('waiting_op =?',$recipient)
							->where('transaction_time > ?', $date)->limit($limitstart, $limit);
		} elseif (!$dateFix && !$provider && !$recipient) {
			$select = $tbl->select();
//                                        ->where(' `transaction_time`  = 0')
//                                        		->order('id DESC');
		} else {
			$select = $tbl->select()->limit($limitstart, $limit);
		}
		$result = $select->query()->fetchAll();
		return $result;
	}

	/**
	 * increment process on time  per timer .
	 * 
	 * @return object with db data 
	 */
	static public function incrementValidProcessByTimer($timer, $obligated, $waiting) {
		$dateStr = date("Y-m-d");
		$date = Application_Model_General::getTimeInSqlFormatAgg($dateStr);
		$tbl = new Application_Model_DbTable_ActivityTimersAgg(Np_Db::master());
		$update_arr = array('process_on_time_count' => new Zend_Db_Expr('count + 1'));
		$where[] = "`timer_type` = '" . $timer . "'";
		$where[] = "`obligated_op` = '" . $obligated . "'";
		$where[] = "`waiting_op` = '" . $waiting . "'";
		$res = $tbl->update($update_arr, $where);
		return $res;
	}

	/**
	 * check if timer exists with specific obligated operator , waiting operator and in specific date  .
	 * 
	 * @return object with db data 
	 */
	static public function checkIfTimerRowExists($timer, $obligatedOp, $waitingOp) {
		$strTime = date('Y-m-d');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->where("last_requests_time > ?", $date)
				->where("timer_type =?", $timer)
				->where("obligated_op =?", $obligatedOp)
				->where("waiting_op =?", $waitingOp);
		$result = $select->query()->fetchObject();
		if (!$result) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * increment incalid process on time  per timer .
	 * 
	 * @return object with db data 
	 */
	static public function incrementInvalidProcessByTimer($timer, $obligated, $waiting) {
		$dateStr = date("Y-m-d");
		$date = Application_Model_General::getTimeInSqlFormatAgg($dateStr);
		$tbl = new Application_Model_DbTable_ActivityTimersAgg(Np_Db::master());
		$update_arr = array('process_delay_count' => new Zend_Db_Expr('count + 1'));
		$where[] = "`timer_type` = '" . $timer . "'";
		$where[] = "`obligated_op` = '" . $obligated . "'";
		$where[] = "`waiting_op` = '" . $waiting . "'";
		$res = $tbl->update($update_arr, $where);
		return $res;
	}

	/**
	 * insert incalid process on time  per timer .
	 * 
	 * @return object with db data 
	 */
	static public function insertInvalidProcessByTimer($row) {

		$tbl = new Application_Model_DbTable_ActivityTimersAgg(Np_Db::master());
		$res = $tbl->insert($row);
		return $res;
	}

	/**
	 * Get Number of Today's Requests
	 * 
	 * @return object with db data 
	 */
	static public function getDailyRequests() {
		$strTime = date('Y-m-d');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select()->where("last_requests_time > ?", $date);
		$result = $select->query()->fetchAll();
		return count($result);
	}

	/**
	 * Get Number of Requests that have timedout today.
	 * 
	 * @return object with db data 
	 */
	static public function getTimedOutRequests($timer) {
		$strTime = date('Y-m-d');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_ActivityTimers(Np_Db::slave());
		$select = $tbl->select()
				->where("transaction_time > ?", $date)
				->where("timer =?", $timer);
		$result = $select->query()->fetchAll();
		return count($result);
	}

	/**
	 * Get Some Percentage.
	 * 
	 * @return object with db data 
	 */
	static public function getPercentage($num_amount, $num_total) {
		$count1 = $num_amount / $num_total;
		$count2 = $count1 * 100;
		$count = number_format($count2, 0);
		return $count;
	}

	static public function getTransactionsByMsgType($msg_type) {
		$strTime = date('Y-d-m');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select()
				->where("last_transactions_time > ?", $date)
				->where("message_type =?", $msg_type);
		$result = $select->query()->fetchAll();
		return $result;
	}

	static public function getAllTransactionsMessages() {
		$strTime = date('Y-d-m');
		$date = Application_Model_General::getTimeInSqlFormatAgg($strTime);
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select()
				->where("last_transactions_time > ?", $date);
		$allRows = $select->query()->fetchAll();
		$counter = 0;
		$messageTypes = array();
		foreach ($allRows as $key => $value) {
			$counter++;
			if (isset($value['message_type']) && !in_array($value['message_type'], $messageTypes)) {
				$messageTypes[$counter] = $value['message_type'];
			}
		}
		return $messageTypes;
	}

	static public function checkTimedOutTransactions($transactions) {

		$numberOfTransactions = count($transactions);

		// count how many transactions took place per message type
		$faultCount = 0;
		$faultArray = array();
		$validArray = array();
		foreach ($transactions as $key => $value) {
			$waiting = substr($transactions[$key]['request_id'], 4, 2);
			$obligated = substr($transactions[$key]['request_id'], 2, 2);
			// iterate through the array looking for timeouts
			// if reject reason code exists and is not null...
			if ($value['reject_reason_code'] && $value['reject_reason_code'] !== NULL) {
				//if this is the first iteration with a reject reason code
				if (!isset($faultArray[$obligated][$waiting][$value['reject_reason_code']]['timeouts'])) {

					// increment array cell with reject reason code key
					$faultArray[$obligated][$waiting][$value['reject_reason_code']]['timeouts'] = 0;
				}

				$faultCount++; //increment the number of faults
				$faultArray[$obligated][$waiting][$value['reject_reason_code']]['timeouts']++;
			}
		}


		foreach ($faultArray as $obligated => $perobligated) {
			foreach ($perobligated as $bals => $aals) {
				foreach ($aals as $rals => $v) {
//					var_dump($v);
//					die;
					$faultArray[$obligated][$bals][$rals]['ontime'] = $numberOfTransactions - $v['timeouts'];
				}
			}
		}

		return $faultArray;
	}

}

