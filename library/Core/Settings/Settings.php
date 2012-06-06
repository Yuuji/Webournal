<?php

class Core_Settings_Settings implements Iterator
{
    protected $_acl = null;
    protected $_loaded = false;
    protected $_settings = array();
    
    public function __construct($acl_group_id)
    {
        $this->_acl = $acl_group_id;
    }
    
    private function loadGroup($key, $addIfNull=true)
    {
        if(isset($this->_settings[$key]))
        {
            return;
        }
        
        $rows = Core()->Db()->fetchAll('
            SELECT
                `name`, `value`
            FROM
                `settings`
            WHERE
                `acl_group` = ? AND
                `group` = ?
        ', array(
            $this->_acl,
            $key
        ));

        if(is_array($rows) && count($rows) > 0)
        {
            $settings = array();
            reset($rows);
            foreach($rows as $row)
            {
                $settings[$row['name']] = $row['value'];
            }
            $this->_settings[$key] = new Core_Settings_Group($this->_acl, $key, $settings);
        }
        else if($addIfNull)
        {
            $this->_settings[$key] = new Core_Settings_Group($this->_acl, $key);
        }
    }
    
    public function __isset($key)
    {
        $this->loadGroup($key, false);
        return isset($this->_settings[$key]);
    }
    
    /**
     *
     * @param string $key
     * @return Core_Settings_Group 
     */
    public function __get($key)
    {
        if(!isset($this->_settings[$key]))
        {
            $this->loadGroup($key);
        }
        
        return $this->_settings[$key];
    }
    
    public function __set($key, $value)
    {
        if(!isset($this->_group) || is_null($this->_group))
        {
            throw new Exception('This is not a group');
        }
        
        Core()->Db()->query('
            REPLACE INTO
                `settings`
            SET
                `acl_group` = ?,
                `group` = ?,
                `name` = ?,
                `value` = ?
        ', array(
            $this->_acl,
            $this->_group,
            $key,
            $value
        ));
    }
    
    private function load()
    {
        if(!$this->_loaded)
        {
            $rows = Core()->Db()->fetchAll('
                SELECT
                    `group`
                FROM
                    `settings`
                WHERE
                    `acl_group` = ?
            ', array(
                $this->_acl
            ));
            
            foreach($rows as $row)
            {
                $this->loadGroup($row['group']);
            }
        }
    }
    
    public function rewind()
    {
        $this->load();
        reset($this->_settings);
    }
    
    public function current()
    {
        $this->load();
        return current($this->_settings);
    }
    
    public function key()
    {
        $this->load();
        return key($this->_settings);
    }
    
    public function next()
    {
        $this->load();
        return next($this->_settings);
    }
    
    public function valid()
    {
        $this->load();
        $key = key($this->_settings);
        return ($key !== NULL && $key !== FALSE);
    }
}