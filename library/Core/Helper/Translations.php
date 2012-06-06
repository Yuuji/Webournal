<?php

class Core_Helper_Translations
{
    public function getLanguage()
    {
        /** @todo implement */
        return 'german';
    }
    
    public function get($name, $module=null, $controller=null, $action=null, $default=null)
    {
        $sql = '
            SELECT
                `value`
            FROM
                `translations`
            WHERE
                `name` = ?
        ';
        $values = array($name);
        
        if(!is_null($module))
        {
            $sql .= ' AND
                (`module` IS NULL OR
                    (`module` = ?
            ';
            $values[] = $module;
            
            if(!is_null($controller))
            {
                $sql .= ' AND
                    (`controller` IS NULL OR
                        (`controller` = ?
                ';
                
                $values[] = $controller;
                
                if(!is_null($action))
                {
                    $sql .= ' AND
                        (`action` IS NULL OR
                        `action` = ?)
                    ';
                    $values[] = $action;
                }
                else
                {
                    $sql .= ' AND
                        `action` IS NULL
                    ';
                }
                
                $sql .= '
                        )
                    )
                ';
            }
            else
            {
                $sql .= ' AND
                    `controller` IS NULL AND
                    `action` IS NULL
                ';
            }
            
            $sql .= '
                    )
                )
            ';
        }
        else
        {
            $sql .= ' AND
                `module` IS NULL AND
                `controller` IS NULL AND
                `action` IS NULL
            ';
        }
        
        $sql .= ' AND `language` = ?';
        $values[] = $this->getLanguage();
        
        $sql .= '
            ORDER BY
                (`module` IS NULL) ASC,
                (`controller` IS NULL) ASC,
                (`action` IS NULL) ASC
        ';
        
        $value = Core()->Db()->fetchOne($sql, $values);

        if($value===false)
        {
            $this->addMissing($name, $module, $controller, $action, $default);
            if(!is_null($default))
            {
                return $default;
            }
            return false;
        }
        
        return $value;
    }
    
    public function addMissing($name, $module=null, $controller=null, $action=null, $default=null)
    {
        $sql = '
            REPLACE INTO
                `translations_missing`
            SET
                `name` = ?,
                `language` = ?
        ';
        
        $values = array($name, $this->getLanguage());
        
        if(!is_null($module))
        {
            $sql .= ',
                `module` = ?
            ';
            $values[] = $module;
            
            if(!is_null($controller))
            {
                $sql .= ',
                    `controller` = ?
                ';
                $values[] = $controller;
                
                if(!is_null($action))
                {
                    $sql .= ',
                        `action` = ?
                    ';
                    $values[] = $action;
                }
            }
        }
        
        if(!is_null($default))
        {
            $sql .= ',
                `default_value` = ?
            ';
            $values[] = $default;
        }
        
        Core()->Db()->query($sql, $values);
    }
    
    public function set($name, $value, $module, $controller, $action)
    {
        
    }
}