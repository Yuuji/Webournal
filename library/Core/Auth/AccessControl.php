<?php

/**
 * Access Control Plugin
 */
class Core_Auth_AccessControl extends Zend_Controller_Plugin_Abstract
{
    private $_permissionGroupId = null;
    
    private $_adminChecks = array();
    
    /**
     * Initialize Access Control
     * @param Zend_Auth $auth
     * @param Zend_Acl $acl 
     * @param array $config
     */
    public function __construct(Zend_Auth $auth, Zend_Acl $acl, $config)
    {
        $this->_auth = $auth;
        $this->_acl = $acl;
        
        $registry = Zend_Registry::getInstance();
        $this->_auth->getIdentity();
        
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
    }
    
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $message = '';
        $error = false;
        
        $front     = Zend_Controller_Front::getInstance();
        
        // Just for AJAX
        if(Core()->isREST())
        {
            $postUsername = $request->getPost('login_user', false);
            $getUsername = $request->getQuery('login_user', false);
            $getPassword = $request->getQuery('login_password', false);
            $callback = $request->getQuery('callback', false);
            
            if($callback!==false && $postUsername===false && $getUsername!==false && $getPassword!==false)
            {
                $request->setPost('login_user', $getUsername);
                $request->setPost('login_password', $getPassword);
            }
        }

