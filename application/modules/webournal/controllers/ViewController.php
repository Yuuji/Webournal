<?php
/**
 * Index-Controller
 */
class webournal_ViewController extends Zend_Controller_Action
{
    const VERSION = 1;

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
    
    /**
     *
     * @var webournal_Service_XOJ
     */
    private $_xoj = null;

    public function init()
    {
        $this->_directories = new webournal_Service_Directories();
        $this->_files = new webournal_Service_Files(&$this->_directories);
        $this->_xoj = new webournal_Service_XOJ(&$this->_files);
    }

    public function postDispatch()
    {
        $submenu = array();
        $directoryId = $this->_request->getParam('id', null);
        
        if(is_null($directoryId))
        {
            $directoryId = $this->_request->getParam('parent', null);
        }

        $params = array();

        $directory = false;
        if(!is_null($directoryId))
        {
            $params['parent'] = $directoryId;
            
            $directory = $this->_directories->getDirectoryById($directoryId);
        }
        
        $submenu = array();
        
        if($directory!==false)
        {
            if(is_array($directory))
            {
                $submenu[] =  array(
                    'module'        => 'webournal',
                    'name'          => '&lt;-- Zur&uuml;ck',
                    'controller'    => 'view',
                    'action'        => 'index',
                    'params'        => array(
                        'id'        => $directory['parent']
                    ),
                    'neverselected' => true
                );
            }
        }


        $submenu[] =  array(
            'module'        => 'webournal',
            'name'          => 'Ordner anzeigen',
            'controller'    => 'view',
            'action'        => 'index',
            'params'        => array(
                'id'        => $directoryId
            )
        );
        $submenu[] = array(
            'module'        => 'webournal',
            'name'          => 'Ordner hinzuf&uuml;gen',
            'controller'    => 'view',
            'action'        => 'adddirectory',
            'params'        => $params
        );

        if(!is_null($directoryId))
        {
            $submenu[] = array(
                'module'        => 'webournal',
                'name'          => 'Datei hinzuf&uuml;gen',
                'controller'    => 'view',
                'action'        => 'addfile',
                'params'        => array(
                    'id'        => $directoryId
                )
            );

            $submenu[] = array(
                'module'        => 'webournal',
                'name'          => 'Ordner &auml;ndern',
                'controller'    => 'view',
                'action'        => 'editdirectory',
                'params'        => array(
                    'id'        => $directoryId
                )
            );

            $submenu[] = array(
                'module'        => 'webournal',
                'name'          => 'Ordner l&ouml;schen',
                'controller'    => 'view',
                'action'        => 'removedirectory',
                'params'        => array(
                    'id'        => $directoryId
                )
            );
        }
        
        $this->view->submenu = $submenu;
    }

    /**
     * default action
     */
    public function indexAction()
    {
        $directoryId = $this->_request->getParam('id', null);

        $directory = false;
        $directories = array();
        if(!is_null($directoryId))
        {
            $directory = $this->_directories->getDirectoryById($directoryId);
            
            if($directory===false)
            {
                return Core()->redirect('index', 'view', 'webournal');
            }
        }
        
        $directories = $this->_directories->getDirectories($directoryId);
        
        $files = array();
        if(!is_null($directoryId))
        {
            $files = $this->_files->getFiles($directoryId);
        }
        
        $this->view->directory = $directory;
        $this->view->directories = $directories;
        $this->view->files = $files;
        $this->view->directory_id = $directoryId;
    }

    public function adddirectoryAction()
    {
        $directoryId = $this->_request->getParam('parent', null);

        if(!is_null($directoryId))
        {
            $directory = $this->_directories->getDirectoryById($directoryId);

            if($directory===false)
            {
                return Core()->redirect('index', 'view', 'webournal');
            }
        }

        $params = array();

        if(!is_null($directoryId))
        {
            $params['parent'] = $directoryId;
        }
        
        $error_name_empty = false;
        $error_date_empty = false;
        $error_date_incorrect = false;
        $error_error_unknown = false;
        
        $name = '';
        $type = 'directory';
        $date = '';
        $description = '';

        $add = $this->_request->getParam('add', false);

        if($add!==false)
        {
            $name = $this->_request->getParam('name', '');
            $type = $this->_request->getParam('type', 'directory');
            $date = $this->_request->getParam('date', '');
            $description = $this->_request->getParam('description', '');

            try
            {
                $id = $this->_directories->addDirectory($name, $type, $description, $date, $directoryId);
                return Core()->redirect('index', 'view', 'webournal', array('id' => $id));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 10:
                        $error_name_empty = true;
                        break;
                    case 11:
                        $error_date_empty = true;
                        break;
                    case 12:
                        $error_date_incorrect = true;
                        break;
                    case 1:
                        return Core()->redirect('index', 'view', 'webournal');
                        break;
                    case 99:
                    default:
                        $error_error_unknown = true;
                        
                }
            }
        }

