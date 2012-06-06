<?php
class Rest_DirectoryController extends Ext_REST_Controller
{
    /**
     *
     * @var webournal_Service_Directories 
     */
    private $_directories = null;

    public function init()
    {
        $this->_directories = new webournal_Service_Directories();
    }
    
    public function indexAction()
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
            $id = $this->_request->getParam('id', null);

            $directory = null;
            if(!is_null($id))
            {
                $directory = $this->_directories->getDirectoryById($id);

                if($directory===false)
                {
                    throw new Exception('Access denied', 100);
                }
            }

            $data = array(
                'directory' => $directory,
                'subdirectories' => $this->_directories->getDirectories($id)
            );
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        $this->output($data, $success, $error);
    }
    
    public function postAction()
    {
        $data = array();
        $success = true;
        $error = null;
        
        try
        {
            $directoryId = $this->_request->getParam('id', null);

            if(!is_null($directoryId))
            {
                $directory = $this->_directories->getDirectoryById($directoryId);

                if($directory===false)
                {
                    throw new Exception('Access denied', 100);
                }
            }

            $name = $this->_request->getParam('name', '');
            $type = $this->_request->getParam('type', 'directory');
            $date = $this->_request->getParam('date', '');
            $description = $this->_request->getParam('description', '');

            $id = $this->_directories->addDirectory($name, $type, $description, $date, $directoryId);
            
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
        $data = array();
        $success = true;
        $error = null;
        
        try
        {
            $directoryId = $this->_request->getParam('id', null);

            if(is_null($directoryId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $directory = $this->_directories->getDirectoryById($directoryId);
            
            if($directory===false)
            {
                throw new Exception('Access denied', 100);
            }

            $id = $this->_directories->removeDirectory($directoryId);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        
        $this->output($data, $success, $error);
    }
    
    public function putAction()
    {
        $data = array();
        $success = true;
        $error = null;
        
        try
        {
            $directoryId = $this->_request->getParam('id', null);

            if(is_null($directoryId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $directory = $this->_directories->getDirectoryById($directoryId);
            
            if($directory===false)
            {
                throw new Exception('Access denied', 100);
            }

            $name = $this->_request->getParam('name', $directory['name']);
            $type = $this->_request->getParam('type', $directory['type']);
            $date = $this->_request->getParam('date', $directory['directory_time']);
            $description = $this->_request->getParam('description', $directory['description']);

            $id = $this->_directories->editDirectory($directoryId, $name, $type, $description, $date);
            
            $data = array('id' => $id);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        
        $this->output($data, $success, $error);
    }
}