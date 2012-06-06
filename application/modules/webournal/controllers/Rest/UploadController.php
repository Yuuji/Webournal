<?php
class Rest_UploadController extends Ext_REST_Controller
{
    public function postAction()
    {
        $data = array();
        $success = true;
        $error = null;
        
        try
        {
            $id = Core()->Upload()->upload('file');
            
            $data = array('id' => $id);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        
        $this->output($data, $success, $error);
    }
    
    public function deleteAction()
    {
        /** @todo implement */
        $this->_forward('get');
    }
    
    public function indexAction()
    {
        $this->_forward('get');
    }
    
    public function putAction()
    {
        $this->_forward('get');
    }
    
    public function getAction()
    {
        $data = array();
        $success = true;
        $error = null;
        try
        {
            throw new Exception('Access denied', 100);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        $this->output($data, $success, $error);
    }
}