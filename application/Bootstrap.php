<?php

/**
 * @todo Nicht optimal gelöst
 * @return Core_Helper_Helper
 */
function Core()
{
    global $helper_instance;
    
    if(!isset($helper_instance))
    {
        $helper_instance = new Core_Helper_Helper();
    }
    
    return $helper_instance;
}

/**
 * Application bootstrap
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     *
     * @var Zend_Auth
     */
    private $_auth;
    
    /**
     *
     * @var Core_Auth_Acl
     */
    private $_acl;
    
    public function __construct($application)
    {
        Core()->setApplication($application);
        
        $this->bootstrap('session');
        
        parent::__construct($application);
        
        /* @todo Entfernen sobald alle Stricts raussind */
        Core()->Front()->throwExceptions(true);
        error_reporting(E_ALL);

        $this->bootstrap('request');
        $this->bootstrap('installer');
        
        Zend_Controller_Action_HelperBroker::addPrefix('Core_ActionHelper');
    }
    
    /**
     * Runs installer and updater
     */
    public function _initInstaller()
    {
        Core_Installer_Installer::run();
	Core_Installer_Updater::run();
    }
    
    /**
     * Initialize Smarty Object
     * @return Core_View_Smarty
     */
    protected function _initView()
    {
        // initialize smarty view
        $view = new Core_View_Smarty($this->getOption('smarty'));

        // setup viewRenderer with suffix and view
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        /* @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer->setViewSuffix('tpl');
        $viewRenderer->setView($view);

        // ensure we have layout bootstraped
        $this->bootstrap('layout');
        // set the tpl suffix to layout also
        $layout = Zend_Layout::getMvcInstance();
        $layout->setViewSuffix('tpl');
        $view->setEncoding('UTF-8');

        $this->_auth = Core()->Auth();
        $this->_acl = Core()->ACL();

        $view->Core = Core();
        $view->auth = $this->_auth;
        $view->acl = $this->_acl;

        $view->maindomain = Core()->getMainDomain();
        
        return $view;
    }

    /**
     * Initialize Authentification object
     */
    protected function _initAuth()
    {
        $this->bootstrap('frontController');
        $auth = Core()->Auth();
        $accesscontrol = Core()->AccessControl();
        
        $this->getResource('frontController')->registerPlugin($accesscontrol)->setParam('auth', $auth);
    }
    
    protected function _initAutoLoader()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array( 
            'namespace' => '', 
            'basePath'  => APPLICATION_PATH
        )); 
        return $autoloader; 
    }

    public function _initRequest()
    {
        $this->bootstrap('frontController');
        $front = $this->getResource('frontController');
        $front->setRequest(new Zend_Controller_Request_Http());
    }
    
    public function _initSession()
    {
        Zend_Session::setSaveHandler(new Core_Session_File());
 
        //start your session
        Zend_Session::start();
    }
}

