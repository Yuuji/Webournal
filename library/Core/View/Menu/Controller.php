<?php

class Core_View_Menu_Controller extends Zend_Navigation_Page_Mvc
{
    public function getType()
    {
        return 'controller';
    }
    
    public function getHref()
    {
        if ($this->_hrefCache) {
            return $this->_hrefCache;
        }

        $params = $this->getParams();

        if ($param = $this->getModule()) {
            $params['module'] = $param;
        }

        if ($param = $this->getController()) {
            $params['controller'] = $param;
        }

        if ($param = $this->getAction()) {
            $params['action'] = $param;
        }
        
        $url = Core()->url($params['action'], $params['controller'], $params['module']);

        return $this->_hrefCache = $url;
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