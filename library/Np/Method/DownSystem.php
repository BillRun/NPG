<?php

/**
 * Np_Method_Up_System File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Up_System
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Up_System Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Up_System
 */
class Np_Method_DownSystem extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array and parent's construct
	 * accordingly sets parent's $type to "Return"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY 
	
	}

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		$xml->$msgType = "";
		return $xml;
	}

}
