<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Zend_View_Helper_PrintTableReportsShutdown extends Zend_View_Helper_Abstract {

	public function printTableReportsShutdown($rows) {
		if (is_array($rows) && count($rows)) {
			
			echo '<div><table>';
			echo '<tr style="border:solid 1px;font-weight:bold;">';
			if(isset($rows[0])){
			foreach ($rows[0] as $key => $row) {
				echo '<td style="border:solid 1px;">' . $key . '</td>';
			}
			}
			else{
			foreach ($rows as $key => $row) {
				echo '<td style="border:solid 1px;">' . $key . '</td>';
			}	
			}
			echo '</tr>';
				echo '<tr style="border:solid 1px;">';
			foreach ($rows as $row => $val) {
				

				
					if ($val !== NULL) {
						if(!is_array($val)){
						echo '<td style="border:solid 1px;">' . $val . '</td><br/>';
						echo '</tr>';
						
						}
						
						else{
						foreach($val as $keys => $valz){
						echo '<td style="border:solid 1px;">' . $valz . '</td>';
						}
						echo '</tr>';
						}
						
					} else {
						echo '<td style="border:solid 1px;">&nbsp;</td>';
					
						
					}
				
			
			}
				
			echo '</table></div>';
		}
	}

}