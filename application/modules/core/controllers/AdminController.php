<?php
/**
 * Index-Controller
 */
class core_AdminController extends Zend_Controller_Action
{
    const VERSION = 1;

    private $_groups = null;

    public function init()
    {
        $this->_groups = new core_Service_Groups();
    }

    public function postDispatch()
    {
        $this->view->submenu = array(
            array(
                'module'        => 'core',
                'name'          => 'Gruppenverwaltung',
                'controller'    => 'admin',
                'action'        => 'groups'
            ),
        );
    }

    /**
     * default action
     */
    public function indexAction()
    {

    }

    public function groupsAction()
    {
        $this->view->groups = $this->_groups->getGroups();
    }

    public function addgroupAction()
    {
        $error_unknown = false;
        $error_name = false;
        $error_url_empty = false;
        $error_url_incorrect = false;
        $error_url_duplicated = false;
        $error_email_empty = false;
        $error_email_notfound = false;
        
        $name = false;
        $url = false;
        $description = false;
        $email = false;

        $add = $this->_request->getParam('add', false);

        if($add!==false)
        {
            $url = $this->_request->getParam('url', false);
            $name = $this->_request->getParam('name', false);
            $description = $this->_request->getParam('description', false);
            $email = $this->_request->getParam('email', false);

            try
            {
                $this->_groups->addGroup($url, $name, $email, $description);
                return Core()->redirect('groups', 'admin', 'core');
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 10:
                        $error_url_empty = true;
                        break;
                    case 11:
                        $error_name = true;
                        break;
                    case 12:
                        $error_email_empty = true;
                        break;
                    case 13:
                        $error_url_duplicated = true;
                        break;
                    case 14:
                        $error_url_incorrect = true;
                        break;
                    case 15:
                        $error_email_notfound = true;
                        break;
                    default:
                        $error_unknown = true;
                        break;
                }
            }
        }
        
        $this->view->name = $name;
        $this->view->url = $url;
        $this->view->description = $description;
        $this->view->email = $email;

