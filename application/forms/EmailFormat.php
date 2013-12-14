<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_EmailFormat extends Zend_Form
{
    public function init()
    {
		
		$this->setMethod('POST');
		$this->setAction('');
		$this->addElement('textarea', 'email_format', array(
            'label'      => 'email_format',
            'value'      => Application_Model_General::getEmailFormat(),
            'required'   => true
			
        )); 
		 
		 
		// Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'submit',
        ));
 
        
    }
}
