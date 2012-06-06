<?php
class core_SettingsController extends Zend_Controller_Action
{
    const VERSION = 1;

    public function indexAction()
    {
        $save = $this->_request->getParam('save', false);
        $saved = false;
        if($save!==false)
        {
            $newSettings = $this->_request->getParam('settings', false);
            $settings = Core()->Settings();
            
            foreach($newSettings as $groupkey => $group)
            {
                if(isset($settings->$groupkey))
                {
                    foreach($group as $key => $value)
                    {
                        if(isset($settings->$groupkey->$key))
                        {
                            $settings->$groupkey->$key = $value;
                        }
                    }
                }
            }
            $saved = true;
        }
        
        $this->view->saved = $saved;
        $this->view->settings = Core()->Settings();
    }

    public static function updater($version)
    {
        if($version<self::VERSION)
        {
            for($i=$version+1; $i<=self::VERSION; $i++)
            {
                $function = 'update' . $i;
                if(!self::$function())
                {
                    return $i-1;
                }
            }
        }

        return self::VERSION;
    }

    private static function update1()
    {
        $parent = Core()->Menu();

        if(is_null($parent->findBy('label', 'SETTINGS')))
        {
            $parent->addControllerEntry('SETTINGS', 'Settings', 'index', 'settings', 'core', array());
        }

        return true;
    }
}