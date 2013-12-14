<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Model_Debug {

	public function getDataPaging($date = FALSE, $stam = FALSE, $stam2 = FALSE, $stam3 = FALSE) {
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());
		$date = date('Y-m-d') . " 00:00:00";
		$select = $tbl->select();
		$select->from(array('r' => 'Requests'), array('r.id'))
			->where('last_requests_time > ?', $date)
			->order('r.id DESC');
		$result = $select->query()->fetchAll();   //take the last one

		if ($result) {
			return $result;
		}

		return FALSE;
	}

	public function getAllLogData($table, $date = false, $phone = FALSE, $reqId = FALSE) {

		$db = Np_Db::slave();
		$select = $db->select()->from(array('t' => $table));

		$tomorrowcalc = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
		$tomorrow = date('Y-m-d', $tomorrowcalc);
		$tomorrow .= ' 00:00:00';

		switch ($table) {
			case 'Requests':
				if (!empty($date)) {
					$select->where('last_' . strtolower($table) . '_time >= ?', $date);
				}
				$select->where('last_' . strtolower($table) . '_time <= ?', $tomorrow);
				$request_id_field = "t.request_id";
				$number_field = "t.number";
				break;
			case 'Transactions':
				$select->join(array('r' => "Requests"), 'r.request_id = t.request_id', array());
				if (!empty($date)) {
					$select->where('last_' . strtolower($table) . '_time >= ?', $date);
				}
				$select->where('last_' . strtolower($table) . '_time <= ?', $tomorrow);
				$request_id_field = "r.request_id";
				$number_field = "r.number";
				break;
			case 'Logs':
				$select->join(array('r' => "Requests"), 'r.number = t.number', array());
				if (!empty($date)) {
					$select->where('time >= ?', $date);
				}
				$select->where('time <= ?', $tomorrow);
				$number_field = "t.number";

				$request_id_field = "r.request_id";
				break;
		}

		if (!empty($phone)) {
			$select->where($number_field . ' =?', $phone);
		}

		if (!empty($reqId)) {
			$select->where($request_id_field . ' =?', $reqId);
		}
		$select->order("t.id DESC")->limit(1000);
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
		$form->addElement(
			'hidden', 'table', array(
			'value' => $table,
		));
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
		foreach($date_fields_array as $field) {
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
