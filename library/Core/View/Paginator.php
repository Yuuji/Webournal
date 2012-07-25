<?php

class Core_View_Paginator
{
    public function __call($function, $arguments)
    {
        if(strtolower(substr($function, -9))==='paginator')
        {
            $function = substr($function, 0, -9);
            
            if(method_exists($this, $function))
            {
                $funcArguments = array();
                $funcArguments[] = $function;
                reset($arguments);
                foreach($arguments as $argument)
                {
                    $funcArguments[] = $argument;
                }
                return call_user_func_array(array($this, 'doPaginator'),$funcArguments);
            }
        }
        
        throw new Exception('Method does not exists');
    }
    
    private function getParam($arguments, $argument, $default=null)
    {
        $request = Core()->Front()->getRequest();
        
        $value = (isset($arguments[$argument]) ? $arguments[$argument] : false);
        
        if($value===false)
        {
            $value = $default;
        }
        
        return $value;
    }
    
    private function doPaginator($function, $arguments, $funcArguments)
    {
        $select = call_user_func_array(array($this, $function), $funcArguments);
        /* @var $select Zend_Db_Select */
        
        $page = intval($this->getParam($arguments, 'page', 1));
        $count = intval($this->getParam($arguments, 'count', 10));
        $sort = $this->getParam($arguments, 'sort', '');
        $order = $this->getParam($arguments, 'order', 'ASC');
        
        if(is_array($sort))
        {
            $sortArray = array();
            foreach($sort as $sortField)
            {
                $sortArray[] = $sortField . ' ' . $order;
            }
            
            if(count($sortArray)>0)
            {
                $select->order($sortArray);
            }
        }
        else if(!empty($sort))
        {
            $select->order($sort . ' ' . $order);
        }

        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($page);
        
        $paginator->setItemCountPerPage($count);
        $paginator->setCacheEnabled(false);
        return $paginator;
    }
}