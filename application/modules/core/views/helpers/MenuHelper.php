<?php
class Zend_View_Helper_MenuHelper extends Zend_View_Helper_Abstract
{  
    function menuHelper() {
        $this->view->menu = Core()->Menu()->getMenuByACL();
    }
}
