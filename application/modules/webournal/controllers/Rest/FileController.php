<?php
class webournal_Rest_FileController extends Core_REST_Controller
{
    /**
     *
     * @var webournal_Service_Directories 
     */
    private $_directories = null;
    
    /**
     *
     * @var webournal_Service_Files
     */
    private $_files = null;

    public function init()
    {
        $this->_directories = new webournal_Service_Directories();
        $this->_files = new webournal_Service_Files($this->_directories);
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
                $files = $this->_files->getFiles($id);

                if($files===false)
                {
                    throw new Exception('Access denied', 100);
                }
            }

            $data = array(
                'files' => $files
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
            $uploadId = $this->_request->getParam('file', null);
            $directoryId = $this->_request->getParam('directory', null);
            $ignore = $this->_request->getParam('ignore', false);
            
            if($ignore==='true' || $ignore==='1' || $ignore===1 || $ignore===true)
            {
                $ignore = true;
            }
            else
            {
                $ignore = false;
            }
            
            if(is_null($directoryId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $directory = $this->_directories->getDirectoryById($directoryId);

            if($directory===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            if(is_null($uploadId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = Core()->Upload()->getById($uploadId);
            
            if($file===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $hash = '';
            $check = $this->_files->checkFile($file['tmpname'], null, $hash);
            
            if($check===true || $ignore)
            {
                $name = $this->_request->getParam('name', '');
                $number = $this->_request->getParam('number', '');
                $description = $this->_request->getParam('description', '');
                
                $fileId = $this->_files->addFile($file['tmpname'], $directoryId, $name, $number, $description, $ignore);
                Core()->Upload()->delete($uploadId);
                
                $data = array(
                    'id' => $fileId
                );
            }
            else
            {
                $duplicatedFiles = array();
                
                reset($check);
                foreach($check as $file)
                {
                    $duplicatedFiles[] = array(
                        'id' => $file['id'],
                        'name' => $file['name'],
                        'number' => $file['number'],
                        'description' => $file['description'],
                        'created' => $file['created'],
                        'updated' => $file['updated'],
                        'url' => $file['url']
                    );
                }
                
                $success = false;
                $data = array(
                    'id' => false,
                    'duplicated' => $duplicatedFiles
                );
            }
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
            $fileId = $this->_request->getParam('id', null);
            
            if(is_null($fileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = $this->_files->getFileById($fileId);
            
            if($file===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $directoryId = $this->_request->getParam('directory', null);
            
            if(is_null($directoryId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $directory = $this->_directories->getDirectoryById($directoryId);

            if($directory===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $this->_files->removeFileFromDirectory($fileId, $directoryId);
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
            $fileId = $this->_request->getParam('id', null);
            
            if(is_null($fileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = $this->_files->getFileById($fileId);
            
            if($file===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $name = $this->_request->getParam('name', false);
            $number = $this->_request->getParam('number', false);
            $description = $this->_request->getParam('description', false);
            
            if($name===false)
            {
                $name = $file['name'];
            }
            
            if($number===false)
            {
                $number = $file['number'];
            }
            
            if($description===false)
            {
                $description = $file['description'];
            }
            
            $directoryId = $this->_request->getParam('directory', null);
            
            if(!is_null($directoryId))
            {
                $directory = $this->_directories->getDirectoryById($directoryId);

                if($directory===false)
                {
                    throw new Exception('Access denied', 100);
                }
                
                $this->_files->addFileToDirectory($fileId, $directoryId);
            }
            
            $this->_files->editFile($fileId, $name, $number, $description);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        
        $this->output($data, $success, $error);
    }
}