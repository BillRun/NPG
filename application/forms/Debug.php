<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Debug extends Zend_Form {

	public function init() {
		//check if url is present in GET. if it is posts it to the url field
		//in the form
		if (isset($_GET['url'])) {
			$getURL = $_GET['url'];
		} else {
			$getURL = '';
		}
		date_default_timezone_set('Asia/Jerusalem');
		// Set the method for the display form to POST
		$this->setMethod('POST');
		//$this->setAction('google.com');

		$this->addElement('select', 'PROCESS_TYPE', array(
			'label' => 'PROCESS_TYPE',
			'required' => true,
			'multioptions' => array('PORT' => 'PORT',
				'RETURN' => 'RETURN',
				'QUERY' => 'QUERY',
				'MAINT' => 'MAINT'
			)
			)
		);
		$this->addElement('select', 'MSG_TYPE', array(
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
			)
			)
		);

		$this->addElement('text', 'NUMBER', array(
			'label' => 'Number',
			'required' => true,
			'validators' => array('Int', array('StringLength', FALSE, array(10, 10))),
			'value' => '0528000000'
			)
		);
		$this->addElement('text', 'porttime', array(
			'label' => 'PORT TIME - enter time for number transfer ( in minutes from now )',
			'required' => true,
			'validators' => array('Int'),
			'value' => '1'
			)
		);
		$this->addElement('select', 'FROM', array(
			'label' => 'FROM',
			'multioptions' => array(
				'GT' => 'Golan Telecom',
				'PL' => 'Pelephone',
				'PR' => 'Partner Cell',
				'PM' => 'Partner Mapa',
				'CL' => 'Cellcom Cell',
				'CM' => 'Cellcom Mapa',
				'MI' => 'Mirs',
				'BZ' => 'Bezeq',
				'HT' => 'Hot',
				'KD' => 'DefenseMinistry',
				'KZ' => 'Kavei Zahav',
				'NV' => 'Barak/Globecall Netvision',
				'EX' => 'Exphone'
			)
			)
		);
		$this->addElement('select', 'TO', array(
			'label' => 'TO',
			'multioptions' => array(
				'GT' => 'Golan Telecom',
				'PL' => 'Pelephone',
				'PR' => 'Partner Cell',
				'PM' => 'Partner Mapa',
				'CL' => 'Cellcom Cell',
				'CM' => 'Cellcom Mapa',
				'MI' => 'Mirs',
				'BZ' => 'Bezeq',
				'HT' => 'Hot',
				'KD' => 'DefenseMinistry',
				'KZ' => 'Kavei Zahav',
				'NV' => 'Barak/Globecall Netvision',
				'EX' => 'Exphone'
			)
			)
		);
		$this->addElement('text', 'Date and Time', array(
			'label' => 'Date and Time',
			'required' => true,
			'value' => date('y/m/d:h/i/s'),
			'disabled' => 'true'
			)
		);
		$this->addElement('submit', 'submit', array(
			'ignore' => true,
			'label' => 'submit',
		));
	}

}
