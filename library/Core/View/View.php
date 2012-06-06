<?php

abstract class Core_View_View extends Zend_View_Abstract
{
    private function pagenationControlSetParam($key, &$params)
    {
        if(!isset($params[$key]))
        {
            $value = $this->getEngine()->getTemplateVars($key);

            if(!is_null($value))
            {
                $params[$key] = $value;
            }
        }
    }
    
    public function paginationControlParams($paginator, $params=null)
    {
        return $this->paginationControl($paginator, null, null, array('paginatorParams' => $params));
    }
    
    public function paginationControl($paginator, $type=null, $tpl = null, $params=null)
    {
        $type = is_null($type) ? 'Sliding' : $type;
        $tpl  = is_null($tpl)  ? 'static/pagination_control.tpl' : $tpl;
        
        if(is_array($params))
        {
            $paginatorParams = $params;
        }
        else
        {
            $paginatorParams = array('paginatorParams' => array());
        }
        
        $this->pagenationControlSetParam('paginatorAllowedCounts', $paginatorParams);
        $this->pagenationControlSetParam('paginatorPage', $paginatorParams);
        $this->pagenationControlSetParam('paginatorCount', $paginatorParams);
        $this->pagenationControlSetParam('paginatorSort', $paginatorParams);
        $this->pagenationControlSetParam('paginatorOrder', $paginatorParams);
        
        return parent::paginationControl($paginator, $type, $tpl, $paginatorParams);
    }
    
    public function Core()
    {
        return Core();
    }
}