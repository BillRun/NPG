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
class EmailSettingsController extends Zend_Controller_Action {

	public function indexAction() {
		$this->view->headerMenu = Application_Model_General::$menu;
		$ProviderEmailsArray = Application_Model_General::getAllProviderEmails();
		$this->view->provider_emails = $ProviderEmailsArray;
		if(isset($_GET['deleteProvider'])){
			$result = Application_Model_Reports::deleteProviderEmailRow($_GET['deleteProvider']);
			if($result !=  FALSE){
				echo "<h2>Provider".$_GET['deleteProvider']." Was Deleted </h2>";
				unset($_GET['deleteProvider']);
				header('Location: /np/emailsettings');
			}
			
		}
		
		
		if (isset($_POST['provider']) && $_POST['provider'] != FALSE
				&& isset($_POST['email']) && $_POST['email'] != FALSE) {
			$result = Application_Model_General::setProviderEmail($_POST['provider'], $_POST['email']);
			
			if($result != FALSE){
				unset($_GET['deleteProvider']);
				header('Location: /np/emailsettings');
			}
			else{
				unset($_GET['deleteProvider']);
				header('Location: /np/emailsettings');
			}
			
		}
		if (isset($_POST['email_format']) && $_POST['email_format'] != FALSE) {
			Application_Model_General::setEmailFormat($_POST['email_format']);
		}
		if (isset($_GET['provider']) && isset($_GET['trx_no'])) {

			$emailSettigns = array();
			$tr = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $emailSettigns);
			Zend_Mail::setDefaultTransport($tr);
			
			$sendto = Application_Model_General::getProviderEmail($_GET['provider']);
			
			$mail = new Zend_Mail();
			$mail->setBodyText(Application_Model_General::getEmailFormatWithPlaceHolders($_GET['provider'], $_GET['trx_no']));
			$mail->setFrom('npg', 'NP Coordinator');
			$mail->addTo($sendto, 'Provider');
			$mail->setSubject("NPG Error.");
			$email_result = $mail->send();
			if ($email_result == TRUE) {
				$modify = Application_Model_General::modifySentRow($_GET['trx_no']);
			header('Location:/np/reports/#noack');	
				
				echo "<h2>Email Successfully Sent to Provider!</h2>";
			} else {
				
				die("<h2>Email Error :</h2><br> ".$email_result);
			}
		}
		$this->view->form = new Application_Form_EmailSettings();
		$this->view->emailFormat = new Application_Form_EmailFormat();
	}

}

