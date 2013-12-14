<?php

/**
 * Np_Db class handle master-slave replication
 * 
 * 
 * @package Np_Db
 * @subpackage Np_Db
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

/**
 * Np_Db Class Definition
 * 
 * @package Np_Db
 * @subpackage Np_Db
 */
Class Np_Db {

	/**
	 * method to retreive the master adapter of the replication
	 * 
	 * @return Zend_Db_Adapter Object
	 */
	public static function master() {
		$dbAdapters = Zend_Registry::get('db');
		return $dbAdapters["master"];
	}

	/**
	 * method to retreive the slave adapter of the replication
	 * 
	 * @return Zend_Db_Adapter Object
	 */
	public static function slave() {
		$dbAdapters = Zend_Registry::get('db');
		return $dbAdapters["slave"];
	}

}
