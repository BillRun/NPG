<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */

class Application_Form_DatePhoneFilter extends Zend_Form
{
    public function init()
    {
		$this->setMethod('POST');
		$this->setAction('');
		$this->addElement('text', 'phone', array(
            'label'      => 'phone',
            'required'   => true,
            
            )
        );
		$this->addElement(
			'DateTextBox',
			'date',
			array(
				'label'          => 'Date:',
				'required'       => true,
				'value'			 => '01/01/2011',
				'invalidMessage' => 'Invalid date specified.',
				'formatLength'   => 'long',
			)
		);
		
//		$this->addElement('select', 'month', array(
//            'label'      => 'month',
//            'required'   => true,
//            'multiOptions'   => array('01' => '01',
//							   '02' => '02',
//							   '03' => '03',
//							   '04' => '04',
//							   '05' => '05',
//							   '06' => '06',
//							   '07' => '07',
//							   '08' => '08',
//							   '09' => '09',
//							   '10' => '10',
//							   '11' => '11',
//							   '12' => '12',
//				  			  ),
//            
//            )
//        );
//		
//		$this->addElement('select', 'day', array(
//            'label'      => 'day',
//            'required'   => true,
//            'multiOptions'   => array('01' => '01',
//							   '02' => '02',
//							   '03' => '03',
//							   '04' => '04',
//							   '05' => '05',
//							   '06' => '06',
//							   '07' => '07',
//							   '08' => '08',
//							   '09' => '09',
//							   '10' => '10',
//							   '11' => '11',
//							   '12' => '12',
//							   '13' => '13',
//							   '14' => '14',
//							   '15' => '15',
//							   '16' => '16',
//							   '17' => '17',
//							   '18' => '18',
//							   '19' => '19',
//							   '20' => '20',
//							   '21' => '21',
//							   '22' => '22',
//							   '23' => '23',
//							   '24' => '24',
//							   '25' => '25',
//							   '26' => '26',
//							   '27' => '27',
//							   '28' => '28',
//							   '29' => '29',
//							   '30' => '30',
//							   '31' => '31'
//				  			  ),
//            
//            )
//        );
//		$this->addElement('select', 'year', array(
//            'label'      => 'year',
//            'required'   => true,
//            'multiOptions'   => array('2011' => '2011',
//									  '2012' => '2012',
//									 )
//            
//            )
//        );
 
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'submit',
        ));
 
        
    }
}
