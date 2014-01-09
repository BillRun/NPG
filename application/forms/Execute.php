<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Execute extends Zend_Form {

	public function init() {
		$this->setMethod('POST');
		$this->setAction(Application_Model_General::getBaseUrl() . "/monitor/execute/");
		$this->setAttrib('class', 'execute-form');
		$this->addElement('hidden', 'id');
		$this->addElement('hidden', 'request_id');
		$this->addElement('hidden', 'from_provider');
		$executeOptions = array(
//			'ignore' => true,
			'label' => 'Send Execute',
		);
		$this->addElement('submit', 'submit', $executeOptions);

	}

}
