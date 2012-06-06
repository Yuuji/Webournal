<?php

/**
 * ACL
 */
class Core_Auth_Acl extends Zend_Acl
{
    /**
     * Const for all rights (admin)
     */
    const ALLRIGHTS = 1;

    /**
     * @var array Groups
     */
    private $_groups	= null;
    
    /**
     * @var array Permissions
     */
    private $_permissions	= null;

    /**
     * Initialize ACL rules
     */
    public function __construct()
    {
        // Groups
        $this->addRole(new Zend_Acl_Role('visitor'));
        $this->addRole(new Zend_Acl_Role('guest'), 'visitor');

        // Resources
        $acls = self::getACLResources();

        foreach($acls as $module => $controllers)
        {
            $prefix = ($module==Core()->getDefaultModule() ? '' : $module . '_');
            foreach($controllers as $controller => $actions)
            {
                $this->add(new Zend_Acl_Resource($prefix . $controller));
                foreach($actions as $action => $subs)
                {
                    $this->add(new Zend_Acl_Resource($prefix . $controller.'_'.$action), $prefix . $controller);
                    foreach($subs as $sub)
                    {
                        $this->add(new Zend_Acl_Resource($prefix . $controller.'_'.$action . '_' . $sub), $prefix . $controller . '_' . $action);
                    }
                }
            }
        }

        // ACL

        // deny all
        $this->deny(null, null);

        // pre install
        foreach(array('core_login', 'core_login_logout', 'core_login_login', 'core_login_register', 'index', 'core_error') as $resource)
        {
            if(!$this->has($resource))
            {
               $this->add(new Zend_Acl_Resource($resource));
            }
        }
        
        // Login / Logout

        $this->deny('visitor', 'core_login_logout');
        $this->allow('visitor', 'core_login_login');
        $this->allow('visitor', 'core_login_register');
        $this->allow('guest', 'core_login');
        $this->deny('guest', 'core_login_login');
        $this->deny('guest', 'core_login_register');
        $this->allow('guest', 'core_login_logout');
        
        $loginConfig = Core()->getLoginConfig();
        
        if(!$loginConfig->register->allow)
        {
            $this->deny('visitor', 'core_login_register');
        }
        
        // Default
        
        $this->allow('visitor', 'index');
        $this->allow('visitor', 'core_error');
        
        if(Core()->getGroupId()>0)
        {
            $this->deny(null, 'core_admin');
        }
        
        $defaults = self::getDefaultPermissions();
        
        foreach($defaults as $default)
        {
            try
            {
                switch($default['type'])
                {
                    case 'allow':
                        $this->allow($default['group'], $default['resource']);
                        break;
                    case 'deny':
                        $this->deny($default['group'], $default['resource']);
                        break;
                }
            }
            catch(Exception $e)
            {
                
            }
        }
    }

    /**
     * Checks if $groupId is allowed to access $resource
     * @param mixed $groupId
     * @param string $resource
     * @return boolean
     */
    public function isAllowed($groupId, $resource)
    {
        $this->addACLsOfGroup($groupId);

        $group = $this->_groups[$groupId]['name'];
        
        return parent::isAllowed($group, $resource);
    }

    /**
     * Returns all ACL resorcues
     * @return array
     */
    public static function getACLResources()
    {
        try
        {
            $all = Core()->Db()->fetchAll('SELECT * FROM `acl_resources` ORDER BY `module`, `controller`, `action`, `sub`');
        }
        catch(Exception $e)
        {
            return array();
        }
        
        $acl = array();
        reset($all);
        foreach($all as $row)
        {
            $row['module'] = strtolower($row['module']);
            $row['controller'] = strtolower($row['controller']);
            $row['action'] = strtolower($row['action']);
            $row['sub'] = strtolower($row['sub']);
            
            if(!isset($acl[$row['module']]))
            {
                $acl[$row['module']] = array();
            }
            
            if(!isset($acl[$row['module']][$row['controller']]))
            {
                $acl[$row['module']][$row['controller']] = array();
            }
            
            if($row['action']!='')
            {
                if(!isset($acl[$row['module']][$row['controller']][$row['action']]))
                {
                    $acl[$row['module']][$row['controller']][$row['action']] = array();
                }
            
                if($row['sub']!='')
                {
                    $acl[$row['module']][$row['controller']][$row['action']][] = $row['sub'];
                }
            }
        }
        
        return $acl;
    }

