<?php

/**
 * Timers Class File
 * Model for Number Transaction operations.
 * 
 * @package Np_Method
 * @subpackage Timers
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Timers Class Definition
 * 
 * @package Np
 * @subpackage Np_Timers
 */
class Np_Timers {

	static $timerMethodArr = array(
		'check_response' => array(
			'code' => 'TRES2',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'request' => array(
			'code' => 'T2DR2',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'request_response' => array(
			'code' => 'T3DR1',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'kd_update_response' => array(
			'code' => 'T3KR',
			'time' => 'CURRENT',
			'lt' => TRUE
		),
		'update' => array(
			'code' => 'T4DR2',
			'time' => 'PORT',
			'lt' => FALSE
		),
		'update_response' => array(
			'code' => 'T3DR1U',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'cancel' => array(
			'code' => 'T4DR2',
			'time' => 'PORT',
			'lt' => FALSE
		),
		'cancel_response' => array(
			'code' => 'T3DR1D',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'execute_response' => array(
			'code' => 'T4DR1',
			'time' => 'CURRENT',
			'lt' => FALSE
		),
		'return' => array(
			'code' => 'T5BA2',
			'time' => 'CURRENT',
			'lt' => TRUE
		),
		'return_response' => array(
			'code' => 'T5BA3',
			'time' => 'CURRENT',
			'lt' => TRUE
		),
		'Cancel_publish_response' => array(
			'code' => 'T5OO1',
			'time' => 'CURRENT',
			'lt' => TRUE
		),
		'Inquire_number_response' => array(
			'code' => 'T5OO1',
			'time' => 'CURRENT',
			'lt' => TRUE
		),
	);

	/**
	 *
	 * @var type 
	 */
	static $last_requests_time;

	/**
	 *
	 * @var String 
	 */
	static $last_transaction;

	/**
	 *
	 * @var String new port time (in case update transfer time
	 */
	static $port_time;

	/**
	 *
	 * @var String last port time (in case update transfer time
	 */
	static $last_port_time;

	/**
	 *
	 * @var mixed last failure timer. 
	 *  Can be false or string (timer or reject reason code)
	 */
	static $failure = FALSE;

	/**
	 * variable to set debug mode
	 * @var boolean
	 */
	static $debug = FALSE;

	/**
	 * gets timer time period in seconds
	 * 
	 * gets timer name in timer param 
	 * and optionally a $type value
	 * if type isset returns $this->timer[$type]
	 * else returns $this->timer 
	 * 
	 * @param string $timer
	 * @return Int the time of the timer
	 */
	static public function get($timer) {
		if ($timer) {
			return (int) Application_Model_General::getTimer($timer);
		}
		return 0;
	}

	/**
	 * check if the request time is not passed the timer time out 
	 * 
	 * @param String $timer
	 * @param mixed $input_time the time (as date string or unix timestamp)to be compared
	 * @param mixed $time the second time (as date string or unix timestamp) to be compared
	 * @param Boolan $type how to compare the two dates
	 * 
	 * @return bool true if request timed out
	 */
	public static function isTimeout($timer, $input_time, $time = null, $lt = TRUE) {
		if (!is_numeric($input_time)) {
			$input_time = strtotime($input_time);
		}
		if (empty($time)) {
			$time = Application_Model_General::getTimeStampInSqlFormat(time());
		} else if (!is_numeric($time)) {
			$time = strtotime($time);
		}
		$compare_time = $input_time + self::get($timer);
		if (self::$debug) {
			error_log("timer type GT:      " . $timer);
			error_log("timer time GT:       " . self::get($timer));
			error_log("input time GT: " . $input_time);
			error_log("time GT:       " . $time);
			error_log("compare time GT:       " . $compare_time);
			error_log("lt: " . ($lt ? "TRUE" : "FALSE"));
		}
		if (($lt && ($compare_time > $time)) || (!$lt && ($compare_time < $time))) {
			if (self::$debug) {
				error_log("Timeout GT");
			}
			self::$failure = $timer;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Method to validate request timers
	 * 
	 * @param object $request Request Object
	 * @return boolean true when the request timing is valid 
	 *   string when request timing is not valid with the correct ack
	 */
	public static function validate($request) {
		//reset the failure for next validation
		self::$failure = FALSE;
		$ret = TRUE;
		$isTimeout = FALSE;
		//we're on check - no timers
		if (!isset($request->last_method_time) && !$request->last_method_time) {
			return TRUE;
		}

		$msg_type = strtolower($request->getHeaderField('MSG_TYPE'));
		self::$last_transaction = $request->last_method;
		self::$last_requests_time = $request->last_method_time;
		self::$last_port_time = $request->last_transfer_time;
		self::$port_time = $request->getBodyField("PORT_TIME"); // current port time

		if (isset(self::$timerMethodArr[$msg_type])) {
			$timer = self::$timerMethodArr[$msg_type];
//			Application_Model_Agg::insertInvalidProcessByTimer($timer, $request->getHeaderField('MSG_TYPE') , $waiting);
			if ($timer['time'] == "PORT") {
				$input_time = self::$last_port_time;
			} else {
				$input_time = self::$last_requests_time;
			}
			if ($timer['lt']) {
				$isTimeout = self::isTimeout($timer['code'], $input_time); //true of false
			} else {
				$isTimeout = self::isTimeout($timer['code'], $input_time, null, FALSE); //true of false
			}
		}

		// the first timeout should return true or false (no further use for the timer code)
		if ($isTimeout === TRUE) {
				Application_Model_General::writeToTimersActivity($request->getHeaders(), self::$failure);
				
			if (self::$debug) {
				error_log("Timer is not valid: " . $ret);
			}
			return "Gen07";
		}
		$method_name = $msg_type . "Timeout";
		if (in_array($method_name, get_class_methods("Np_Timers"))) {
			self::$method_name();
			if (self::$failure !== FALSE) {
				$ret = ucfirst(strtolower(self::$failure));
				Application_Model_General::writeToTimersActivity($request->getHeaders(), $ret);
				if (self::$debug) {
					error_log("Timer is not valid: " . $ret);
				}
				return $ret;
			}
		}

		return $ret;
	}

	/**
	 * checks for possible "Request" transaction timeouts.
	 * 
	 * @return bool 
	 */
	static protected function requestTimeout() {
		if (self::isTimeout("REQ02", self::$last_requests_time, self::$port_time, TRUE)) {
			return FALSE;
		}
		if (self::isTimeout("REQ03", self::$last_requests_time, self::$port_time, FALSE)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * checks for possible "Execute" transaction timeouts.
	 * 
	 * @return bool 
	 */
	static protected function executeTimeout() {
		if (self::isTimeout("EXE02", self::$last_port_time, NULL, FALSE)) {
			return FALSE;
		}
		if (self::isTimeout("EXE04", self::$last_port_time, NULL, TRUE)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * checks for possible "Cancel Publish" transaction timeouts.
	 * 
	 * @return bool 
	 */
	static protected function cancel_publishTimeout() {
		if (self::isTimeout("CPB04", self::$last_requests_time)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * checks for possible "Kd_update" transaction timeouts.
	 * 
	 * @return bool 
	 */
	static protected function kd_updateTimeout() {
		if (self::$last_transaction == 'update_response') {
			$timer = "T3RK2";
		} else { // cancel_response
			$timer = "T3RK3";
		}
		if (self::isTimeout($timer, self::$last_requests_time)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * checks for possible "Update" transaction timeouts.
	 * 
	 * @return bool 
	 */
	static protected function updateTimeout() {
		//the requested port time is smaller than old port time
		if (self::isTimeout("UPD02", self::$last_requests_time, self::$port_time)) {
			return FALSE;
		}
		if (self::isTimeout("UPD03", self::$last_requests_time, self::$port_time, FALSE)) {
			return FALSE;
		}
		if (self::isTimeout("UPD04", self::$port_time, NULL, FALSE)) {
			return FALSE;
		}
		return TRUE;
	}

}
