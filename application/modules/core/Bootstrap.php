<?php

class core_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initAuthAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'core_',
            'basePath'   => APPLICATION_PATH . '/modules/core',
            'resourceTypes' => array (
                'form' => array(
                    'path'      => 'forms',
                    'namespace' => 'Form',
                ),
                'model' => array(
                    'path'      => 'models',
                    'namespace' => 'Model',
                ),
                'service' => array(
                    'path'      => 'service',
                    'namespace' => 'Service',
                )
            )
        ));
        return $autoloader;
    }
    
    public function _initRouter()
    {
        /*$front     = Zend_Controller_Front::getInstance();
        $restRoute = new Zend_Rest_Route($front, array(), array(
            'core' => array('rest_login', 'rest_directory', 'rest_upload', 'rest_file')
        ));
        $front->getRouter()->addRoute('core_rest', $restRoute);
        
        $front->registerPlugin(new Zend_Controller_Plugin_PutHandler()); */
    }
}
