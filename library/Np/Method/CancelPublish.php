<?php

/**
 * Np_Method_CancelPublish File
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelPublish
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_CancelPublish Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_CancelPublish
 */
class Np_Method_CancelPublish extends Np_Method {

	/**
	 * Constructor
	 * 
	 * calls parent constructor , sets type "CancelPublish"
	 * and places params in  body fields 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
		foreach ($options as $key => $value) {
			switch (ucwords(strtolower($key))) {
				case "Donor":
					$this->setBodyField($key, $value);
					break;
			}
		}
	}

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$xml->$msgType->donor = $this->getBodyField('DONOR');
		return $xml;
	}

}
