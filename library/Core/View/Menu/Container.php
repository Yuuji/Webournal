<?php

class Core_View_Menu_Container extends Zend_Navigation_Page
{
    public function getType()
    {
        return 'container';
    }
    
    public function getHref()
    {
        return false;
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