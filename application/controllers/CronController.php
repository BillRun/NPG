<?php

/**
 * Controller for Cron Processes 
 * 
 * @package         ApplicationController
 * @subpackage      CronController
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
			$this->view->output_enabled = true;
		} else {
			$this->view->output_enabled = false;
		}
		// we make 3 process at different iteraion
		// we order the check publish before publish for scenario of force all (all on one time)
		// we don't use else in case we force all
		$this->view->transfer = array();
		$this->view->publish_check = array();
		$this->view->publish = array();
		$minute = date('i');
		if ($minute % 2 === 0 || $forceAll) {
			//first execute
			$this->view->transfer = Application_Model_Cron::makeChangeProvider();
		}
		$publish_verification_iteration = Application_Model_General::getSettings('publish-verification-iteration', 20);
		$hour = date('G'); // 24-hour format of an hour without leading zeros
		$dayofweek = date('w'); // Numeric representation of the day of the week, 0 (for Sunday) through 6 (for Saturday)
		$working_days = (bool) ($dayofweek >= 0 && $dayofweek <= 4 && $hour >= 8 && $hour < 23);
		$friday = (bool) ($dayofweek == 5 && $hour >= 8 && $hour < 15);
		if (($minute % $publish_verification_iteration == 0 && ($working_days || $friday)) || $forceAll) {
			// verify all requests return publish
			$this->view->publish_check = Application_Model_Cron::checkPublishResponseFromProviders();
		}
		if ($minute % 2 === 1 || $forceAll) {
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
	 * Check Publish verify if all providers return response
	 * in case some provider did not respond, the action will resend the publish
	 * 
	 * Check Publish Action - "http://SERVER/Cron/checkpublish"
	 * 
	 * @package ApplicationController
	 * @subpackage CronController
	 */
	public function checkpublishAction() {
		$run = new Application_Model_Cron();
		$run->checkPublish($this->getRequest()->getParams());
	}

	/**
	 * Gateway for Check Response Timeout Checks. http://SERVER/cron/checktimeout should be ran in daily cron .
	 * 
	 */
	public function checktimeoutAction() {

		$res = Application_Model_Cron::setTimeoutChecks('Check', 11);
		$res = Application_Model_Cron::setTimeoutChecks('Check_response', 30);
		$res = Application_Model_Cron::setTimeoutChecks('Request', 60, true);
		// 1500 => more than 48 hrs
		$res = Application_Model_Cron::setTimeoutChecks('Request_response', 3000);
		$res = Application_Model_Cron::setTimeoutChecks('Update', 60, true);
		$res = Application_Model_Cron::setTimeoutChecks('Update_response', 3000);
		$res = Application_Model_Cron::setTimeoutChecks('KD_Update', 3000);
		$res = Application_Model_Cron::setTimeoutChecks('KD_Update_response', 3000);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel_response', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Execute', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Execute_response', 60);
//		$res = Application_Model_Cron::setTimeoutChecks('Publish', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Publish_response', 5);
//		$res = Application_Model_Cron::setTimeoutChecks('Return', 10);
//		$res = Application_Model_Cron::setTimeoutChecks('Return_response', 10);
//		$res = Application_Model_Cron::setTimeoutChecks('Inquire_number', 1);
//		$res = Application_Model_Cron::setTimeoutChecks('Inquire_number_respon', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel_publish', 15);
//		$res = Application_Model_Cron::setTimeoutChecks('Cancel_publish_respon', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Up_system', 0);
//		$res = Application_Model_Cron::setTimeoutChecks('Down_system', 0);
	}

}
