<?php

class Core_View_Menu extends Core_View_Menu_Main
{
    private function checkMenuEntries($entries, Zend_Navigation_Page &$menu)
    {
        $inserted = false;
        foreach($entries as $entry)
        {
            $tmpEntry = clone $entry;
            /* @var $tmpEntry Zend_Navigation_Page */
            
            // check ACL
            // @todo Sub?
            if($tmpEntry->getType()==='controller')
            {
                if(!Core()->checkAccessRights($tmpEntry->module, $tmpEntry->controller, $tmpEntry->action))
                {
                    continue;
                }
            }
            
            $tmpEntry->removePages();
            
            $check = true;
            if($entry->hasChildren())
            {
                $check = $this->checkMenuEntries($entry, $tmpEntry);
            }
            
            if($check)
            {
                $menu->addPage($tmpEntry);
                $inserted = true;
            }
        }
        
        return $inserted;
    }
    
    public function getMenuByACL()
    {
        $menu = clone $this;
        /* @var $menu Core_View_Menu  */
        
        $menu->removePages();
        $this->checkMenuEntries($this, $menu);
        
        return $menu;
    }
}