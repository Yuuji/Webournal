<?php
class Zend_View_Helper_CssHelper extends Zend_View_Helper_Abstract
{  
    function cssHelper() {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $file_uri = 'css/';
        
        $file_uri .= $request->getModuleName() .  '/' . $request->getControllerName();
 
        if (file_exists($file_uri . '.css')) {
            $this->view->headLink()->appendStylesheet('/' . $file_uri . '.css');
        }
        
        $file_uri .= '/' . $request->getActionName();
        
        if (file_exists($file_uri . '.css')) {
            $this->view->headLink()->appendStylesheet('/' . $file_uri . '.css');
        }
    }
}
