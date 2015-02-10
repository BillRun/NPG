<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Zend_View_Helper_PrintTable extends Zend_View_Helper_Abstract {

	public function printTable($rows, $columns = false, $table = null, $link = false) {
		if (!is_array($rows) || !count($rows)) {
			print "Empty Results";
			return;
		}
		echo '<div><table class="monitor ' . strtolower($table) . '">';
		$this->printTableHeader($rows, $columns);
		$this->printTableBody($rows, $columns, $table, $link);
		echo '</table></div>';
	}

	protected function printTableHeader($rows, $columns) {
		echo '<tr style="border:solid 1px;font-weight:bold;">';

		if (is_array($columns)) {
			foreach ($columns as $key => $label) {
				echo '<th style="border:solid 1px;">' . $label . '</th>';
			}
		} else {
			foreach ($rows[0] as $key => $row) {
				echo '<th style="border:solid 1px;">' . $key . '</th>';
			}
		}
		echo '</tr>';
	}

	protected function printTableBody($rows, $columns, $table = null, $link = false) {
		foreach ($rows as $row) {
			echo '<tr style="border:solid 1px;">';
			if (is_array($row)) {
				if (is_array($columns)) {
					foreach ($columns as $field => $label) {
						$value = $row[$field];
						$this->printTableRow($field, $value, $table, $link);
					}
				} else {
					foreach ($row as $field => $value) {
						$this->printTableRow($field, $value, $table, $link);
					}
				}
			}
			echo '</tr>';
		}
	}

	protected function printTableRow($field, $value, $table, $link = false) {
		$baseUrl = Application_Model_General::getBaseUrl();
		if ($field == 'id' && $link && $table == 'Requests') {
			echo '<td style="border:solid 1px;"><a href="' . $baseUrl . '/monitor/edit?table=Requests&id=' . (int) $value . '">' . $value . '</td>';
		} else if ($value !== NULL) {
			echo '<td style="border:solid 1px;">' . $value . '</td>';
		} else {
			echo '<td style="border:solid 1px;">&nbsp;</td>';
		}
	}

}
