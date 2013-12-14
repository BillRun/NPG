<?php

/**
 * Cron Model
 * 
 * @package ApplicationModel
 * @subpackage CronModel
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Cron Object
 * 
 * @package ApplicationModel
 * @subpackage CronModel
 */
class Application_Model_Cron {

	/**
	 * Constructor
	 * 
	 * 
	 */
	public function __construct() {
		
	}

	/**
	 * Check if Number is ready for Execute .
	 * If it does opens a new Process
	 * 
	 * @return void
	 * 
	 */
	public static function makeChangeProvider() {
		$ret = array();
		$minutes = 15;

		//select the row to Excute Transfer NOW
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$execute_statuses = array('Request', 'Request_response', 'Update', 'Update_response', 'KD_update', 'KD_update_response');
		if (Application_Model_General::getSettings('EnableExecuteRetry') != 0) {
			$execute_statuses[] = 'Execute';
		}
		$select = $tbl->select();
		// the first two conditions will verify it will run only in the first 17 minutes
		$select->where('transfer_time IS NOT NULL');
		$select->where('transfer_time <= \'' . Application_Model_General::getTimeInSqlFormat(time()) . '\'');
		$select->where('transfer_time > \''. Application_Model_General::getTimeInSqlFormat(strtotime($minutes . ' minutes ago')) . '\'');
		$select->where('last_transaction IN (?)', $execute_statuses);
		$select->where("to_provider LIKE '" . Application_Model_General::getSettings('InternalProvider') . "'");
		$select->where('status = 1');
		$select->where('cron_lock = 0');

		$rows = $select->query()->fetchAll();
		foreach ($rows as $row) {
			$ret[] = $row;
			Application_Model_General::forkProcess("/cron/transfer", $row);
		}
		return $ret;
	}

	// TODO
	public static function forceExecuteProvider() {
		
	}

	/**
	 * Select all request to Publish, new process for each request. 
	 * update DB Requests to status publish
	 * 
	 * @return void
	 * 
	 */
	public static function publishChangeProvider() {

		$ret = array();
		$providerArray = array_keys(Application_Model_General::getSettings('provider'));
		$updateIDs = array();
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select();
		$select->where('last_transaction IN (?)', array('Execute_response', 'Return_response'));
		$select->where("to_provider LIKE '" . Application_Model_General::getSettings('InternalProvider') . "'");
		$select->where('status = 1');

		$result = $select->query();
		while ($row = $result->fetch()) {

			$updateIDs[] = $row['id'];

			foreach ($providerArray as $provider) {
				if (substr($row['request_id'], 2, 2) != Application_Model_General::getSettings('InternalProvider') && strtoupper($row['last_transaction']) == "RETURN_RESPONSE") {
					// not supposed to send to abandoned provider 
					if ($provider != $row['to_provider']) {
						$row['provider'] = $provider;
						$ret[] = $row;
						Application_Model_General::forkProcess("/cron/publish", $row);
					}
				} elseif (strtoupper($row['last_transaction']) != "RETURN_RESPONSE") {
					if ($provider != $row['from_provider'] && $provider != $row['to_provider']) {
						$row['provider'] = $provider;
						$ret[] = $row;
						Application_Model_General::forkProcess("/cron/publish", $row);
					}
				}
			}

			if ($updateIDs &&
				!($row['last_transaction'] == "Return_response" &&
				substr($row['request_id'], 2, 2) == Application_Model_General::getSettings('InternalProvider'))
			) {
				$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
				$tbl->update(array('last_transaction' => 'Publish'), array('id IN (?)' => $updateIDs));
			}
		}
		return $ret;
	}

