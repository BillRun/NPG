<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Request extends Zend_Form {

	public function init() {

		// Set the method for the display form to POST
		$this->setMethod('POST');
		$this->setAction("/monitor/send/");

		$processTypeOptions = array(
			'label' => 'PROCESS_TYPE',
			'required' => true,
			'multioptions' => array(
				'PORT' => 'PORT',
				'RETURN' => 'RETURN',
				'QUERY' => 'QUERY',
				'MAINT' => 'MAINT'
			),
		);
		$this->addElement('select', 'PROCESS_TYPE', $processTypeOptions);

		$msgTypeOptions = array(
			'label' => 'MSG_TYPE',
			'required' => true,
			'multioptions' => array(
				'Check' => 'Check',
				'Check_response' => 'Check_response',
				'Request' => 'Request',
				'Request_response' => 'Request_response',
				'Update' => 'Update',
				'Update_response' => 'Update_response',
				'Cancel' => 'Cancel',
				'KD_Update' => 'KD_Update',
				'KD_update_response' => 'KD_update_response',
				'Execute' => 'Execute',
				'Execute_response' => 'Execute_response',
				'Publish' => 'Publish',
				'Publish_response' => 'Publish_response',
				'Cancel_publish' => 'Cancel_publish',
				'Cancel_publish_response' => 'Cancel_publish_response',
				'Return' => 'Return',
				'Return_response' => 'Return_response',
			),
		);

		$this->addElement('select', 'MSG_TYPE', $msgTypeOptions);

		$numberOptions = array(
			'label' => 'Number',
			'required' => true,
			'validators' => array('Int', array('StringLength', FALSE, array(10, 10))),
			'value' => ''
		);
		$this->addElement('text', 'NUMBER', $numberOptions);
		$d = new Zend_Date(null, null, 'he_IL');
		$dateOptions = array(
			'label' => 'PORT TIME',
			'id' => 'datetimepicker',
			'required' => true,
//			'validators' => array('Int'),
			'value' => $d->toString('YYYY-MM-dd HH:mm:ss', 'he_IL'),
		);
		$this->addElement('text', 'porttime', $dateOptions);
		$providers = array(
				'GT' => 'Golan Telecom',
				'PL' => 'Pelephone',
//				'PR' => 'Partner Cell',
//				'PM' => 'Partner Mapa',
//				'CL' => 'Cellcom Cell',
//				'CM' => 'Cellcom Mapa',
//				'MI' => 'Mirs',
//				'BZ' => 'Bezeq',
//				'HT' => 'Hot',
//				'KD' => 'DefenseMinistry',
//				'KZ' => 'Kavei Zahav',
//				'NV' => 'Barak/Globecall Netvision',
//				'EX' => 'Exphone'
		);
		$currentProvider = Application_Model_General::getSettings('InternalProvider');
		unset($providers[$currentProvider]);
		$toOptions = array(
			'label' => 'TO',
			'multioptions' => $providers,
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
			'label' => 'submit',
		);
		$this->addElement('submit', 'submit', $submitOptions);
	}

}
