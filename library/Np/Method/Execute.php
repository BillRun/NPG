<?php

/**
 * Np_Method_Execute File
 * 
 * @package Np_Method
 * @subpackage Np_Method_Execute
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Method_Execute Class Definition
 * 
 * @package Np_Method
 * @subpackage Np_Method_Execute
 */
class Np_Method_Execute extends Np_Method {

	/**
	 * Constructor
	 * 
	 * receives options array and sets into body array accordingly
	 * sets parent's $type to "execute"
	 * 
	 * @param array $options 
	 */
	protected function __construct($options) {
		parent::__construct($options);

		//SET BODY - there is no Body in addition to Method
	}

	public function createXml() {
		$xml = parent::createXml();
		$msgType = $this->getHeaderField('MSG_TYPE');
		// TODO: check with spec
		$xml->$msgType = "";
		return $xml;
	}

}

