<?php

class Core_Settings_Group extends Core_Settings_Settings
{
    protected $_group = null;
    protected $_settings = array();
    
    public function __construct($acl_group_id, $group, array $settings = array())
    {
        parent::__construct($acl_group_id);
        
        $this->_group = $group;
        $this->_settings = $settings;
        
        $this->_loaded = true;
    }
    
    public function __get($key)
    {
        if(isset($this->_settings[$key]))
        {
            return $this->_settings[$key];
        }
        
        return null;
    }
    
    public function __set($key, $value)
    {
        $this->_settings[$key] = $value;
        parent::__set($key, $value);
    }
}