<?php

/**
 * @TODO Replace with autoloader
 */
if(!class_exists('webournal_Rest_FileController'))
{
	require_once(dirname(__FILE__) . '/FileController.php');
}

class webournal_Rest_AttachmentController extends webournal_Rest_FileController
{
   public function getAction()
    {
        $data = array();
        $success = true;
        $error = null;
        try
        {
            $id = intval($this->_request->getParam('id', null));

            $files = null;
            if(!is_null($id))
            {
        		$file = $this->_files->getFileById($id);
        	
        		if($file===false)
        		{
        	    	throw new Exception('Access denied', 100);
        		}
        	
                $files = $this->_files->getFileAttachments($id);

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
            $uploadId = intval($this->_request->getParam('file', null));
            $attachToFileId = intval($this->_request->getParam('attachtofile', null));
            $ignore = $this->_request->getParam('ignore', false);
            
            if($ignore==='true' || $ignore==='1' || $ignore===1 || $ignore===true)
            {
                $ignore = true;
            }
            else
            {
                $ignore = false;
            }
            
            if(is_null($attachToFileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = $this->_files->getFileById($attachToFileId);

            if($file===false)
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
                
                $fileId = $this->_files->addFileAttachment($file['tmpname'], $attachToFileId, $name, $number, $description, $ignore);
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
            $fileId = intval($this->_request->getParam('id', null));
            
            if(is_null($fileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = $this->_files->getFileAttachmentById($fileId);
            
            if($file===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $attachedToFileId = intval($this->_request->getParam('attachedtofile', null));
            
            if(is_null($attachedToFileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $attachedToFile = $this->_files->getFileById($attachedToFileId);

            if($attachedToFile===false)
            {
                throw new Exception('Access denied', 100);
            }
            
            $this->_files->removeFileAttachement($fileId, $attachedToFileId);
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
            $fileId = intval($this->_request->getParam('id', null));
            
            if(is_null($fileId))
            {
                throw new Exception('Access denied', 100);
            }
            
            $file = $this->_files->getFileAttachementById($fileId);
            
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
            
            $attachToFileId = intval($this->_request->getParam('attachtofile', null));
            
            if(!is_null($attachToFileId))
            {
                $attachToFile = $this->_files->getFileById($attachToFileId);

                if($attachToFile===false)
                {
                    throw new Exception('Access denied', 100);
                }
                
                $this->_files->addFileToAttachment($fileId, $attachToFileId);
            }
            
            $this->_files->editFileAttachment($fileId, $name, $number, $description);
        }
        catch(Exception $e)
        {
            $success = false;
            $error = $e;
        }
        
        $this->output($data, $success, $error);
    }
}
