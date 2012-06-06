<?php

class Core_View_Menu_Main extends Zend_Navigation_Page
{
    private static $_prototype = null;
    
    public function __construct()
    {
        self::Prototype()->addChilds($this, true);
    }
    
    /**
     *
     * @return Core_View_Menu_Prototype 
     */
    public static function Prototype()
    {
        if(is_null(self::$_prototype))
        {
            self::$_prototype = new Core_View_Menu_Prototype();
        }
        
        return self::$_prototype;
    }
    
    public function getHref()
    {
        return false;
    }
    
    public function getType()
    {
        return 'main';
    }
    
    public function __call($name, $arguments)
    {
        if(method_exists(Core()->Menu()->Prototype(), $name))
        {
            $parameters = array();
            $parameters[] = $this;
            foreach($arguments as $argument)
            {
                $parameters[] = $argument;
            }
            return call_user_func_array(array(Core()->Menu()->Prototype(), $name), $parameters);
        }
        
        throw new BadMethodCallException();
    }
}