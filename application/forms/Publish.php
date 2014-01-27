<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Publish extends Zend_Form {

	public function init() {
		$this->setMethod('POST');
		$this->setAction(Application_Model_General::getBaseUrl() . "/monitor/publish/");
		$this->setAttrib('class', 'publish-form');
		$this->addElement('hidden', 'id');
		$this->addElement('hidden', 'request_id');
		$this->addElement('hidden', 'from_provider');
		$this->addElement('hidden', 'to_provider');
		$this->addElement('hidden', 'transfer_time');
		$this->addElement('hidden', 'last_transaction');
		$this->addElement('hidden', 'phone_number');
		$executeOptions = array(
//			'ignore' => true,
			'label' => 'Send Publish',
		);
		$this->addElement('submit', 'submit', $executeOptions);

	}

}
