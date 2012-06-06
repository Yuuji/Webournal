<?php
function smarty_block_translate($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    if (is_null($content))
    {
        return;
    } 
    
    if(!isset($params['name']) || empty($params['name']))
    {
        return trigger_error('translate: missing param name');
    }
    
    $file = Core()->parseFilename($template->template_resource);
    
    $translation = Core()->Translations()->get($params['name'], $file['module'], $file['controller'], $file['file'], $content);
    
    if(isset($params['params']))
    {
        $paramsArray = array($translation);
        if(!is_array($params['params']))
        {
            $paramsArray[] = $params['params'];
        }
        else
        {
            foreach($params['params'] as $param)
            {
                $paramsArray[] = $param;
            }
        }
        
        $translation = call_user_func_array('sprintf', $paramsArray);
    }
    
    return $translation;
} 

?>