        if (!$this->_auth->hasIdentity() && null !== $request->getPost('login_user') && null !== $request->getPost('login_password'))
        {
            // clear POST data
            $username = $request->getPost('login_user');
            $password = $request->getPost('login_password');
            if (empty($username))
            {
                $error = true;
                $message = 'username';
            }
            elseif (empty($password))
            {
                $error = true;
                $message = 'password';
            }
            else
            {
                $result = $this->checkUser($username, $password);
                if ($result===false)
                {
                    $message = 'passwordwrong';
                    $error = true;
                }
                else
                {
                    $storage = $this->_auth->getStorage();

                    // store data in session withot password
                    $storage->write(array(
                        'username' => $username,
                        'userid' => $result
                    ));
                    if(!Core()->isREST())
                    {
                        return Core()->redirect('index', 'index');
                    }
                }

                $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
                $view = $viewRenderer->view;
            }

            if ($error)
            {
                $view->login_error = $error;
                $view->login_message = $message;
            }
        }
    }

    /**
     * Check login status before run controller
     * @param Zend_Controller_Request_Abstract $request 
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        
        $registry = Zend_Registry::getInstance();
        
        $module     = $request->getModuleName();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        // check Group
        $group = ($request->group ? $request->group : false);
        
        $permissionGroupId = null;
        
        $currentGroupId = null;
        
            
        $check = Core()->getGroupId();
        if($check===false && !defined('IS_CLI'))
        {
            // incorrect group
            return Core()->redirectMain('index', 'index');
        }

        $currentGroupId = $check;


        /** @todo check admin */
        $permissionGroupId = 2;
        $isAdmin = false;

        if($this->_auth->hasIdentity())
        {
            $check = $this->getPermissionGroupForGroup(Core()->getGroupId());
            
            if($check!==false)
            {
                $permissionGroupId = $check;
            }
            else
            {
                $permissionGroupId = 3;
            }
        }

        $this->_permissionGroupId = $permissionGroupId;

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        
        $viewAcl = new stdClass();
        
        $viewAcl->group = $permissionGroupId;
        $viewAcl->isAdmin = $isAdmin;
        
        $view->accesscontrol = $viewAcl;
        
        if ($this->_acl->has($module . '_' . $controller . '_' . $action))
        {
            $resource = $module . '_' . $controller . '_' . $action;
        }
        else if ($this->_acl->has($module . '_' . $controller))
        {
            $resource = $module . '_' . $controller;
        }
        else if ($this->_acl->has($module))
        {
            $resource = $module;
        }
        else
        {
            $resource = null;
        }

        if (!$this->_acl->isAllowed($permissionGroupId, $resource))
        {
            if ($this->_auth->hasIdentity())
            {
                // loggedin, but no rights => error
                Core()->redirectMain('index', 'index');
            }
            else
            {
                // not logged in => login
                Core()->redirect('login', 'login', 'core');
            }
        }
    }
    
    /**
     * Check if user is logged in as admin
     * @return boolean 
     */
    public function isAdmin()
    {
        return ($this->_permissionGroupId == 1 ? true : false);
    }
    
    /**
     * Adds User by PiratenID
     * @param string $username
     * @param string $password
     * @param string $email
     * @return int User-ID
     */
    public function addUser($username, $password, $email = false)
    {
        $db = Core()->Db();
        
        $id = $db->fetchOne('
            SELECT
                id
            FROM
                `acl_users`
            WHERE
                `username` = ?
        ', array($username));
        
        if($id!==false)
        {
            return $id;
        }

        if($email===false)
        {
            $sql = '
                INSERT INTO
                    `acl_users`
                SET
                    `username` = ?,
                    `password` = ?,
                    `email` = null
            ';

            $db->query($sql, array($username, Core()->cryptPassword($password)));
        }
        else
        {
            $sql = '
                INSERT INTO
                    `acl_users`
                SET
                    `username` = ?,
                    `password` = ?,
                    `email` = ?
            ';

            $db->query($sql, array($username, Core()->cryptPassword($password), $email));
        }
        
        return $db->lastInsertId('acl_users');
    }

    public function getUserId($username)
    {
        $db = Core()->Db();

        $id = $db->fetchOne('
            SELECT
                id
            FROM
                `acl_users`
            WHERE
                `username` = ?
        ', array($username));

        return $id;
    }

    public function getUserIdByEmail($email)
    {
        $db = Core()->Db();

        $id = $db->fetchOne('
            SELECT
                id
            FROM
                `acl_users`
            WHERE
                `email` = ?
        ', array($email));

        return $id;
    }
    
    public function getUserIdByUsername($username)
    {
        $db = Core()->Db();

        $id = $db->fetchOne('
            SELECT
                id
            FROM
                `acl_users`
            WHERE
                `username` = ?
        ', array($username));

        return $id;
    }

    /**
     *
     * @param string $username
     * @param string $password
     * @return boolean Result
     */
    public function checkUser($username, $password)
    {
        $check = Core()->Db()->fetchOne('
            SELECT
                id
            FROM
                `acl_users`
            WHERE
                `username` = ? AND
                `password` = ?
        ', array($username, Core()->cryptPassword($password)));

        return $check;
    }

    public function changePassword($username, $password)
    {
        Core()->Db()->query('
            UPDATE
                `acl_users`
            SET
                `password` = ?
            WHERE
                `username` = ?
        ', array(
            Core()->cryptPassword($password), $username
        ));
    }
    
    /**
     * Adds a User to a group and permissiongroup
     * @param int $userId
     * @param int|null $groupId
     * @param int $permissionGroupId 
     */
    public function addUserGroup($userId, $groupId, $permissionGroupId)
    {
        $sql = '
            REPLACE INTO
                `acl_users_groups`
            SET
                `user_id` = ?,
                `group_id` = ?,
                `permissions_group_id` = ?
        ';
        
        Core()->Db()->query($sql, array($userId, $groupId, $permissionGroupId));
    }
    
    /**
     * Check if User id is admin
     * @param int $userId
     * @return boolean
     */
    private function checkIfUserIsAdmin($userId)
    {
        if(!isset($this->_adminChecks[$userId]))
        {
            $check = Core()->Db()->fetchOne('
                SELECT
                    `user_id`
                FROM
                    `acl_users_groups`
                WHERE
                    `user_id` = ? AND
                    `group_id` IS NULL AND
                    `permissions_group_id` = 1
            ', array($userId));

            if($check!==false && (int)$check===(int)$userId && (int)$check>0)
            {
                $this->_adminChecks[$userId] = true;
            }
            else
            {
                $this->_adminChecks[$userId] = false;
            }
        }
        
        return $this->_adminChecks[$userId];
    }
    
    /**
     * Check if group has Admin (aka Vorstand)
     * @param int $groupId
     * @return boolean
     */
    public function checkIfGroupHasAdmin($groupId)
    {
        $check = Core()->Db()->fetchOne('
            SELECT
                `group_id`
            FROM
                `acl_users_groups`
            WHERE
                `group_id` = ? AND
                `permissions_group_id` = 1
        ', array($groupId));
        
        if($check!==false && (int)$check===(int)$groupId && (int)$check>0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Returns permission group of User for group of false
     * @param int $groupId
     * @return int|false
     * @todo implement
     */
    public function getPermissionGroupForGroup($groupId)
    {
        $check = false;

        if(($userId = Core()->getUserId())!==false)
        {
            if($groupId===0)
            {
                $id = Core()->Db()->fetchOne('
                    SELECT
                        `permissions_group_id`
                    FROM
                        `acl_users_groups`
                    WHERE
                        `user_id` = ? AND
                        `group_id` IS NULL
                ', array($userId));
            }
            else
            {
                $id = Core()->Db()->fetchOne('
                    SELECT
                        `permissions_group_id`
                    FROM
                        `acl_users_groups`
                    WHERE
                        `user_id` = ? AND
                        `group_id` = ?
                ', array($userId, $groupId));
            }
            
            if($id!==false)
            {
                $check = (int)$id;
            }
        }

        return $check;
    }
    
    /**
     * Adds a group and / or returns it id
     * @param string $name
     * @param string $description
     * @return type 
     */
    public function addGroup($name, $description='')
    {
        $id = Core()->Db()->fetchOne('
            SELECT
                `id`
            FROM
                `acl_groups`
            WHERE
                `name` = ?
        ', array($name));
        
        
        if($id!==false)
        {
            return $id;
        }
        
        $url = Core()->getURLName($name);
        
        Core()->Db()->query('
            INSERT INTO
                `acl_groups`
            SET
                `name` = ?,
                `url` = ?,
                `description` = ?
        ', array($name, $url, $description));
        
        return Core()->Db()->lastInsertId('acl_groups');
    }
    
    public function getPermissionGroupId()
    {
        return $this->_permissionGroupId;
    }
}
