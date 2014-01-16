<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Request extends Zend_Form {

	public function init() {

		// Set the method for the display form to POST
		$this->setMethod('POST');
		$this->setAction(Application_Model_General::getBaseUrl() . "/monitor/send/");
		$this->setAttrib('class', 'request-form');

		$msgTypeOptions = array(
			'label' => 'Message',
			'required' => true,
			'multioptions' => array(
				'Check' => 'Check',
				'Request' => 'Request',
				'Update' => 'Update',
				'Cancel' => 'Cancel',
				'Inquire_number' => 'Inquire_number',
				'KD_Update' => 'KD_Update',
			),
		);

		$this->addElement('select', 'MSG_TYPE', $msgTypeOptions);

		$numberOptions = array(
			'label' => 'Phone number',
			'required' => true,
			'validators' => array('Int', array('StringLength', FALSE, array(10, 10))),
			'value' => ''
		);
		$this->addElement('text', 'NUMBER', $numberOptions);
		$d = new Zend_Date(null, null, Application_Model_General::getLocale(null, false));
		$dateOptions = array(
			'label' => 'PORT TIME',
			'id' => 'datetimepicker',
			'required' => true,
//			'validators' => array('Int'),
			'value' => $d->toString('YYYY-MM-dd HH:mm:ss', Application_Model_General::getLocale(null, false)),
		);
		$this->addElement('text', 'porttime', $dateOptions);
		$providers = Application_Model_General::getProviderArray();
		$currentProvider = Application_Model_General::getSettings('InternalProvider');
		if (($key = array_search($currentProvider, $providers)) !== FALSE) {
			unset($providers[$key]);
		}
		$toOptions = array(
			'label' => 'TO',
			'multioptions' => array_combine($providers, $providers),
		);
		$this->addElement('select', 'TO', $toOptions);
		$dateTimeLabelOptions = array(
			'label' => 'Date and Time',
			'required' => true,
			'value' => date('y/m/d:h/i/s'),
			'disabled' => 'true'
		);
		$this->addElement('text', 'Date and Time', $dateTimeLabelOptions);
		$submitOptions = array(
			'ignore' => true,
			'label' => 'Send',
		);
		$this->addElement('submit', 'submit', $submitOptions);
	}

}
