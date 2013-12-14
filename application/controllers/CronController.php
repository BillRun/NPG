<?php

/**
 * Controller for Cron Processes 
 * 
 * @package ApplicationController
 * @subpackage CronController
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Cron Controller Class
 * 
 * @package ApplicationController
 * @subpackage CronController
 */
class CronController extends Zend_Controller_Action {

	/**
	 * Cron Controller Class
	 * Index Action - "http://SERVER/Cron"
	 * calls the modle to select the requests to Transfer and 
	 * opens process for each one of the requests   
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function indexAction() {
		$disabled_output = $this->getRequest()->getParam('no-output');
		$forceAll = $this->getRequest()->getParam('force-all');
		if (!isset($disabled_output) || !$disabled_output) {
			$output_enabled = true;
		} else {
			$output_enabled = false;
		}
		$this->view->output_enabled = $output_enabled;
		// move to model and make it configurable
		$stage = date('i') % 6;
		// we make 3 process at different iteraion
		// we order the check publish before publish for scenario of force all (all on one time)
		// we don't use else in case we force all
		$this->view->transfer = array();
		$this->view->publish_check = array();
		$this->view->publish = array();
		if ($stage == 0 || $stage == 2 || $stage == 4 || $forceAll) {
			//first execute
			$this->view->transfer = Application_Model_Cron::makeChangeProvider();
		} 
		else if (date('i') % 10 == 9 || $forceAll) {
			// verify all requests return publish
			$this->view->publish_check = Application_Model_Cron::checkPublishResponseFromProviders();
		}
		else if ($stage == 1 || $stage == 3 || $stage == 5 || $forceAll) {
			// publish
			$this->view->publish = Application_Model_Cron::publishChangeProvider();
		}
	}

	/**
	 * Transfer Request Commit
	 * 
	 * Transfer Action - "http://SERVER/Cron/transfer"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function transferAction() {
		$run = new Application_Model_Cron();
		$run->executeTransfer($this->getRequest()->getParams());
	}

	/**
	 * Transfer out Request by internal provider
	 * 
	 * Transfer Action - "http://SERVER/cron/transferout"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function transferoutAction() {
		$run = new Application_Model_Cron();
		$run->executeTransferOut($this->getRequest()->getParams());
	}

	
	
	/**
	 * Publish the Transfer
	 * 
	 * Publish Action - "http://SERVER/Cron/publish"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function publishAction() {
		$run = new Application_Model_Cron();
		$run->executePublish($this->getRequest()->getParams());
	}

	/**
	 * Revert Number Transfer
	 * 
	 * Revert Action - "http://SERVER/Cron/revert"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function revertAction() {
		$run = new Application_Model_Cron();
		$run->revertPublish($this->getRequest()->getParams());
	}

	/**
	 * Check Publish
	 * 
	 * Check Publish Action - "http://SERVER/Cron/checkpublish"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function checkpublishAction() {
		$run = new Application_Model_Cron();
		if ($this->getRequest()->getParam('forceAll') == 1) {
			$forcePublishAll = TRUE;
		} else {
			$forcePublishAll = FALSE;
		}
		$run->checkPublish($this->getRequest()->getParams(), $forcePublishAll);
	}

	/**
	 * Agg Publish
	 * 
	 * Aggregate Action - "http://SERVER/Cron/checkpublish"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function aggAction() {
		$parentProcessArray = Application_Model_Agg::getParentProcessArray();
		$processTypes = Application_Model_Agg::$processTypes;

		foreach ($parentProcessArray as $key => $val) {
			$msgType = $parentProcessArray[$key]['last_transaction'];
			$rejectReasonCode = Application_Model_Agg::getRejectReasonCodeByRequestID($parentProcessArray[$key]['request_id']);
			$processType = Application_Model_General::getProcessType($msgType);
			$from = $parentProcessArray[$key]['from_provider'];
			$to = $parentProcessArray[$key]['to_provider'];
			$status = Application_Model_Agg::getParentProcessStatus($msgType, $rejectReasonCode);
			$res = Application_Model_Agg::validateProcessTypeReportsRow($processType, $status, $from, $to);
			if (empty($res) || $res == FALSE) {
				Application_Model_Agg::InsertToProcessTypeReports($processType, $status, $from, $to);

				
			} else {
				Application_Model_Agg::UpdateProcessTypeReports($processType, $status, $from, $to);
			}

		}
	}

	/**
	 * Gateway for Check Response Timeout Checks. http://SERVER/cron/checktimeout should be ran in daily cron .
	 * 
	 */
	public function checktimeoutAction() {
			
		$res = Application_Model_Cron::setTimeoutChecks('Check', 11);
		$res = Application_Model_Cron::setTimeoutChecks('Check_response', 30);
		$res = Application_Model_Cron::setTimeoutChecks('Request', 60, true);
		// 1500 => more than 24 hrs
		$res = Application_Model_Cron::setTimeoutChecks('Request_response',1500);
		$res = Application_Model_Cron::setTimeoutChecks('Update', 60, true);
		$res = Application_Model_Cron::setTimeoutChecks('Update_response', 1500);
		$res = Application_Model_Cron::setTimeoutChecks('Cancel', 15);
		$res = Application_Model_Cron::setTimeoutChecks('Cancel_response', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Execute', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Execute_response', 60);
//		$res = Application_Model_Cron::setTimeoutChecks('Publish', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Publish_response', 5);
		$res = Application_Model_Cron::setTimeoutChecks('Return', 10);
//		$res = Application_Model_Cron::setTimeoutChecks('Return_response', 10);
//		$res = Application_Model_Cron::setTimeoutChecks('Inquire_number', 1);
//		$res = Application_Model_Cron::setTimeoutChecks('Inquire_number_respon', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel_publish', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel_publish_respon', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Up_system', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Down_system', 0);
	}

	/**
	 * Action to trigger timers aggregate
	 * 
	 */
	public function aggregateTimersAction() {
		$timerActivityRows = Application_Model_Agg::getTimersActivityRows();

		// gets all data from Activity_Timers

		foreach ($timerActivityRows as $vals) {
			// check if timer exists with specific obligated operator , waiting operator and in specific date .
		}
	}

	public function checkifpublishAction() {
		$result = Application_Model_Cron::checkifpublish();
		return $result;
	}

	public function testAction() {
		die('ASD');
	}

}
