<?php
class Zend_View_Helper_JavascriptHelper extends Zend_View_Helper_Abstract
{  
    function javascriptHelper() {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $file_uri = 'js/';
        
        $file_uri .= $request->getModuleName() .  '/' . $request->getControllerName();
 
        if (file_exists($file_uri . '.js')) {
            $this->view->headScript()->appendFile('/' . $file_uri . '.js');
        }
        
        $file_uri .= '/' . $request->getActionName();
        
        if (file_exists($file_uri . '.js')) {
            $this->view->headScript()->appendFile('/' . $file_uri . '.js');
        }
    }
}
