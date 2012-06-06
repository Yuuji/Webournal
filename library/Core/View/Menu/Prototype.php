<?php

class Core_View_Menu_Prototype
{
    public static function getChilds(Zend_Navigation_Page $entry)
    {
        $select = Core()->Db()->select()->from('menu');
        
        if(!is_null($entry))
        {
            if(method_exists($entry, 'getId'))
            {
                $id = $entry->getId();
            }
            else
            {
                return array();
            }
        }
        
        if(is_null($id))
        {
            $select->where('`parent` IS NULL');
        }
        else
        {
            $select->where('`parent` = ?', $entry->getId());
        }
        
        $select->order('order');
        
        return Core()->Db()->fetchAll($select);
    }
    
    public static function addChilds(Zend_Navigation_Page $entry, $loadChilds = false)
    {
        $childs = self::getChilds($entry);
        
        foreach($childs as $child)
        {
            switch($child['type'])
            {
                case 'container':
                    $childEntry = self::_addContainerEntry($entry, $child['label'], $child['defaultTranslation'], $child['order'], $child['class'], $child['id']);
                    break;
                case 'controller':
                    try
                    {
                        if(!empty($child['params']))
                        {
                            $child['params'] = unserialize($child['params']);
                        }
                    }
                    catch(Exception $e)
                    {
                        $child['params'] = '';
                    }
                    
                    if(empty($child['params']) || !is_array($child['params']))
                    {
                        $child['params'] = array();
                    }
                    
                    $childEntry = self::_addControllerEntry($entry, $child['label'], $child['defaultTranslation'], $child['action'], $child['controller'], $child['module'], $child['params'], $child['order'], $child['class'], $child['id']);
                    break;
                case 'url':
                    $childEntry = self::_addURLEntry($entry, $child['label'], $child['defaultTranslation'], $child['url'], $child['order'], $child['class'], $child['id']);
                    break;
                default:
                    throw new Exception('Unknown menu type');
            }
            
            if($loadChilds)
            {
                self::addChilds($childEntry, true);
            }
        }
    }
    
