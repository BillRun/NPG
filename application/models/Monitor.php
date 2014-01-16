<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Model_Monitor {

	/**
	 * default limit of the queries
	 * @var type 
	 */
	protected $limit = 100;
	
	public function getAllLogData($table, $date = false, $phone = FALSE, $reqId = FALSE, 
		$stage = FALSE, $to = FALSE, $from = FALSE, $status = -1) {

		$db = Np_Db::slave();
		$select = $db->select()->from(array('t' => $table));

		$tomorrowcalc = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
		$tomorrow = date('Y-m-d', $tomorrowcalc);
		$tomorrow .= ' 00:00:00';

		$stageMapping = array(
			'Check' => array('Check', 'Check_response'),
			'Request' => array('Request', 'Request_response', 'Update', 'Update_response', 'Kd_update', 'Kd_update_response'),
			'Execute' => array('Execute', 'Execute_response'),
			'Publish' => array('Publish', 'Publish_response'),
		);
		switch ($table) {
			case 'Requests':
				if (!empty($date)) {
					$select->where('last_request_time >= ?', $date);
				}
				$select->where('last_request_time <= ?', $tomorrow);
				if ($stage) {
					$select->where('last_transaction IN (?)', $stageMapping[$stage]);
				}
				$request_id_field = "t.request_id";
				$filter_provider_table = 't';
				$number_field = "t.phone_number";
				break;
			case 'Transactions':
				$select->join(array('r' => "Requests"), 'r.request_id = t.request_id', array());
				if (!empty($date)) {
					$select->where('last_transaction_time >= ?', $date);
				}
				if ($stage) {
					$select->where('r.last_transaction IN (?)', $stageMapping[$stage]);
				}
				$select->where('last_transaction_time <= ?', $tomorrow);
				$request_id_field = "r.request_id";
				$filter_provider_table = 'r';
				$number_field = "r.phone_number";
				break;
			case 'Logs':
				$select->join(array('r' => "Requests"), 'r.phone_number = t.phone_number', array());
				if (!empty($date)) {
					$select->where('log_time >= ?', $date);
				}
				if ($stage) {
					$select->where('r.last_transaction IN (?)', $stageMapping[$stage]);
				}

				$select->where('log_time <= ?', $tomorrow);
				$number_field = "t.phone_number";
				$filter_provider_table = 'r';
				$request_id_field = "r.request_id";
				break;
		}

		if (!empty($phone)) {
			$select->where($number_field . ' =?', $phone);
		}

		if (!empty($reqId)) {
			$select->where($request_id_field . ' =?', $reqId);
		}
		
		if (!empty($to) && strtoupper($to) != 'NONE') {
			$select->where($filter_provider_table . '.to_provider = ?', strtoupper($to));
		}
		
		if (!empty($from) && strtoupper($from) != 'NONE') {
			$select->where($filter_provider_table . '.from_provider = ?', strtoupper($from));
		}

		if ($status != -1) {
			$select->where($filter_provider_table . '.status = ?', (int) $status);
		}
		$select->order("t.id DESC")->limit($this->limit);
//		print $select . "<br/ >";
		$rows = $db->query($select)->fetchAll();

		return $rows;
	}

	public function getRequestIdByNumber($number) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());

		$select = $tbl->select();

		$select
			->where('number =?', $number)->order('id DESC');

		$result = $select->query()->fetchObject();   //take the last one

		if ($result) {

			return $result->request_id;
		}
		return FALSE;
	}

	public function getTransActionsByRequestID($reqId = FALSE) {
		foreach ($reqId as $row => $val) {
			$request_ids[$row] = $reqId[$row]['request_id'];
		}
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		$select->where('request_id IN (?)', $request_ids)->order('id DESC');
		$result = $select->query()->fetchAll();   //take the last one
		if ($result) {
			return $result;
		}
		return null;
	}

	public function getReqLog($request_id) {
		$logPath = Application_Model_General::getRequestFilePath($request_id);
		if (file_exists($logPath)) {
			return file_get_contents($logPath);
		}
		return '';
	}

	public function getTableRow($table, $id) {
		$table_class = 'Application_Model_DbTable_' . ucfirst($table);
		if (class_exists($table_class)) {
			$table_object = new $table_class(Np_Db::slave());
			$select = $table_object->select();
			$select->where('id IN (?)', $id)->limit(1);
			$result = $select->query()->fetch();
			if ($result) {
				return $result;
			}
		}
		return false;
	}

	public function createForm($form, $table, $data) {
		$id = $data['id'];
		unset($data['id']);
		foreach ($data as $key => $val) {
			if ($key != 'id') {
				$form->addElement(
					'text', $key, array(
					'label' => $key,
					'value' => $val
					)
				);
			} else {
				$form->addElement(
					'hidden', $key, array(
					'value' => $val,
				));
			}
		}
		$form->addElement('submit', 'submit', array(
			'ignore' => true,
			'label' => 'submit',
		));
		$form->addElement(
			'hidden', 'table', array(
			'value' => $table,
		));
		$form->addElement(
			'hidden', 'id', array(
			'value' => $id,
		));
		$form->addElement(
			'hidden',
			'table', 
			array(
				'value' => $table,
			)
		);
		$form->setAttrib('class', 'request-form');
	}

	public function saveRow($row) {
		$id = $row['id'];
		unset($row['id']);
		$table = $row['table'];
		unset($row['table']);

		// date fields empty should be set to null, else they will set to 00-00-00 00:00:00
		$date_fields_array = array(
			'transfer_time',
			'disconnect_time',
			'connect_time',
		);
		foreach ($date_fields_array as $field) {
			if (empty($row[$field])) {
				$row[$field] = null;
			}
		}

		$table_class = 'Application_Model_DbTable_' . ucfirst($table);
		if (class_exists($table_class)) {
			$table_object = new $table_class(Np_Db::master());
			$ret = $table_object->update($row, array('id = ' . $id));
			if (!$ret) {
				print "Nothing update...";
			}
			sleep(1.5);
		}
	}

}
