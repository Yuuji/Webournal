<?php

/**
 * Helper class
 */
class Core_Helper_Helper
{
    // Instances 
    
    private static $_php = null;
    private static $_acl = null;
    private static $_auth = null;
    private static $_accesscontrol = null;
    private static $_application = null;
    private static $_db = null;
    private static $_log = null;
    private static $_redirector = null;
    private static $_router = null;
    private static $_session = array();
    private static $_events = null;
    private static $_upload = null;
    private static $_config = null;
    private static $_translations = null;
    private static $_menu = null;
    private static $_settings = array();
    
    // Caching variables
    private static $_groupId = null;
    private static $_group = null;
    private static $_httpsSettings = null;
    
    /**
     * PHP service functions
     * @return Core_Service_PHP 
     */
    public static function PHP()
    {
        if(is_null(self::$_php))
        {
            self::$_php = new Core_Service_PHP();
        }
        
        return self::$_php;
    }
    
    
    /**
     * Sets Application
     * @param Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application 
     */
    public static function setApplication($application)
    {
        self::$_application = $application;
    }
    
    /**
     * Returns Application
     * @return Zend_Application|Zend_Application_Bootstrap_Bootstrapper 
     */
    public static function Application()
    {
        return self::$_application;
    }
    
    /**
     * Returns ACL
     * @return Core_Auth_Acl
     */
    public static function ACL()
    {
        if(is_null(self::$_acl))
        {
            self::$_acl = new Core_Auth_Acl();
        }
        
        return self::$_acl;
    }
    
    /**
     * Returns Auth
     * @return Zend_Auth 
     */
    public static function Auth()
    {
        if(is_null(self::$_auth))
        {
            self::$_auth = Zend_Auth::getInstance();
        }
        
        return self::$_auth;
    }
    
    /**
     *
     * @return Core_Auth_AccessControl
     */
    public static function AccessControl()
    {
        if(is_null(self::$_accesscontrol))
        {
            self::$_accesscontrol = new Core_Auth_AccessControl(self::Auth(), self::ACL(), self::Application()->getOption('auth'));
        }
        
        return self::$_accesscontrol;
    }

    /**
     * Returns router
     * @return Core_Route_Hostname
     */
    public static function Router()
    {
        if(is_null(self::$_router))
        {
            $config = self::Application()->getOption('resources');
            $config = new Zend_Config($config['router']['routes']['default']);
            self::$_router = Core_Route_Hostname::getInstance($config);
        }

        return self::$_router;
    }
    
    /**
     * Returns Database
     * @return Zend_Db_Adapter_Abstract or null 
     */
    public static function Db()
    {
        if(is_null(self::$_db))
        {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            
            if(!is_object($db))
            {
                self::Application()->getBootstrap()->bootstrap('db');
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            }
            
            $db->query("SET NAMES 'utf8'");
            self::$_db = $db;
        }
        return self::$_db;
    }
    
    /**
     *
     * @return Core_Helper_Config
     */
    public static function Config()
    {
        if(is_null(self::$_config))
        {
            self::$_config = new Core_Helper_Config();
        }
        
        return self::$_config;
    }
    
    /**
     *
     * @return Core_Helper_Translations
     */
    public static function Translations()
    {
        if(is_null(self::$_translations))
        {
            self::$_translations = new Core_Helper_Translations();
        }
        
        return self::$_translations;
    }
    
    /**
     * Returns Redirector Helper
     * @return Zend_Controller_Action_Helper_Redirector 
     */
    public static function Redirector()
    {
        if(is_null(self::$_redirector))
        {
            $view = self::Application()->getBootstrap()->view;
            if(!is_object($view))
            {
                self::$_redirector = new Zend_Controller_Action_Helper_Redirector();
            }
            else
            {
                try
                {
                    self::$_redirector = $view->getHelper('Redirector');
                }
                catch(Exception $e)
                {
                    self::$_redirector = new Zend_Controller_Action_Helper_Redirector();
                }
            }
        }
        return self::$_redirector;
    }
    