	/**
	 * Select all request to Publish, new process for each request. 
	 * update DB Requests to status publish
	 * 
	 * @return void
	 * 
	 */
	public static function checkPublishResponseFromProviders($force = FALSE) {

		$ret = array();
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select();
		$minutes = 10;
		$min_minutes = 5;
		$max_minutes = 15;

		$select->where('last_transaction = ?', 'Publish')
//			->where('transfer_time <= \'' . Application_Model_General::getTimeInSqlFormat() . '\'')
//			->where('transfer_time > \''. Application_Model_General::getTimeInSqlFormat(strtotime($minutes . ' minutes ago')) . '\'')
			->where('transfer_time <= \'' . Application_Model_General::getTimeInSqlFormat(strtotime('-' . $min_minutes .' minutes ago')) . '\'')
			->where('transfer_time > \''. Application_Model_General::getTimeInSqlFormat(strtotime('-' . $max_minutes . ' minutes ago')) . '\'')

			->where('status =  1');

		$result = $select->query();
		while ($row = $result->fetch()) {
			//            $past_minutes = (time() - strtotime($row['transfer_time'])) / 60;
			//            if (!$force){ //&& $past_minutes > Application_Model_General::getSettings('minutesToRevertTransfer')) {
			//				$view[] = array_merge($row, array("status" => "time pasted - revert"));
			//				//revert!!
			//				$cmd = "/cron/revert";
			//			} else {
			$ret[] = array_merge($row, array("status" => "publish check"));
			$cmd = "/cron/checkpublish";
			Application_Model_General::forkProcess($cmd, $row); // for debugging
//            }
//			Application_Model_General::forkProcess($cmd, $row);
		}

		return $ret;
	}

	/**
	 * locks request process according to request id 
	 * and sends execute message to provider
	 * 
	 * @param array $request 
	 * @return void
	 */
	public function executeTransfer($request) {
		self::lockDBRow(1, $request['id']);
		$params = array(
			//FROM + TO
			'REQUEST_ID' => $request['request_id'],
			'PROCESS_TYPE' => "PORT",
			'MSG_TYPE' => "Execute",
			'FROM' => Application_Model_General::getSettings('InternalProvider'),
			'TO' => $request['from_provider'],
			"RETRY_DATE" => Application_Model_General::getDateIso(),
			"RETRY_NO" => "1",
			"VERSION_NO" => Application_Model_General::getSettings("VersionNo"),
		);
		$reqModel = new Application_Model_Request($params);
		// make sure the request is ok.
		// can use $reqModel->RequestValidate() but maybe there is no need becuse i'm building it
		//fix bug #5285
		$params['NUMBER'] = Application_Model_General::getFieldFromRequests("number", $params['REQUEST_ID']);
		//end of fix bug #5285
		Application_Model_General::writeToLog($params);
		$reqModel->ExecuteFromInternal();

		//SEND TO INTERNAL the response will be send to internal
		self::lockDBRow(0, $request['id']);
	}

	/**
	 * locks request process according to request id 
	 * and sends execute message to provider
	 * 
	 * @param array $request 
	 * @return void
	 */
	public function executeTransferOut($request) {
		$params = array(
			//FROM + TO
			'REQUEST_ID' => $request['request_id'],
			'PROCESS_TYPE' => "PORT",
			'MSG_TYPE' => "Execute_response",
			'TO' => Application_Model_General::getSettings('InternalProvider'),
			'FROM' => $request['from_provider'],
			"RETRY_DATE" => Application_Model_General::getDateIso(),
			"RETRY_NO" => "1",
			"VERSION_NO" => Application_Model_General::getSettings("VersionNo"),
		);
		$reqModel = new Application_Model_Request($params);
		// make sure the request is ok.
		// can use $reqModel->RequestValidate() but maybe there is no need becuse i'm building it
		//fix bug #5285
		$params['NUMBER'] = Application_Model_General::getFieldFromRequests("number", $params['REQUEST_ID']);
		//end of fix bug #5285
		Application_Model_General::writeToLog($params);
		$reqModel->ExecuteFromInternal();

	}

