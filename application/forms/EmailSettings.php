<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Application_Form_EmailSettings extends Zend_Form
{
    public function init()
    {
		
		$this->setMethod('POST');
		$this->setAction('');
		$this->addElement('text', 'provider', array(
            'label'      => 'provider',
            'required'   => true
			
        )); 
		$this->addElement('text', 'email', array(
            'label'      => 'email',
            'required'   => true
			
        )); 
		 
		// Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'submit',
        ));
 
        
    }
}
