<?php

class Core_Helper_Config
{
    private $_config = null;
    
    public function __construct()
    {
        $this->_config = Core()->arrayToObject(Core()->Application()->getOption('core'));
    }
    
    public function __get($key)
    {
        if(isset($this->_config->$key))
        {
            return $this->_config->$key;
        }
        
        return null;
    }
}