    /**
     * Returns Front
     * @return Zend_Controller_Front
     */
    public static function Front()
    {
        return Zend_Controller_Front::getInstance();
    }
    
    /**
     * Returns Events
     * @return Core_Helper_Events
     */
    public static function Events()
    {
        if(is_null(self::$_events))
        {
            self::$_events = new Core_Helper_Events();
        }
        return self::$_events;
    }
    
    /**
     * Returns Upload
     * @return Core_Helper_Upload
     */
    public static function Upload()
    {
        if(is_null(self::$_upload))
        {
            self::$_upload = new Core_Helper_Upload();
        }
        return self::$_upload;
    }
    
    /**
     *
     * @return Core_View_Menu_Main
     */
    public static function Menu()
    {
        if(is_null(self::$_menu))
        {
            self::$_menu = new Core_View_Menu();
        }
        return self::$_menu;
    }
    
    /**
     *
     * @return Core_Settings_Settings
     */
    public static function Settings($acl_group_id=null)
    {
        if(is_null($acl_group_id))
        {
            $acl_group_id = self::getGroupId();
        }
        
        if(!isset(self::$_settings[$acl_group_id]))
        {
            self::$_settings[$acl_group_id] = new Core_Settings_Settings($acl_group_id);
        }
        return self::$_settings[$acl_group_id];
    }
    
    /**
     * Logger
     * @param string $text
     * @param int $type 
     */
    public static function Log($text, $type = Zend_Log::INFO)
    {
        // Only if admin
        if(!self::AccessControl()->isAdmin())
        {
            return;
        }
        
        if(is_null(self::$_log))
        {
            $logger = new Zend_Log();
            $writer = new Zend_Log_Writer_Firebug();
            $logger->addWriter($writer);
            
            self::$_log = $logger;
        }
        
        self::$_log->log($text, $type);
    }
    
    public static function isREST()
    {
        $front     = Zend_Controller_Front::getInstance();
        
        $router = clone $front->getRouter();
        $rrequest = clone self::Redirector()->getRequest();
        
        $router->route($rrequest);
        
        if($rrequest->getControllerName()=='index')
        {
            // no rest!
            return false;
        }
        
        $routeName = $router->getCurrentRoute();
        
        return is_a($routeName, 'Zend_Rest_Route');
    }
    