    public static function addControllerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $action, $controller, $module, $params=array(), $order=0, $class='')
    {
        if(is_a($entry, 'Core_View_Menu_Main'))
        {
            $parent = new Zend_Db_Expr('NULL');
        }
        else
        {
            try
            {
                if(!method_exists($entry, 'getId'))
                {
                    throw new Exception('');
                }
                $parent = $entry->getId();
            }
            catch(Exception $e)
            {
                throw new Exception('Parent entry not allowed');
            }
        }
        
        if(Core()->Db()->insert('menu', array(
                'parent'                => $parent,
                'label'                 => $label,
                'defaultTranslation'    => $defaultTranslation,
                'type'                  => 'controller',
                'module'                => $module,
                'controller'            => $controller,
                'action'                => $action,
                'params'                => (empty($params) ? '' : serialize($params)),
                'url'                   => new Zend_Db_Expr('NULL'),
                'class'                 => $class,
                'order'                 => $order
            ))==0)
        {
            throw new Exception('Cannot add menu entry');
        }
        
        $id = Core()->Db()->lastInsertId('menu');
        
        return self::_addControllerEntry($entry, $label, $defaultTranslation, $action, $controller, $module, $params, $order, $class, $id);
    }
    
    public static function addTemporaryControllerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $action, $controller, $module, $params=array(), $order=0, $class='')
    {
       return self::_addControllerEntry($entry, $label, $defaultTranslation, $action, $controller, $module, $params, $order, $class);
    }
    
    private static function _addControllerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $action, $controller, $module, $params=array(), $order=0, $class='', $id=null)
    {
        $page = new Core_View_Menu_Controller(array(
            'label' => $label,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'params' => $params,
            'class' => $class,
            'id' => $id,
            'order' => $order,
            'defaultTranslation' => $defaultTranslation
        ));
        $entry->addPage($page);
        
        return $page;
    }
    
    public static function addURLEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $url, $order=0, $class='')
    {
        if(is_a($entry, 'Core_View_Menu_Main'))
        {
            $parent = new Zend_Db_Expr('NULL');
        }
        else
        {
            try
            {
                if(!method_exists($entry, 'getId'))
                {
                    throw new Exception('');
                }
                $parent = $entry->getId();
            }
            catch(Exception $e)
            {
                throw new Exception('Parent entry not allowed');
            }
        }
        
        if(Core()->Db()->insert('menu', array(
                'parent'                => $parent,
                'label'                 => $label,
                'defaultTranslation'    => $defaultTranslation,
                'type'                  => 'url',
                'module'                => new Zend_Db_Expr('NULL'),
                'controller'            => new Zend_Db_Expr('NULL'),
                'action'                => new Zend_Db_Expr('NULL'),
                'params'                => new Zend_Db_Expr('NULL'),
                'url'                   => $url,
                'class'                 => $class,
                'order'                 => $order
            ))==0)
        {
            throw new Exception('Cannot add menu entry');
        }
        
        $id = Core()->Db()->lastInsertId('menu');
        
        self::_addURLEntry($entry, $label, $defaultTranslation, $url, $order, $class, $id);
    }
    
    public static function addTemporaryURLEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $url, $order=0, $class='')
    {
        return self::_addURLEntry($entry, $label, $defaultTranslation, $url, $order, $class);
    }
    
    private static function _addURLEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $url, $order=0, $class='', $id=null)
    {
        $page = new Core_View_Menu_Url(array(
            'label' => $label,
            'class' => $class,
            'uri' => $url,
            'id' => $id,
            'order' => $order,
            'defaultTranslation' => $defaultTranslation
        ));
        $entry->addPage($page);
        
        return $page;
    }
    
    public static function addContainerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $order=0, $class='')
    {
        if(is_a($entry, 'Core_View_Menu_Main'))
        {
            $parent = new Zend_Db_Expr('NULL');
        }
        else
        {
            try
            {
                if(!method_exists($entry, 'getId'))
                {
                    throw new Exception('');
                }
                $parent = $entry->getId();
            }
            catch(Exception $e)
            {
                throw new Exception('Parent entry not allowed');
            }
        }
        
        if(Core()->Db()->insert('menu', array(
                'parent'                => $parent,
                'label'                 => $label,
                'defaultTranslation'    => $defaultTranslation,
                'type'                  => 'container',
                'module'                => new Zend_Db_Expr('NULL'),
                'controller'            => new Zend_Db_Expr('NULL'),
                'action'                => new Zend_Db_Expr('NULL'),
                'params'                => new Zend_Db_Expr('NULL'),
                'url'                   => new Zend_Db_Expr('NULL'),
                'class'                 => $class,
                'order'                 => $order
            ))==0)
        {
            throw new Exception('Cannot add menu entry');
        }
        
        $id = Core()->Db()->lastInsertId('menu');
        
        return self::_addContainerEntry($entry, $label, $defaultTranslation, $order, $class, $id);
    }
    
    public static function addTemporaryContainerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $order=0, $class='')
    {
        return self::_addURLEntry($entry, $label, $defaultTranslation, $order, $class);
    }
    
    private static function _addContainerEntry(Zend_Navigation_Page $entry, $label, $defaultTranslation, $order=0, $class='', $id=null)
    {
        $page = new Core_View_Menu_Container(array(
            'label' => $label,
            'class' => $class,
            'id' => $id,
            'order' => $order,
            'defaultTranslation' => $defaultTranslation
        ));
        $entry->addPage($page);
        
        return $page;
    }
    
    public static function removeEntry(Zend_Navigation_Page $parent, Zend_Navigation_Page $entry)
    {
        $where = Core()->Db()->quoteInto('`label` = ?', $entry->getLabel());
        
        Core()->Db()->delete('menu', $where);
        
        if(!is_null($parent))
        {
            $parent->removePage($entry);
        }
    }
    
    public static function editLabel(Zend_Navigation_Page $entry, $label, $defaultTranslation)
    {
        $where = Core()->Db()->quoteInto('`label` = ?', $entry->getLabel());
        Core()->Db()->update('menu', array(
            'label' => $label,
            'defaultTranslation' => $defaultTranslation
        ), $where);
        
        $entry->setLabel($label);
        $entry->set('defaultTranslation', $defaultTranslation);
    }
    
    public static function editOrder(Zend_Navigation_Page $entry, $order)
    {
        $where = Core()->Db()->quoteInto('`label` = ?', $entry->getLabel());
        Core()->Db()->update('menu', array(
            'order' => $order
        ), $where);
        
        $entry->setOrder($order);
    }
}