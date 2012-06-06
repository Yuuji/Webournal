<?php

abstract class Core_REST_Controller extends Zend_Rest_Controller
{
    public function init()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }
    
    public function output($data, $success = true, Exception $e = null)
    {
        Core()->outputREST($data, $success, $e);
    }
}