    public static function outputREST($data, $success = true, Exception $e = null)
    {
        $output = array(
            'data' => $data,
            'success' => $success
        );
        
        if(!is_null($e))
        {
            $output['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        $json = Zend_Controller_Action_HelperBroker::getStaticHelper('jsonJsonp');
        $json->sendJson($output);
    }
    
    public static function useMainDomainOnHTTPs()
    {
        if(is_null(self::$_httpsSettings))
        {
            self::$_httpsSettings = new stdClass();
            
            self::$_httpsSettings->useHTTPs = false;
            self::$_httpsSettings->useMainDomain = false;
            self::$_httpsSettings->httpsOnly = array();
            
            $config = self::Application()->getOption('core');
            
            if(isset($config['https']))
            {
                $https = $config['https'];
                if(isset($https['enabled']) && $https['enabled']==='1')
                {
                    self::$_httpsSettings->useHTTPs = true;
                }
                
                if(isset($https['usemaindomain']) && $https['usemaindomain']==='1')
                {
                    self::$_httpsSettings->useMainDomain = true;
                }
                
                if(isset($https['httpsonly']) && is_array($https['httpsonly']))
                {
                    reset($https['httpsonly']);
                    foreach($https['httpsonly'] as $tmodule => $controllers)
                    {
                        if(!isset(self::$_httpsSettings->httpsOnly[$tmodule]))
                        {
                            self::$_httpsSettings->httpsOnly[$tmodule] = array();
                        }
                        
                        if(is_array($controllers))
                        {
                            foreach($controllers as $tcontroller => $actions)
                            {
                                if(!isset(self::$_httpsSettings->httpsOnly[$tmodule][$tcontroller]))
                                {
                                    self::$_httpsSettings->httpsOnly[$tmodule][$tcontroller] = array();
                                }
                                
                                if(is_array($actions))
                                {
                                    foreach($actions as $taction => $enabled)
                                    {
                                        if($enabled==="1")
                                        {
                                            self::$_httpsSettings->httpsOnly[$tmodule][$tcontroller][$taction] = true;
                                        }
                                    }
                                }
                                else if($actions==='1')
                                {
                                    self::$_httpsSettings->httpsOnly[$tmodule][$tcontroller] = true;
                                }
                            }
                        }
                        else if($controllers==='1')
                        {
                            self::$_httpsSettings->httpsOnly[$tmodule] = true;
                        }
                    }
                }
            }
        }
        
        return self::$_httpsSettings->useMainDomain;
    }
    
    public static function isHTTPs($module, $controller, $action)
    {
        self::useMainDomainOnHTTPs();
        
        if(self::$_httpsSettings->useHTTPs!==true)
        {
            return false;
        }
        
        if(isset(self::$_httpsSettings->httpsOnly[$module]))
        {
            if(is_array(self::$_httpsSettings->httpsOnly[$module]))
            {
                if(isset(self::$_httpsSettings->httpsOnly[$module][$controller]))
                {
                    if(is_array(self::$_httpsSettings->httpsOnly[$module][$controller]))
                    {
                        if(isset(self::$_httpsSettings->httpsOnly[$module][$controller][$action]))
                        {
                            if(self::$_httpsSettings->httpsOnly[$module][$controller][$action]===true)
                            {
                                return true;
                            }
                        }
                    }
                    else if(self::$_httpsSettings->httpsOnly[$module][$controller]===true)
                    {
                        return true;
                    }
                }
            }
            else if(self::$_httpsSettings->httpsOnly[$module]===true)
            {
                return true;
            }
                
        }
        
        return false;
    }
    
    /**
     * Generate url
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @param boolean $resetGroup
     * @return string
     */
    public static function url($action=null, $controller=null, $module = null, $params = null, $resetGroup = false)
    {
        $request = self::Front()->getRequest();
        if(!is_null($action) && !is_null($controller) && is_null($module))
        {
            $module = self::getDefaultModule();
        }
        else if(is_null($module))
        {
            $module = $request->getModuleName();
        }
        
        if(is_null($action))
        {
            $action = $request->getActionName();
        }
        
        if(is_null($controller))
        {
            $controller = $request->getControllerName();
        }
        
        $router = self::Router();
        $data = array(
            'action' => $action,
            'controller' => $controller,
            'module' => $module
        );

        if($resetGroup===false)
        {
            $group = self::getGroup();

            if(is_null($params))
            {
                $params = array();
            }

            if($group!==false)
            {
                $group = $group['name'];
                $params['group'] = $group;
            }
        }
        
        if(is_array($params))
        {
            $data = array_merge($data, $params);
        }

        if(isset($data['module']) && $data['module']==self::getDefaultModule())
        {
            unset($data['module']);
        }
        
        $useMainDomain = false;
        
        $router->setScheme('http');
        if(self::isHTTPs($module, $controller, $action))
        {
            $router->setScheme('https');
            
            if(self::useMainDomainOnHTTPs())
            {
                $useMainDomain = true;
            }
        }
        
        $url = $router->assemble($data, true, false, false, $useMainDomain);
        
        $router->setScheme('http');
        
        return $url;
    }

    /**
     * Redirect to url
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @param boolean $resetGroup
     */
    public static function redirect($action, $controller = null, $module = null, $params = null, $resetGroup = false)
    {
        if(self::isREST())
        {
            return self::outputREST('', false, new Exception('Access denied', 100));
        }
     
        if(is_null($controller))
        {
            $controller = self::Redirector()->getRequest()->getControllerName();
        }

        if(is_null($module))
        {
            $module = self::Redirector()->getRequest()->getModuleName();
        }
        
        if($resetGroup===false)
        {
            $group = self::getGroup();

            if(is_null($params))
            {
                $params = array();
            }

            if($group!==false)
            {
                $group = $group['name'];
                $params['group'] = $group;
            }
        }
        
        $url = self::url($action, $controller, $module, $params, $resetGroup);
        self::Redirector()->gotoUrlAndExit($url);
    }
    
    /**
     * Reset Group and redirect to url
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     */
    public static function redirectMain($action, $controller, $module = null, $params = null)
    {
        if(is_null($module))
        {
            $module = self::getDefaultModule();
        }
        
        self::redirect($action, $controller, $module, $params, true);
    }
    
    /**
     * Returns URL to the group
     * @param int $groupId 
     */
    public static function urlToGroup($groupId)
    {
        $urls = self::Db()->fetchRow('
            SELECT a.`url`
            FROM  `acl_groups` a
            WHERE
                a.id = ?
        ', array($groupId));
        
        
        $params['group'] = $urls['url'];
        
        return self::url('index', 'index', '', $params, true);
    }
    
    /**
     * Returns default Module
     * @return string
     */
    public static function getDefaultModule()
    {
        $config = self::Application()->getOption('resources');
        if(isset($config['frontController']) && isset($config['frontController']['moduleDefault']))
        {
            return $config['frontController']['moduleDefault'];
        }
        else
        {
            return 'default';
        }
    }
    
    /**
     * Returns session for given namespace
     * @param string $namespace
     * @return Zend_Session_Namespace 
     */
    public static function Session($namespace, $global=false)
    {
        $groupId = self::getGroupId();
        if($groupId!==false && !$global)
        {
            $namespace = '#' . $groupId . '#' . $namespace;
        }
        
        if(!isset(self::$_session[$namespace]))
        {
            self::$_session[$namespace] = new Zend_Session_Namespace($namespace);
        }
        
        return self::$_session[$namespace];
    }
    
    /**
     * Returns id of group or false if not exists
     * @param string $urlname
     * @return int|false 
     */
    private static function checkGroupId($urlname)
    {
        $id = self::Db()->fetchOne('
            SELECT
                `id`
            FROM
                `acl_groups`
            WHERE
                `url` = ?
        ', array($urlname));
        
        return $id;
    }
    
    /**
     * Returns current group id
     * @return int|false
     */
    public static function getGroupId()
    {
        if(is_null(self::$_groupId) || is_null(self::$_group))
        {
            $request = self::Front()->getRequest();
            $match = self::Router()->match($request);
            
            if(!$request->isDispatched())
            {
                $match = self::Router()->match($request);

                $group = (isset($match['group']) ? $match['group'] : false);
                
                if($group===false || empty($group))
                {
                    return 0;
                }
                
                $id = self::checkGroupId($group);
                
                if($id!==false && intval($id)===1)
                {
                    $id = 0;
                }
                
                if($id!==false)
                {
                    self::$_groupId = $id;
                    self::$_group = array(
                        'name' => $group,
                        'id' => $id
                    );
                }
                
                return $id;
            }
            else
            {
                $group = ($request->group ? $request->group : false);
                
                if($group===false || empty($group))
                {
                    if($group===false && isset($match['group']) && !empty($match['group']))
                    {
                        self::$_groupId = false;
                    }
                    else
                    {
                        self::$_groupId = 0;
                    }
                }
                else
                {
                    $id = self::checkGroupId($group);
                    
                    if($id!==false && intval($id)===1)
                    {
                        $id = 0;
                    }

                    if($id===false)
                    {
                        self::$_groupId = false;
                    }
                    else
                    {
                        self::$_groupId = $id;
                        self::$_group = array(
                            'name' => $group,
                            'id' => $id
                        );
                    }
                }
            
                if(self::$_groupId===false)
                {
                    self::$_group = array();
                }
            }
        }
        
        return self::$_groupId;
    }

    
    /**
     * Returns username
     * @return string username
     */
    public static function getUsername()
    {
        if(!self::Auth()->hasIdentity())
        {
            return false;
        }
        else
        {
            $storage = self::Auth()->getStorage()->read();
            return $storage['username'];
        }
    }

    
    /**
     * Returns user id
     * @return int userid
     */
    public static function getUserId()
    {
        if(!self::Auth()->hasIdentity())
        {
            return false;
        }
        else
        {
            $storage = self::Auth()->getStorage()->read();
            return $storage['userid'];
        }
    }
    
    
    /**
     * Return group
     * @return array
     */
    public static function getGroup()
    {
        if(is_null(self::$_group))
        {
            self::getGroupId();
        }
        
        return self::$_group;
    }
    
    
    /**
     * Returns group data
     * @param int $id group id
     * @return array group dara
     */
    public static function getGroupData($id)
    {
        $data = self::Db()->fetchRow('
            SELECT
                *
            FROM
                `acl_groups`
            WHERE
                `id` = ?
        ', $id);
        
        $data['active'] = self::AccessControl()->checkIfGroupHasAdmin($id);
        
        return $data;
    }
    
    
    /**
     * Generates URL name for group name
     * @param string $name
     * @return string 
     */
    public static function getURLName($name)
    {
        $name = strtolower($name);
        $name = str_replace(array('ä','ö','ü'), array('ae','oe','ue'), $name);
        return preg_replace('/[^a-zA-Z0-9-]/', '', $name);
    }
    
    
    /**
     * Set return url for pre processing modules
     * @param string $action
     * @param string $controller
     * @param string|null $module 
     */
    public static function setReturnUrl($action, $controller, $module = null)
    {
        $session = self::Session('core_helper');
        $session->returnurl = array(
            'action'        => $action,
            'controller'    => $controller,
            'module'        => $module
        );
    }
    
    
    /**
     * Redirects to return url (setReturnUrl)
     */
    public static function redirectToReturnUrl()
    {
        $session = self::Session('core_helper');
        $url = $session->returnurl;
        
        if(!isset($url) || !is_array($url))
        {
            throw new Exception('Something goes wrong #help');
        }
        
        self::redirect($url['action'], $url['controller'], $url['module']);
    }
    
    
    /**
     * Returns sender email address
     * @return string
     */
    public static function getSenderMailAddress()
    {
        $config = self::Application()->getOption('email');
        
        return $config['sender'];
    }
    
    /**
     * checks is user has rights
     * @param string $module Modulename
     * @param string $controller Controllername
     * @param string $action Actionname
     * @param string $sub Subactionname
     * @return boolean true if has rights
     */
    public static function checkAccessRights($module, $controller, $action, $sub = null)
    {
        $acl = self::ACL();
        $resource = null;
        if(!is_null($sub) && $acl->has($module . '_' . $controller . '_' . $action . '_' . $sub))
        {
            $resource = $module . '_' . $controller . '_' . $action . '_' . $sub;
        }
        else if(!is_null($action) && $acl->has($module . '_' . $controller . '_' . $action))
        {
            $resource = $module . '_' . $controller . '_' . $action;
        }
        else if(!is_null($controller) && $acl->has($module . '_' . $controller))
        {
            $resource = $module . '_' . $controller;
        }
        else if(!is_null($module) && $acl->has($module))
        {
            $resource = $module;
        }
        
        if($acl->isAllowed(self::AccessControl()->getPermissionGroupId(), $resource))
        {
            return true;
        }
        
        return false;
    }

    /**
     * Crypts password
     * @param string $password
     * @return string
     */
    public static function cryptPassword($password)
    {
        $crypted = '';

        $config = self::Application()->getOption('core');
        $salt = (string)$config['crypt']['salt'];

        $crypted = crypt($password, '$6$rounds=5000$' . $salt . '$');

        return $crypted;
    }

    /**
     * Returns main domain
     * @return string domain
     */
    public static function getMainDomain()
    {
        $config = self::Application()->getOption('resources');
        $domain = (string)$config['router']['routes']['default']['static'];

        return $domain;
    }
    
    /**
     * Returns temp directory for file upload
     * @return string
     */
    public static function getTempUploadDirectory()
    {
        $config = $this->Config()->directories;
        
        if(!is_null($config) && isset($config->temp) && isset($config->temp->files))
        {
            return (string)$config->temp->files;
        }
        return false;
    }
    
    /**
     * Returns max age for temp files
     * @return string
     */
    public static function getTempMaxAge()
    {
        $config = $this->Config()->directories;
        
        if(!is_null($config) && isset($config->temp) && isset($config->temp->maxage))
        {
            return (string)$config->temp->maxage;
        }
        return false;
    }
    
    /**
     * Returns public directory for file upload
     * @return string
     */
    public static function getPublicUploadDirectory()
    {
        $config = $this->Config()->directories;
        
        if(!is_null($config) && isset($config->public) && isset($config->public->files))
        {
            return (string)$config->public->files;
        }
        return false;
    }
    
    /**
     * Returns public path for file upload
     * @return string
     */
    public static function getPublicUploadPath()
    {
        $config = $this->Config()->path;
        
        if(!is_null($config) && isset($config->public) && isset($config->public->files))
        {
            return (string)$config->public->files;
        }
        return false;
    }
    
    public static function getLoginConfig()
    {
        $config = self::Config()->login;
        
        $return = new stdClass();
        
        $return->register = new stdClass();
        $return->register->allow = false;
        $return->regsiter->allowwithoutemail = false;
        
        if(!is_null($config) && isset($config->register))
        {
            if(isset($config->register->allow))
            {
                $return->register->allow = ($config->register->allow === '1' ? true : false);
            }
            
            if(isset($config->register->allowwithoutemail))
            {
                $return->register->allowwithoutemail = ($config->register->allowwithoutemail === '1' ? true : false);
            }
        }
        
        return $return;
    }
    
    public static function arrayToObject($array)
    {
        if(!is_array($array))
        {
            return $array;
        }

        $object = new stdClass();
        if (is_array($array) && count($array) > 0)
        {
            $isNumeric = true;
            reset($array);
            foreach ($array as $name=>$value)
            {
                if(!is_numeric($name))
                {
                    $isNumeric = false;
                }
                
                $name = strtolower(trim($name));
                if (!empty($name) || $name===0 || $name==='0')
                {
                    $value = self::arrayToObject($value);
                    $object->$name = $value;
                    $array[$name] = $value;
                }
            }
            if($isNumeric)
            {
                return $array;
            }
            return $object; 
        }
        else
        {
            return false;
        }
    }
    
    public static function parseFilename($file)
    {
        $extPos = strrpos($file, '.');
        $ext = substr($file, $extPos+1);
        
        $file = substr($file, 0, $extPos);
        switch($ext)
        {
            case 'tpl':
                $actionPos = strrpos($file, '/');
                if($actionPos===false)
                {
                    $action = $file;
                    $controller = null;
                    $module = null;
                    break;
                }
                $action = substr($file, $actionPos+1);
                
                $file = substr($file, 0, $actionPos);
                
                $controllerPos = strrpos($file, '/');
                $controller = substr($file, $controllerPos+1);
                
                $file = substr($file, 0, $controllerPos);
                $file = substr($file, 0, strrpos($file, '/'));
                $file = substr($file, 0, strrpos($file, '/'));
                
                $modulePos = strrpos($file, '/');
                $module = substr($file, $modulePos+1);
                
                if($controller==='templates')
                {
                    $controller = null;
                    $action = null;
                }
                
                break;
            default:
                throw new Exception('Not implemented yet');
        }
        
        return array(
            'module' => $module,
            'controller' => $controller,
            'file' => $action
        );
    }
}