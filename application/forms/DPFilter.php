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
			'label' => 'request_id',
//			'value' => $this->getValue('request_id'),
			)
		);
		$this->addElement(
			'text', 'phone', array(
			'label' => 'phone',
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
			'label' => 'Date: YYYY-MM-DD For Example : 2013-11-20',
			'required' => true,
			)
		);
		$this->addElement(
			'text', 'time', array(
			'label' => 'Time: HH:MM:SS For Example : 16:20:00',
			'required' => true,
			'invalidMessage' => 'Invalid date specified.',
//			'formatLength' => 'long',
			'filters' => array('StringTrim')
			)
		);
		// Add the submit button
		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'label' => 'submit',
		));
	}

}
