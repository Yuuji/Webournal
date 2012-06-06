<?php
function smarty_block_ifallowed($params, $content, $template, &$repeat)
{
    if (is_null($content))
    {
        return;
    } 
    
    if(!isset($params['module']) || empty($params['module']))
    {
        return trigger_error('ifallowed: missing param module');
    }
    
    $module = $params['module'];
    
    $controller = false;
    $action = false;
    $sub = false;
    
    if(isset($params['controller']) && !empty($params['controller']))
    {
        $controller = $params['controller'];
    }
    
    if(isset($params['action']) && !empty($params['action']))
    {
        if($controller===false)
        {
            return trigger_error('ifallowed: missing param controller');
        }
        $action = $params['action'];
    }
    
    if(isset($params['sub']) && !empty($params['sub']))
    {
        if($controller===false)
        {
            return trigger_error('ifallowed: missing param controller');
        }
        if($action===false)
        {
            return trigger_error('ifallowed: missing param action');
        }
        $sub = $params['sub'];
    }

    $resource = '';
    
    if($sub!==false && Core()->ACL()->has($module . '_' . $controller . '_' . $action . '_' . $sub))
    {
        $resource = $module . '_' . $controller . '_' . $action . '_' . $sub;
    }
    else if($action!==false && Core()->ACL()->has($module . '_' . $controller . '_' . $action))
    {
        $resource = $module . '_' . $controller . '_' . $action;
    }
    else if($controller!==false && Core()->ACL()->has($module . '_' . $controller))
    {
        $resource = $module . '_' . $controller;
    }
    else if(Core()->ACL()->has($module))
    {
        $resource = $module;
    }
    else
    {
        $resource = null;
    }
    
    if(Core()->ACL()->isAllowed(Core()->AccessControl()->getPermissionGroupId(), $resource))
    {
        return $content;
    }
} 

?>