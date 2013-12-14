<?php

/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_Helper_StatsFilter extends Zend_Form
{
    public function init()
    {
		
		$this->setMethod('POST');
		$this->setAction('');
		$this->addElement('text', 'phone', array(
            'label'      => 'phone',
            'required'   => true,
			'validators' => array('Alnum',
								array('regex', false, '/^[a-z]/i')
								),
            'filters'	 => array('Alnum')
            )
        );
		
		
		
		$this->addElement(
			'date',
			'date',
			array(
				'label'          => 'Date: YYYY-MM-DD For Example : 2011-11-20',
				'required'       => true,
				'value'				 => '2011-11-20'
			)
		);
		$this->addElement(
			'Time',
			'Time',
			array(
				'label'          => 'Time: HH:MM:SS For Example : 16:20:00',
				'required'       => true,
				'value'			 => '16:20:00',
				'invalidMessage' => 'Invalid date specified.',
				'formatLength'   => 'long',
			)
		);
		$this->addElement(
			'ToDate',
			'ToDate',
			array(
				'label'          => 'Date: YYYY-MM-DD For Example : 2011-11-20',
				'required'       => true,
				'value'				 => '2011-11-20',
				'invalidMessage' => 'Invalid date specified.',
				'formatLength'   => 'long',
			)
		);
		$this->addElement(
			'ToTime',
			'ToTime',
			array(
				'label'          => 'Time: HH:MM:SS For Example : 16:20:00',
				'required'       => true,
				'value'			 => '16:20:00',
				'invalidMessage' => 'Invalid date specified.',
				'formatLength'   => 'long',
			)
		);
		
 
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'submit',
        ));
 
        
    }
}
