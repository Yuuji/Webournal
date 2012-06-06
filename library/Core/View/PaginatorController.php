<?php

class Core_View_PaginatorController extends Zend_Controller_Action
{
    protected $_page = 1;
    protected $_count = 10;
    protected $_sort = null;
    protected $_sortKey = null;
    protected $_order = 'ASC';
    
    protected $_allowedSort = null;
    protected $_allowedCounts = array(10, 20, 50, 100, 200, 500);
    
    /**
     * Do pagination
     * @param array|string $callFunc
     * @param mixed Argument1, argument2, ...
     * @return Zend_Pagination 
     */
    public function Pagination($callFunc)
    {
        $defaultSort = false;
        
        if(is_array($callFunc))
        {
            if(method_exists($callFunc[0], $callFunc[1] . 'AllowedSort'))
            {
                $this->setAllowedSort(call_user_func_array(array($callFunc[0], $callFunc[1] . 'AllowedSort'), array()));
            }
            
            if(method_exists($callFunc[0], $callFunc[1] . 'DefaultSort'))
            {
                $defaultSort = call_user_func_array(array($callFunc[0], $callFunc[1] . 'DefaultSort'), array());
            }
            
            $callFunc[1] .= 'Paginator';
        }
        else
        {
            $callFunc .= 'Paginator';
        }
        
        $this->setPage($this->_request->getParam('page', false));
        $this->setCount($this->_request->getParam('count', false));
        $this->setSort($this->_request->getParam('sort', $defaultSort));
        $this->setOrder($this->_request->getParam('order', false));
        
        $this->view->paginatorAllowedCounts = $this->_allowedCounts;
        
        $funcArguments = array_slice(func_get_args(),1);
        
        return call_user_func($callFunc, array(
            'page' => $this->_page,
            'count' => $this->_count,
            'sort' => $this->_sort,
            'order' => $this->_order
        ), $funcArguments);
    }
    
    protected function setPage($page)
    {
        if(is_numeric($page) && intval($page)==$page)
        {
            $this->_page = $page;
        }
        
        $this->view->paginatorPage = $this->_page;
    }
    
    protected function setCount($count)
    {
        if(is_numeric($count) && intval($count)==$count)
        {
            $count = intval($count);
            if($count>0 || $count==-1)
            {
                $this->_count = $count;
            }
        }
        $this->view->paginatorCount = $this->_count;
    }
    
    protected function setSort($sort)
    {
        if(empty($sort))
        {
            $this->_sort = null;
            $this->_sortKey = null;
        }
        else
        {
            if(!is_null($this->_allowedSort))
            {
                if(isset($this->_allowedSort[$sort]))
                {
                    $this->_sort = $this->_allowedSort[$sort];
                    $this->_sortKey = $sort;
                }
            }
            else
            {
                $this->_sort = $sort;
                $this->_sortKey = $sort;
            }
        }
        $this->view->paginatorSort = $this->_sortKey;
    }
    
    protected function setOrder($order)
    {
        if(strtolower($order)==='asc')
        {
            $this->_order = 'ASC';
        }
        else if(strtolower($order)==='desc')
        {
            $this->_order = 'DESC';
        }
        
        $this->view->paginatorOrder = $this->_order;
    }
    
    protected function setAllowedSort($allowedSort)
    {
        $this->_allowedSort = $allowedSort;
    }
    
    protected function setAllowedCounts($allowedCounts)
    {
        $this->_allowedCounts = $allowedCounts;
    }
}