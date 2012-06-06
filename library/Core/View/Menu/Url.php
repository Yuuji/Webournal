<?php

class Core_View_Menu_Url extends Zend_Navigation_Page_Uri
{
    public function getType()
    {
        return 'url';
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