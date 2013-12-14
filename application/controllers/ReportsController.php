<?php

/**
 * Controller for Reports 
 * 
 * @package ApplicationController
 * @subpackage ReportsController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Reports Controller Class
 * 
 * @package ApplicationController
 * @subpackage ReportsController
 */
class ReportsController extends Zend_Controller_Action {

	public function indexAction() {
		$this->view->headerMenu = Application_Model_General::$menu;
		$this->view->reportsMenu = "<a href='#process_type'>Process Type</a> / <a href='#noack'>Failed Transactions</a> / <a href='#timers'>Timeouts</a> / <a href='#shutdown'>Shutdowns</a> / <a href='#rejectreason'>Reject Reason</a>";
		$this->view->backToTop = "<a href='#top'>Back To Top</a>";
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 0;
		}
		$this->view->portIns = Application_Model_Reports::getNumberOfPorts();
		$this->view->portOut = Application_Model_Reports::getNumberOfPorts("OUT");
		
		$this->view->adjacents_Activity_Process = 3;
		$this->view->total_pages_Activity_Process = Application_Model_Reports::getStatsPaging("Activity_Process");
		$this->view->limit_Activity_Process = $limit = 5;   //how many items to show per page
		$this->view->pagination_Activity_Process = $page;
		$this->view->adjacents_Activity_Timers = 3;
		$this->view->total_pages_Activity_Timers = Application_Model_Reports::getStatsPaging("Activity_Timers");
		$this->view->limit_Activity_Timers = 5;   //how many items to show per page
		$this->view->pagination_Activity_Timers = $page;
		$this->view->adjacents_Shutdown = 3;
		$this->view->total_pages_Shutdown = Application_Model_Reports::getStatsPaging("Shutdown");
		$this->view->limit_Shutdown = 5;   //how many items to show per page
		$this->view->pagination_Shutdown = $page;
		$this->view->adjacents_Transactions = 3;
		$this->view->total_pages_Transactions = Application_Model_Reports::getStatsPaging("Transactions");
		$this->view->limit_Transactions = 5;   //how many items to show per page
		$this->view->pagination_Transactions = $page;
		if (isset($_GET)) {
			/**
			 * 
			 */
			if (!isset($_GET['date']) || empty($_GET['date'])) {
				$date = "00-00-00 00:00:00";
			} else {
				$date = $_GET['date'];
			}

			if (!isset($_GET['provider']) || (!$_GET['provider'] || empty($_GET['provider']))) {
				$provider = FALSE;
			} else {
				$provider = $_GET['provider'];
			}
			if (!isset($_GET['recipient']) || ($_GET['recipient'] == FALSE || empty($_GET['recipient']))) {
				$recipient = FALSE;
			} else {  
				$recipient = $_GET['recipient'];
			}
			
			$start_limit_array = $this->getPaging("Activity_Process", $page, 5);
			
			$this->view->processTypeReports = Application_Model_Agg::getProcessTypeRows($date, $provider, $recipient, $start_limit_array['limit'], $start_limit_array['start']);

			$res = Application_Model_Agg::getTimersActivityRows($date, $provider, $recipient);
			$this->view->timersActivity = $res;

			$this->view->shutDownReports = Application_Model_General::getShutDowns();
			$this->view->noAck = Application_Model_Reports::getNoAckTransactions($date, $start_limit_array['limit'], $start_limit_array['start']);
		} else {
			$start_limit_array = $this->getPaging("Activity_Process", 0, 5);

			$activity_start_limit_array = $this->getPaging("Activity_Timers", 0);

			$res = Application_Model_Agg::getTimersActivityRows(FALSE, FALSE, FALSE, $activity_start_limit_array['limit'], $activity_start_limit_array['start']);
			$this->view->timersActivity = $res;
			$this->view->processTypeReports = Application_Model_Agg::getProcessTypeRows(FALSE, FALSE, FALSE, $start_limit_array['limit'], $start_limit_array['start']);

			$this->view->shutDownReports = Application_Model_General::getShutDowns();
			$this->view->noAck = Application_Model_Reports::getNoAckTransactions(FALSE, $start_limit_array['limit'], $start_limit_array['start']);
		}
		$form = new Application_Form_StatsFilter();
		$this->view->form = $form;
		
		
		if (isset($_GET['page']) && $_GET['page'] != FALSE) {

			$page = $_GET['page'];
		} else {
			$page = 0;
			
		}
		
		
		if (isset($_POST['reject_reason_code'])) {
			$reject_reason_code = $_POST['reject_reason_code'];
		} else {
			$reject_reason_code = "";
		}
		if(isset($_GET['rejectReason'])){
			$reject_reason_code = $_GET['rejectReason'] ;
		}
		if(isset($_POST['date'])){
			$date = $_POST['date'] ;  
		}
		elseif(isset($_GET['date'])){
			$date = $_GET['date'] ;
		}
		if(isset($_POST['provider'])){
			$provider = $_POST['provider'] ;  
		}
		elseif(isset($_GET['provider'])){
			$provider = $_GET['provider'] ;
		}
		$adjacents = 3;
		$limit = 5;
		$start_limit_array = $this->getPaging(FALSE, $page, $limit);
		extract($start_limit_array);	
		
		$res = Application_Model_Reports::getTransactionsByRejectReasonCode($reject_reason_code,$date,FALSE,FALSE,$provider,$recipient);
		$total_pages = count($res);	
		
		
		if ($page != 0) {
			
			$res = Application_Model_Reports::getTransactionsByRejectReasonCode($reject_reason_code, $date, $start, $limit ,$provider,$recipient);
			
			
		} else {
			$res = Application_Model_Reports::getTransactionsByRejectReasonCode($reject_reason_code, $date, 0, 5,$provider,$recipient);
		
			
		}
		
		
		$this->view->total_pages_rejectReasonCount = $total_pages;
		$this->view->limit_rejectReasonCount = 5; //$start_limit_array['start'];

		$this->adjacents_rejectReasonCount = 3;

		$this->view->pagination_rejectReasonCount = ($page * $limit);
		$this->view->rejectReasonTable = $res;
		$form = new Application_Form_RejectForm();
		$this->view->rejectform = $form;
	}
		
	 

	function getPaging($table_name = FALSE, $page = FALSE, $limit = FALSE) {

		if ($page == FALSE) {
			$page = 0;
		}

		if ($page)
			$start = ($page - 1) * $limit; //first item to display on this page
		
		else
			$start = 0;  //if no page var is given, set start to 0

		$return_value = array("start" => $start, "limit" => $limit);
		return $return_value;
	} 

	

}

