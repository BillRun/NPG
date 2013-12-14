<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Model_Reports {

	/**
	 * Gets Number Of Rows In Statistics Tables 
	 */
	public static function getStatsPaging($table_name = FALSE) {
		if ($table_name != FALSE) {
			$underScore = strpos($table_name, '_');
			if ($underScore != FALSE) {

				$table_name = explode('_', $table_name);
				$table_name = ucfirst(strtolower($table_name[0])) . "_" . ucfirst(strtolower($table_name[1]));
			} else {
				$table_name = ucfirst(strtolower($table_name));
			}
		}
		$db = Np_Db::slave();
		$select = $db->select();
		$select->from($table_name, array())
				->columns(array('amount' => new Zend_Db_Expr('COUNT(*)')));
		$total = $db->fetchOne($select);

		return($total);
	}

	public static function getNoAckTransactions($date = FALSE, $start = FALSE, $end = FALSE) {
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		if ($date == FALSE) {
			$date = "2012-05-01" . " 00:00:00";
		} else {
			$date = $date . " 00:00:00";
		}
		$select = $tbl->select()->from(array('T' => 'Transactions'), "*")->distinct();
		$select->where('ack_code = ?', "Err")
				->where('last_transactions_time > ?', $date)
				->order('id DESC')->limit($start, $end);
		$result = $select->query()->fetchAll();   //take the last one
		if ($result) {
			foreach($result as $a_result => $val){
				$target = $result[$a_result]['target'];
				$trx_no = $result[$a_result]['trx_no'];
				unset($result[$a_result]['reject_reason_code']);
				unset($result[$a_result]['donor']);
				if($result[$a_result]['message_type'] == "Message Sent"){
					$send_mail="Mail Sent";
				}
				else{
					$send_mail="<a href='/np/emailsettings?trx_no=".$trx_no."&provider=".$target."'>Send Mail</a>";
				}
				$result[$a_result]['mail']=$send_mail;
			}
			return $result;
		}
		return null;
	}

	public static function getTransactionsByRejectReasonCode($rejectreason=FALSE, $thedate=FALSE, $limitstart=FALSE, $limitend=FALSE, $provider=FALSE, $to=FALSE) {
		if($thedate == FALSE){
			$thedate = "0000-00-00 00:00:00"; 
		}
		else{
			
			$thedate = strtotime($thedate);
			$thedate = date('Y-m-d H:i:s',$thedate);
		}
		
//		var_dump($thedate);
//		die;
		$tbl = new Application_Model_DbTable_Transactions(Np_Db::slave());
		$select = $tbl->select();
		
		if (isset($provider) && !empty($provider) && $provider != FALSE) {
			$provider = $provider."%";
			$select->where('trx_no LIKE ?', $provider);
		}
		if (isset($thedate) && !empty($thedate) && $thedate != FALSE) {
			$select->where('last_transactions_time > ?', $thedate);
		}
		if (isset($to) && !empty($to) && $to != FALSE) {
			$select->where('target =?', $to);
		}
		if ($rejectreason == FALSE) {
			return array();
		}
		if ($limitstart == FALSE) {
			$limitstart = 5;
		}
		if ($limitend == FALSE) {
			$limitend = 0;
		}
		if($limitend == $limitstart){
			$limitstart = 0 ; 
		}


		$select->where("reject_reason_code = ? ", $rejectreason)
				->limit($limitend, $limitstart);
		$results = $select->query()->fetchAll();


		return $results;
	}

	public static function getNumberOfPorts($port_type="IN") {
		if ($port_type == "IN") {
			$port_type = 'to_provider';
		} else {
			$port_type = 'from_provider';
		}
		$tbl = new Application_Model_DbTable_Requests(Np_Db::slave());

		$select = $tbl->select();
		$select->where("last_transaction = ? ", "Publish_response")
				->where($port_type . " = ? ", "GT");

		$results = $select->query()->fetchAll();

		return count($results);
	}

	
	public static function deleteProviderEmailRow($provider){
		$tbl = new Application_Model_DbTable_EmailSettings(Np_Db::slave());
		$where_arr = array('`providername` = ?' => $provider);
		$res = $tbl->delete($where_arr);
		return $res ;  
		
	}
}