    /**
     *
     * @param string $type (allow, deny)
     * @param int $permission_group_id
     * @param string $resource
     */
    public static function addDefaultPermissions($type, $permission_group_id, $resource, $group_id=null)
    {
        if(is_null($group_id))
        {
            Core()->Db()->query('
                REPLACE INTO
                    `acl_permissions_default`
                SET
                    `type` = ?,
                    `permissions_group_id` = ?,
                    `group_id` = NULL,
                    `resource` = ?
            ', array(
                $type,
                $permission_group_id,
                $resource
            ));
        }
        else
        {
            Core()->Db()->query('
                REPLACE INTO
                    `acl_permissions_default`
                SET
                    `type` = ?,
                    `permissions_group_id` = ?,
                    `group_id` = ?,
                    `resource` = ?
            ', array(
                $type,
                $permission_group_id,
                $group_id,
                $resource
            ));
        }
    }
    
    public static function getDefaultPermissions($group_id = null)
    {
        try
        {
            if(is_null($group_id))
            {
                $group_id = Core()->getGroupId();
            }
            
            $all = Core()->Db()->fetchAll('
                SELECT
                    apd.`type`, apd.`resource`, apg.`name` as `group`
                FROM
                    `acl_permissions_default` apd
                INNER JOIN
                    `acl_permissions_groups` apg
                ON
                    apg.`id` = apd.`permissions_group_id`
                WHERE
                    apd.`group_id` IS NULL OR
                    apd.`group_id` = ?
                ORDER BY
                    (apd.`group_id` IS NULL) DESC
            ', array(
                $group_id
            ));
        }
        catch(Exception $e)
        {
            return array();
        }
        
        if(!is_array($all))
        {
            return array();
        }
        
        return $all;
    }

    /**
     * Returns Guest permissions
     * @return array
     */
    public static function getGuestPermissions()
    {
        return array(
            'core_login',
            'core_error'
        );
    }

    /**
     * Load ACLs of Group
     * @param mixed $groupid
     * @return array 
     */
    public function loadACLsOfGroup($groupid)
    {
        if(isset($this->_groups[$groupid]))
        {
            return;
        }

        $db = Core()->Db();

        if(is_null($this->_groups))
        {
            $sql = '
                SELECT
                    *
                FROM
                    `acl_permissions_groups`';
            $groups = $db->fetchAll($sql);
            $this->_groups = array();
            foreach($groups as $group)
            {
                $this->_groups[$group["id"]] = $group;
            }
        }

        if(!isset($this->_groups[$groupid]))
        {
            throw new Exception('Could not load ACL group');
        }
        if(!isset($this->_permissions[$groupid]))
        {
            $permissions = self::getGuestPermissions();
            if($groupid==1)
            {
                // admin
                $permissions = self::ALLRIGHTS;
            }
            else
            {
                $sql = '
                    SELECT
                        *
                    FROM
                        `acl_permissions`
                    WHERE
                        `group`= ?';

                $permissionsArray = $db->fetchAll($sql, array($groupid));

                foreach($permissionsArray as $permission)
                {
                    $permissions[$permission['id']] = $permission['permission'];
                }
            }

            $this->_permissions[$groupid] = $permissions;
        }

        return $this->_permissions[$groupid];
    }

    /**
     * Initialize ACL rules for $groupid
     * @param mixed $groupid
     * @return none 
     */
    function addACLsOfGroup($groupid)
    {
        if(isset($this->_groups[$groupid]) && isset($this->_groups[$groupid]['loaded']) && $this->_groups[$groupid]['loaded']==true)
        {
            return;
        }
        $acls = $this->loadACLsOfGroup($groupid);
        $group = $this->_groups[$groupid]['name'];

        if($group!='visitor' && $group!='guest')
        {
            $this->addRole(new Zend_Acl_Role($group), 'guest');
        }

        if($acls == self::ALLRIGHTS)
        {
            $this->allow($group, NULL);
        }
        else if(is_array($acls))
        {
            foreach($acls as $acl)
            {
                $this->allow($group, $acl);
            }
        }

        $this->_groups[$groupid]['loaded'] = true;
    }
    
    function addControllerACLs($module, $controller)
    {
        
    }
}