        $this->view->addParams = $params;
        $this->view->add_error_name_empty = $error_name_empty;
        $this->view->add_error_date_empty = $error_date_empty;
        $this->view->add_error_date_incorrect = $error_date_incorrect;
        $this->view->add_error_error_unknown = $error_error_unknown;

        $this->view->add_name = $name;
        $this->view->add_type = $type;
        $this->view->add_date = $date;
        $this->view->add_description = $description;
    }
    
    public function editdirectoryAction()
    {
        $directoryId = $this->_request->getParam('id', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $params = array();

        $error_name_empty = false;
        $error_date_empty = false;
        $error_date_incorrect = false;
        $error_error_unknown = false;
        
        $name = $directory['name'];
        $type = $directory['type'];
        $date = $directory['directory_time'];
        $description = $directory['description'];

        $edit = $this->_request->getParam('edit', false);

        if($edit!==false)
        {
            $name = $this->_request->getParam('name', '');
            $type = $this->_request->getParam('type', 'directory');
            $date = $this->_request->getParam('date', '');
            $description = $this->_request->getParam('description', '');

            try
            {
                $this->_directories->editDirectory($directoryId, $name, $type, $description, $date);
                return Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 10:
                        $error_name_empty = true;
                        break;
                    case 11:
                        $error_date_empty = true;
                        break;
                    case 12:
                        $error_date_incorrect = true;
                        break;
                    case 1:
                        return Core()->redirect('index', 'view', 'webournal');
                        break;
                    case 99:
                    default:
                        $error_error_unknown = true;
                        
                }
            }
        }

        $this->view->editParams = $params;
        $this->view->edit_error_name_empty = $error_name_empty;
        $this->view->edit_error_date_empty = $error_date_empty;
        $this->view->edit_error_date_incorrect = $error_date_incorrect;
        $this->view->edit_error_error_unknown = $error_error_unknown;

        $this->view->edit_id  = $directoryId;
        $this->view->edit_name = $name;
        $this->view->edit_type = $type;
        $this->view->edit_date = $date;
        $this->view->edit_description = $description;
    }
    
    public function removedirectoryAction()
    {
        $directoryId = $this->_request->getParam('id', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $error = false;
        
        $remove = $this->_request->getParam('remove', false);

        if($remove!==false)
        {
            try
            {
                $this->_directories->removeDirectory($directoryId);
                
                $params = array();
                
                if(!is_null($directory['parent']))
                {
                    $params['id'] = $directory['parent'];
                }
                
                return Core()->redirect('index', 'view', 'webournal', $params);
            }
            catch(Exception $e)
            {
                $error = true;
            }
        }

        $this->view->remove_error = $error;

        $this->view->remove_id  = $directoryId;
        $this->view->remove_name = $directory['name'];
        $this->view->remove_type = $directory['type'];
        $this->view->remove_date = $directory['directory_time'];
        $this->view->remove_description = $directory['description'];
    }

    public function addfileAction()
    {
        $directoryId = $this->_request->getParam('id', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $error_upload = false;
        $error_file_missing = false;
        $error_file_type = false;
        $error_unknown = false;
        
        $add = $this->_request->getParam('add', false);
        
        if($add!==false)
        {
            try
            {
                $id = Core()->Upload()->upload('addfile');
                
                if(!is_int($id))
                {
                    throw new Exception('Upload error', 23);
                }
                
                $file = Core()->Upload()->getById($id);
                
                if($file===false)
                {
                    return Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
                }
                
                $check = $this->_files->checkFile($file['tmpname']);
                
                if($check===true)
                {
                    Core()->redirect('addfilesettings', 'view', 'webournal', array('directory' => $directoryId, 'file' => $id));
                }
                else
                {
                    Core()->redirect('addfileduplicated', 'view', 'webournal', array('directory' => $directoryId, 'file' => $id));
                }
            }
            catch(Exception $e)
            {
                if(isset($file) && isset($file['id']))
                {
                    try
                    {
                        Core()->Upload()->delete($file['id']);
                    }
                    catch(Exception $ee)
                    {
                        
                    }
                }
                switch($e->getCode())
                {
                    case 20:
                    case 21:
                        $error_file_missing = true;
                        break;
                    case 22:
                    case 23:
                        $error_upload = true;
                        break;
                    case 30:
                        $error_file_type = true;
                        break;
                    default:
                        $error_unknown = true;
                }
            }
        }
        
        $this->view->add_id = $directoryId;
        $this->view->add_error_upload = $error_upload;
        $this->view->add_error_file_missing = $error_file_missing;
        $this->view->add_error_file_type = $error_file_type;
        $this->view->add_error_unknown = $error_unknown;
    }
    
    public function addfilesettingsAction()
    {
        $directoryId = $this->_request->getParam('directory', null);
        $uploadId = $this->_request->getParam('file', null);
        $ignore = $this->_request->getParam('ignore', '0');

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $file = Core()->Upload()->getById($uploadId);
        
        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $error_name = false;
        $error_unknown = false;
        
        $add = $this->_request->getParam('add', false);
        
        $pdf = new Ext_Service_PDF($file['tmpname']);
        
        $name = $file['data']['name'];
        $number = '';
        
        $description = $pdf->getSubject();
        if(empty($description))
        {
            $description = $pdf->getTitle();
        }
        
        if($add!==false)
        {
            try
            {
                $name = $this->_request->getParam('name', '');
                $number = $this->_request->getParam('number', '');
                $description = $this->_request->getParam('description', '');
                
                $ignore = ($ignore==='1' ? true : false);
                
                $fileId = $this->_files->addFile($file['tmpname'], $directoryId, $name, $number, $description, $ignore);
                Core()->Upload()->delete($uploadId);
                
                Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 10:
                        Core()->redirect('addfileduplicated', 'view', 'webournal', array('directory' => $directoryId, 'file' => $uploadId));
                        break;
                    case 11:
                        $error_name = true;
                        break;
                    default:
                        $error_unknown = true;
                }
            }
        }
        
        $this->view->add_directoryId = $directoryId;
        $this->view->add_fileId = $uploadId;
        $this->view->add_ignore = $ignore;
        
        $this->view->add_name = $name;
        $this->view->add_number = $number;
        $this->view->add_description = $description;
        
        $this->view->add_error_name = $error_name;
        $this->view->add_error_unknown = $error_unknown;
    }
    
    public function addfileduplicatedAction()
    {
        $directoryId = $this->_request->getParam('directory', null);
        $uploadId = $this->_request->getParam('file', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $file = Core()->Upload()->getById($uploadId);
        
        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $hash = $this->_files->calcHash($file['tmpname']);
        $fileIds = $this->_files->getFileIdsByHash($hash);
        
        $files = array();
        reset($fileIds);
        foreach($fileIds as $fid)
        {
            $files[] = $this->_files->getFileById($fid);
        }
        
        $add = $this->_request->getParam('add', false);
        
        if($add!==false)
        {
            $use = $this->_request->getParam('use', '');
            
            if($use!='')
            {
                if($use==='new')
                {
                    Core()->redirect('addfilesettings', 'view', 'webournal', array('directory' => $directoryId, 'file' => $uploadId, 'ignore' => '1'));
                }
                else
                {
                    $this->_files->addFileToDirectory($use, $directoryId);
                    Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
                }
            }
        }
        
        $this->view->add_directoryId = $directoryId;
        $this->view->add_files = $files;
        $this->view->add_fileId = $uploadId;
    }

    public function editfileAction()
    {
        $directoryId = $this->_request->getParam('directory', null);
        $fileId = $this->_request->getParam('id', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $file = $this->_files->getFileById($fileId);

        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $error_name = false;
        $error_unknown = false;

        $edit = $this->_request->getParam('edit', false);

        $name = $file['name'];
        $number = $file['number'];

        $description = $file['description'];

        if($edit!==false)
        {
            try
            {
                $name = $this->_request->getParam('name', '');
                $number = $this->_request->getParam('number', '');
                $description = $this->_request->getParam('description', '');

                $this->_files->editFile($fileId, $name, $number, $description);

                Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 11:
                        $error_name = true;
                        break;
                    default:
                        $error_unknown = true;
                        break;
                }
            }
        }

        $this->view->edit_directoryId = $directoryId;
        $this->view->edit_fileId = $fileId;

        $this->view->edit_name = $name;
        $this->view->edit_number = $number;
        $this->view->edit_description = $description;

        $this->view->edit_error_name = $error_name;
        $this->view->edit_error_unknown = $error_unknown;
    }

    public function removefileAction()
    {
        $directoryId = $this->_request->getParam('directory', null);
        $fileId = $this->_request->getParam('id', null);

        $directory = $this->_directories->getDirectoryById($directoryId);

        if($directory===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $file = $this->_files->getFileById($fileId);

        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }

        $error_unknown = false;

        $remove = $this->_request->getParam('remove', false);

        if($remove!==false)
        {
            try
            {
                $this->_files->removeFileFromDirectory($fileId, $directoryId);

                Core()->redirect('index', 'view', 'webournal', array('id' => $directoryId));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    default:
                        $error_unknown = true;
                        break;
                }
            }
        }

        $this->view->remove_directoryId = $directoryId;
        $this->view->remove_fileId = $fileId;

        $this->view->remove_file = $file;
        $this->view->remove_directory = $directory;

        $this->view->remove_error_unknown = $error_unknown;
    }
    
    public function viewAction()
    {
        //$this->_response->setHeader('Access-Control-Allow-Origin', '*', true); //https://' . Core()->getMainDomain(), true);
        $fileId = $this->_request->getParam('id', null);

        $file = $this->_files->getFileById($fileId);

        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $this->_helper->layout()->disableLayout();
        $this->view->fileId = $fileId;
    }
    
    public function viewxojAction()
    {
        $fileId = $this->_request->getParam('id', null);

        $file = $this->_files->getFileById($fileId);

        if($file===false)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
        
        $type = $this->_request->getParam('type', 'json');
        
        $xoj = $this->_xoj->getXOJObject($fileId, Core()->getUserId());
        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        switch($type)
        {
            case 'download':
                $this->getResponse()
                    ->setHeader('Content-Disposition', 'attachment; filename=' . $file['name'] . '.xoj')
                    ->setHeader('Content-type', 'application/xoj');
                $this->view->xoj = $xoj;
                echo gzencode($this->view->render('view/viewxoj.tpl'));
                break;
            case 'json':
                $this->_helper->json->sendJson($xoj);
                break;
        }
    }
    
    public function savexojAction()
    {
        $fileId = $this->_request->getParam('id', null);

        $file = $this->_files->getFileById($fileId);

        if($file===false)
        {
            die('Access denied');
        }
        
        $data = $this->_request->getParam('data', null);
        if(is_null($data))
        {
            die('Access denied');
        }
        if(is_string($data))
        {
            try
            {
                $data = Zend_Json_Decoder::decode($data);
            }
            catch(Exception $e)
            {
                die('Access denied');
            }
        }

        $data =  Core()->arrayToObject($data);
        
        if(!is_object($data))
        {
            die('Access denied');
        }
        
        try
        {
            $this->_xoj->setLayers($fileId, $data);
            Core()->outputREST(array('saved' => true), true);
        }
        catch(Exception $e)
        {
            Core()->outputREST(array('saved' => false), false);
        }
    }

    public static function updater($version)
    {
        if($version<self::VERSION)
        {
            for($i=$version+1; $i<=self::VERSION; $i++)
            {
                $function = 'update' . $i;
                if(!self::$function())
                {
                    return $i-1;
                }
            }
        }

        return self::VERSION;
    }

    private static function update1()
    {
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_view_index');
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_view_view');
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_view_viewxoj');
        Core()->ACL()->addDefaultPermissions('allow', 3, 'webournal_view_savexoj');
        
        Core()->ACL()->addDefaultPermissions('deny', 2, 'webournal_view_index', 0);
        Core()->ACL()->addDefaultPermissions('deny', 2, 'webournal_view_view', 0);
        Core()->ACL()->addDefaultPermissions('deny', 2, 'webournal_view_viewxoj', 0);
        Core()->ACL()->addDefaultPermissions('deny', 3, 'webournal_view_savexoj', 0);
        return true;
    }
}