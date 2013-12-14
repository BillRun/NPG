<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Zend_View_Helper_PrintTable extends Zend_View_Helper_Abstract {

	public function printTable($rows, $table = null, $link = false) {
		if (!is_array($rows) || !count($rows)) {
			print "Empty Results";
			return;
		}
		echo '<div><table>';
		echo '<tr style="border:solid 1px;font-weight:bold;">';
		foreach ($rows[0] as $key => $row) {
			echo '<td style="border:solid 1px;">' . $key . '</td>';
		}
		echo '</tr>';
		$baseUrl = Application_Model_General::getBaseUrl();
		foreach ($rows as $row) {
			echo '<tr style="border:solid 1px;">';
			if (is_array($row)) {

				foreach ($row as $index => $value) {
					if ($index=='id' && $link && $table=='Requests') {
						echo '<td style="border:solid 1px;"><a href="' . $baseUrl . '/debug/edit?table=Requests&id=' . (int)$value . '">' . $value  . '</td>';
					} else if ($value !== NULL) {
						echo '<td style="border:solid 1px;">' . $value . '</td>';
					} else {
						echo '<td style="border:solid 1px;">&nbsp;</td>';
					}
				}
			}
			echo '</tr>';
		}
		echo '</table></div>';
	}

}