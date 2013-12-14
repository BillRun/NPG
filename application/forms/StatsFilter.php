<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_StatsFilter extends Zend_Form {

	public function init() {


		$this->setMethod('GET');
		$this->setAction('');
		if (isset($_GET)) {
			extract($_GET);
			if (!isset($date)) {
				$date = array(NULL);
			}
			if (!isset($provider)) {
				$provider = array(NULL);
			}
			if (!isset($recipient)) {
				$recipient = array(NULL);
			}
		}



//		$this->addElement(
//			'date',
//			'date',
//			array(
//				'label'          => 'Date: YYYY-MM-DD For Example : 2011-11-20',
//				'required'       => true,
//				'value'				 => '2011-11-20'
//			)
//		);
		$this->addElement(
				'text', 'date', array(
			'label' => 'Date: Y-M-D For Example : 12-02-22',
			'required' => true,
			'invalidMessage' => 'Invalid date specified.',
			'formatLength' => 'long',
				)
		);

//                'multiOptions'			 => Application_Model_General::getAllAvailableStatisticsTimes(),
//                                                                        'value'=>$date,                    

		$this->addElement(
				'text', 'provider', array(
			'label' => 'provider who initiated the request',
			'required' => true,
			'formatLength' => 'long',
				)
		);
//		'multiOptions'				 => Application_Model_General::getProviderArray(),
//				'value'   => $provider,
		$this->addElement(
			'text', 'recipient', array(
			'label' => 'the request recipient',
			'required' => true,
			'formatLength' => 'long',
				)
		);


		// Add the submit button
		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'label' => 'submit',
		));
	}

}
