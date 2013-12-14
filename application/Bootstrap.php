<?php
/**
 * @license    GNU Affero Public License version 3 or later; see LICENSE
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	public function _initAutoLoad() {
		$autoLoader = Zend_Loader_Autoloader::getInstance();
		$autoLoader->registerNamespace('Np_')
				->setFallbackAutoloader(true)
				->suppressNotFoundWarnings(true);
		return $autoLoader;
	}

	/**
	 * override the default Zend database layer with master-slave configuration
	 */
	public function _initDatabase() {
		$config = $this->getApplication()->getOption('resources');
		$dbArrays = array();
		foreach ($config['db'] as $name => $dbConf) {
			// Set up database
			$db = Zend_Db::factory($dbConf['adapter'], $dbConf['params']);
			$db->query("SET NAMES 'utf8'");
			$dbArrays[$name] = $db;

			if ((boolean) $dbConf['default']) {
				Zend_Db_Table::setDefaultAdapter($db);
			}
			unset($db);
		}

		Zend_Registry::set("db", $dbArrays);
	}

}
