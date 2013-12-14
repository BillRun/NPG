<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_DPFilter extends Zend_Form {

	public function init() {
		if (isset($_GET['request_id'])) {
			$request_id = htmlentities($_GET['request_id']);
		} else {
			$request_id = NULL;
		}
		if (!isset($_GET['date'])) {
			$today = htmlentities(date('Y-m-d'));
		} else {
			$today = htmlentities($_GET['date']);
		}
		if (!isset($_GET['time'])) {
			$time = '00:00:00';
		} else {
			$time = $_GET['time'];
		}
		if (isset($_GET['phone'])) {
			$phone = htmlentities($_GET['phone']);
		} else {
			$phone = NULL;
		}
		$this->setMethod('GET');
		$this->setAction('');
		$this->addElement(
			'text', 'request_id', array(
			'label' => 'request_id',
			'value' => $request_id
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
			'value' => $phone
			)
		);
		$this->addElement(
			'text', 'date', array(
			'label' => 'Date: YYYY-MM-DD For Example : 2011-11-20',
			'required' => true,
			'value' => $today
			)
		);
		$this->addElement(
			'text', 'time', array(
			'label' => 'Time: HH:MM:SS For Example : 16:20:00',
			'required' => true,
			'value' => $time,
			'invalidMessage' => 'Invalid date specified.',
			'formatLength' => 'long',
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
