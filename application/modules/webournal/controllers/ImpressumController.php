<?php
/**
 * Index-Controller
 */
class webournal_ImpressumController extends Zend_Controller_Action
{
    const VERSION = 1;
    
    public function indexAction()
    {
        
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
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_impressum');
        
        $entry = Core()->Menu()->findBy('label', 'IMPRINT');
        if(is_null($entry))
        {
            Core()->Menu()->addControllerEntry('IMPRINT', 'Imprint', 'index', 'impressum', 'webournal', array(), 9999);
        }
        
        return true;
    }
}