        $this->view->add_error_unknown = $error_unknown;
        $this->view->add_error_name = $error_name;
        $this->view->add_error_url_empty = $error_url_empty;
        $this->view->add_error_url_incorrect = $error_url_incorrect;
        $this->view->add_error_url_duplicated = $error_url_duplicated;
        $this->view->add_error_email_empty = $error_email_empty;
        $this->view->add_error_email_notfound = $error_email_notfound;
    }

    public function editgroupAction()
    {
        $error_unknown = false;
        $error_name = false;

        $id = $this->_request->getParam('id', false);

        if($id===false || ($group = $this->_groups->getGroupById($id))===false)
        {
            return Core()->redirect('groups', 'admin', 'core');
        }

        $name = $group['name'];
        $url = $group['url'];
        $description = $group['description'];

        $edit = $this->_request->getParam('edit', false);

        if($edit!==false)
        {
            $name = $this->_request->getParam('name', false);
            $description = $this->_request->getParam('description', false);

            try
            {
                $this->_groups->editGroup($id, $name, $description);
                return Core()->redirect('groups', 'admin', 'core');
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

        $this->view->id = $id;
        $this->view->name = $name;
        $this->view->url = $url;
        $this->view->description = $description;

        $this->view->edit_error_unknown = $error_unknown;
        $this->view->edit_error_name = $error_name;
    }
    
    public function removegroupAction()
    {
        $error_unknown = false;

        $id = $this->_request->getParam('id', false);

        if($id===false || ($group = $this->_groups->getGroupById($id))===false)
        {
            return Core()->redirect('groups', 'admin', 'core');
        }

        $remove = $this->_request->getParam('remove', false);

        if($remove!==false)
        {
            try
            {
                $this->_groups->removeGroup($id);
                return Core()->redirect('groups', 'admin', 'core');
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

        $this->view->id = $id;
        $this->view->group = $group;

        $this->view->remove_error = $error_unknown;
    }
    
    public function grouprightsAction()
    {
        $id = $this->_request->getParam('id', false);

        if($id===false || ($group = $this->_groups->getGroupById($id))===false)
        {
            return Core()->redirect('groups', 'admin', 'core');
        }
        
        $admins = $this->_groups->getAdminsOfGroup($id);

        $this->view->id = $id;
        $this->view->group = $group;
        $this->view->admins = $admins;
    }
    
    public function addgrouprightsAction()
    {
        $error_unknown = false;
        $error_empty = false;
        $error_username = false;
        $error_email = false;
        $error_username_email = false;

        $id = $this->_request->getParam('id', false);

        if($id===false || ($group = $this->_groups->getGroupById($id))===false)
        {
            return Core()->redirect('groups', 'admin', 'core');
        }
        
        $username = $this->_request->getParam('username', false);
        $email = $this->_request->getParam('email', false);
        
        $add = $this->_request->getParam('add', false);
        
        if($add!==false)
        {
            try
            {
                $this->_groups->addAdminToGroup($id, $username, $email);
                return Core()->redirect('grouprights', 'admin', 'core', array('id' => $id));
            }
            catch(Exception $e)
            {
                switch($e->getCode())
                {
                    case 15:
                        $error_email = true;
                        break;
                    case 17:
                        $error_username = true;
                        break;
                    case 18:
                        $error_username_email = true;
                        break;
                    case 16:
                    case 12:
                        $error_empty = true;
                        break;
                    case 100:
                    default:
                        $error_unknown = true;
                }
            }
        }

        $this->view->id = $id;
        $this->view->group = $group;
        
        $this->view->username = $username;
        $this->view->email = $email;
        
        $this->view->error_unknown = $error_unknown;
        $this->view->error_empty = $error_empty;
        $this->view->error_username = $error_username;
        $this->view->error_email = $error_email;
        $this->view->error_username_email = $error_username_email;
    }
    
    public function removegrouprightsAction()
    {
        $error_unknown = false;
        $error_noemailadmin = false;

        $id = $this->_request->getParam('id', false);

        if($id===false || ($group = $this->_groups->getGroupById($id))===false)
        {
            return Core()->redirect('groups', 'admin', 'core');
        }
        
        $userId = $this->_request->getParam('userid', false);
        
        if($userId===false)
        {
            return Core()->redirect('grouprights', 'admin', 'core', array('id' => $id));
        }
        
        $admins = $this->_groups->getAdminsOfGroup($id);
        
        if(!isset($admins[$userId]))
        {
            return Core()->redirect('grouprights', 'admin', 'core', array('id' => $id));
        }
        
        $check = false;
        
        reset($admins);
        foreach($admins as $adminid => $admin)
        {
            if($adminid==$userId)
            {
                continue;
            }
            
            if(!is_null($admin['email']) && !empty($admin['email']))
            {
                $check = true;
                break;
            }
        }
        
        if(!$check)
        {
            $error_noemailadmin = true;
        }

        $remove = $this->_request->getParam('remove', false);
        
        if($remove!==false && !$error_noemailadmin)
        {
            try
            {
                $this->_groups->removeAdminFromGroup($id, $userId);
                return Core()->redirect('grouprights', 'admin', 'core', array('id' => $id));
            }
            catch(Exception $e)
            {
                $error_unknown = true;
            }
        }

        $this->view->id = $id;
        $this->view->userId = $userId;
        $this->view->group = $group;
        $this->view->user = $admins[$userId];
        
        $this->view->error_unknown = $error_unknown;
        $this->view->error_noemailadmin = $error_noemailadmin;
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
        $parent = Core()->Menu()->findBy('label', 'ADMIN');
        if(is_null($parent))
        {
            $parent = Core()->Menu()->addContainerEntry('ADMIN', 'Admin');
        }

        if(is_null($parent->findBy('label', 'GROUPS')))
        {
            $parent->addControllerEntry('GROUPS', 'Groups', 'groups', 'admin', 'core');
        }
        
        return true;
    }
}