	/**
	 * gets number of publish retries
	 * 
	 * @param type $params reqyest params
	 * @return int number of retries 
	 */
	function get_retry_no($params) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		$select->from('Transactions', array('retry' => new Zend_Db_Expr('COUNT(*)')));
		$select->where('request_id LIKE ?', $params['REQUEST_ID']);
		$select->where('message_type LIKE ?', 'Publish');
		$select->where('target LIKE ?', $params['TO']);
		$result = (int) $select->query()->fetchObject()->retry;
		return $result + 1;
	}

	/**
	 * send publish to specific provider
	 * 
	 * @param array $request 
	 * @return void
	 */
	public function executePublish($request) {
		$params = Application_Model_General::getParamsArray($request);
		// let's check if it's port (means we have execute response before)
		$execute_exists = Application_Model_General::getTransactions($request['request_id'], 'Execute_response', 'null');
		if (empty($execute_exists) || !is_array($execute_exists) || !count($execute_exists)) {
			$publish_type = "Rtrn";
		} else {
			$publish_type = "Port";
		}

		$params['PROCESS_TYPE'] = "PORT";
		$params['MSG_TYPE'] = "Publish";
		$params['FROM'] = Application_Model_General::getSettings('InternalProvider');
		$params['DONOR'] = $params['FROM_PROVIDER']; //$row['DONOR'] = $provider;
		$params['PUBLISH_TYPE'] = $publish_type;
		$params["RETRY_DATE"] = Application_Model_General::getDateIso();
		$params["RETRY_NO"] = $this->get_retry_no($params);
		$params["VERSION_NO"] = Application_Model_General::getSettings("VersionNo");

		$reqModel = new Application_Model_Request($params);
		// make sure the request is ok.
		// can use $reqModel->RequestValidate() but maybe there is no need becuse i'm building it
		// TODO.oc666: why we execute from internal?
		$reqModel->ExecuteFromInternal(); //what with the ack in the DB - status 1 for each?
	}

	/**
	 * checks if all providers received publish 
	 * if not sends again to the ones who didnt.
	 * 
	 * @param array $request 
	 * @return void
	 */
	public function checkPublish($request, $publishForceAll = FALSE) {
		$internalProvider = Application_Model_General::getSettings('InternalProvider');
		// check if this not the internal provider publish
		//TODO oc666: check if necessary
		if (substr($request['request_id'], 2, 2) != $internalProvider) {
			$where[] = "request_id = " . $resultFetch['request_id'] . "";
			$where[] = "trx_no NOT LIKE '" . $internalProvider . "%'";
			$req_tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$res = $req_tbl->update(array('last_transaction' => 'Publish_response'), $where);
			return $res;
		}
		$publish_response_providers = array();
		if (!$publishForceAll) {
			$db = Np_Db::slave();
			$select = $db->select();
			$select->from('Transactions'); //, array('provider' => new Zend_Db_Expr('SUBSTR(trx_no,1,2)') ,'request_id')); //THERE IS NO FROM FIELD!!
			$select->where('request_id = ?', $request['request_id'])
				->where('message_type = ?', 'Publish_response')
				->where('target = ?', $internalProvider)
				->where('reject_reason_code is NULL');
			$result = $db->query($select);
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				$publish_response_providers[] = substr($row['trx_no'], 0, 2);
			}
		}
		$providerArray = array_keys(Application_Model_General::getSettings('provider'));
		//remove publish response, source & destination provider
		$problem_providers = array_diff($providerArray, array_merge($publish_response_providers, array($request['from_provider'], $request['to_provider'])));
		if ($problem_providers) {
			//problem! - send providers again
			$ret = array();
			foreach ($problem_providers as $provider) {
				$request['provider'] = $provider;
				Application_Model_General::forkProcess("/cron/publish", $request);
				$ret[] = $request;
			}
			return $ret;
		} else {

			//no problem - update publish_response
			$req_tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$req_tbl->update(array('last_transaction' => 'Publish_response', 'status' => 0), 'id=' . $request['id']);
			//send to internal!
		}
		return TRUE;
	}

	public function revertPublish($request) {
		$internalProvider = Application_Model_General::getSettings('InternalProvider');
		// check if this not the internal provider publish
		//TODO oc666: check if necessary
		if (substr($request['request_id'], 2, 2) != $internalProvider) {
			$where[] = "request_id = " . $resultFetch['request_id'] . "";
			$where[] = "trx_no NOT LIKE '" . $internalProvider . "%'";
			$req_tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$res = $req_tbl->update(array('last_transaction' => 'Publish_response'), $where);
			return $res;
		}
		$publish_response_providers = array();
		if ($publish_response_providers) {
			//problem! - send providers again
			$ret = array();
			foreach ($publish_response_providers as $provider) {
				$request['provider'] = $provider;
				Application_Model_General::forkProcess("/cron/cancelpublish", $request);
				$ret[] = $request;
			}
			return $ret;
		} else {

			//no problem - update publish_response
			$req_tbl = new Application_Model_DbTable_Requests(Np_Db::master());
			$req_tbl->update(array('last_transaction' => 'Publish_response', 'status' => 0), 'id=' . $request['id']);
			//send to internal!
		}
		return TRUE;
	}

	/**
	 * Locks a transfer process 
	 * 
	 * @param type $lock - 1 is lock 0 is unlock
	 * @param type $id - id in the Requests DB
	 * @return type status (0/1)
	 */
	static protected function lockDBRow($lock, $id) {

		$tbl = new Application_Model_DbTable_Requests();
		$res = $tbl->update(array('cron_lock' => $lock), 'id=' . $id);
		return $res;
	}

	/**
	 * gets row from requests table by request id
	 * 
	 * @param type $reqId the request id 
	 * @return object DB RESPONSE 
	 */
	public function getRequestByID($reqId) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select();
		$select->where('request_id = ?', (string) $reqId)
			->order('id DESC');
		$result = $select->query()->fetch();
		return $result;
	}

	/**
	 * sets status = 0 to any "Check_response" requests that have timedout
	 * 
	 * @return int number of affected rows 
	 */
	static public function setTimeoutChecks($msg_type = "Check", $time = 1, $checkTransferTimeExists = false) {
//		$setTimeOutArray = array('Check'=>'30','Check_response'=>'30','Request'=>'30',);
		$dateInTimeStamp = time() - ($time * 60);
		$compareTime = Application_Model_General::getTimeInSqlFormat($dateInTimeStamp);

		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$update_arr = array('status' => 0);
		$where_arr = array(
			'last_transaction =?' => $msg_type,
			'last_requests_time < ?' => $compareTime,
			'status =?' => 1
		);

		if ($checkTransferTimeExists) {
			$where_arr[] = "transfer_time IS NOT NULL OR transfer_time = ''";
		}

		$res = $tbl->update($update_arr, $where_arr);
		return $res;
	}

	public function timersAggregate($dataArray) {
		return FALSE;
	}

	public function checkifpublish() {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$select = $tbl->select();
		$select->where('last_transaction= "Publish"')
			->where('status =  1');

		$result = $select->query();

		while ($row = $result->fetch()) {

			$goodpublish_result = Application_Model_Cron::checkifgoodpublish($row['request_id']);
		}
	}

	public function checkifgoodpublish($reqId) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		$select->where('request_id=?', $reqId)
			->where('message_type = "Publish_response" ')
			->where("reject_reason_code is NULL OR reject_reason_code = '' ");
		$result = $select->query();

		$rows = $result->fetchAll();
		foreach ($rows as $row) {
			$publish_update_result = Application_Model_Cron::updateGoodPublish($row['request_id'], substr($row['trx_no'], 0, 2));
		}
		return;
	}

	public function updateGoodPublish($reqId, $provider) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::master());
		$where[] = "request_id ='" . $reqId . "' AND from_provider='" . $provider . "'";
		$res = $tbl->update(array('last_transaction' => 'Publish_response'), $where);
		return $res;
	}

}

