<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_DPFilter extends Zend_Form {

	public function init() {
		$this->setMethod('GET');
		$this->setAction('');
		$this->addElement(
			'text', 'request_id', array(
			'label' => 'Request Id',
//			'value' => $this->getValue('request_id'),
			)
		);
		$this->addElement(
			'text', 'phone', array(
			'label' => 'Phone',
			'required' => true,
			'validators' => array(
				'Alnum',
				array('regex', false, '/^[a-z]/i'),
				'int'
			),
			'filters' => array('StringTrim'),
			)
		);

		$msgTypeOptions = array(
			'label' => 'Stage',
			'required' => true,
			'multioptions' => array(
				'All' => 'All',
				'Check' => 'Check',
				'Request' => 'Request',
				'Execute' => 'Execute',
				'Publish' => 'Publish',
			),
		);

		$this->addElement('select', 'stage', $msgTypeOptions);

		$this->addElement(
			'text', 'date', array(
			'label' => 'Date (YYYY-MM-DD)',
			'required' => true,
			)
		);
		$this->addElement(
			'text', 'time', array(
			'label' => 'Time (HH:MM:SS)',
			'required' => true,
			'invalidMessage' => 'Invalid date specified.',
//			'formatLength' => 'long',
			'filters' => array('StringTrim')
			)
		);

		$providers = Application_Model_General::getProviderArray();
		$currentProvider = Application_Model_General::getSettings('InternalProvider');
		if (($key = array_search($currentProvider, $providers)) !== FALSE) {
			unset($providers[$key]);
		}
		array_unshift($providers, 'None', $currentProvider);
		$toOptions = array(
			'label' => 'From provider',
			'multioptions' => array_combine($providers, $providers),
		);
		$this->addElement('select', 'from', $toOptions);
		$toOptions['label'] = 'To provider';
		$this->addElement('select', 'to', $toOptions);

		$statusOptions = array(
			'label' => 'Status',
			'multioptions' => array(-1 => "All", 1 => "Active", 0 => "Inactive"),
		);
		$this->addElement('select', 'status', $statusOptions);

		// Add the submit button
		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'label' => 'Search',
			'class' => 'request-search-button',
		));
	}

}
