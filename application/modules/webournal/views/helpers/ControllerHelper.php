<?php
class Zend_View_Helper_ControllerHelper extends Zend_View_Helper_Abstract
{  
    function controllerHelper() {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        
        $group = Core()->getGroup();
        $group = $group['name'];
        
        $this->view->WEBOURNAL_MODULE = $request->getModuleName();
        $this->view->WEBOURNAL_CONTROLLER = $request->getControllerName();
        $this->view->WEBOURNAL_ACTION = $request->getActionName();
        $this->view->WEBOURNAL_GROUP = $group;
        $this->view->WEBOURNAL_DOMAIN = Core()->getMainDomain();
    }
}
