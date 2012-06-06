<?php
/**
 * Index-Controller
 */
class webournal_IndexController extends Zend_Controller_Action
{
    const VERSION = 1;
    
    /**
     * default action
     */
    public function indexAction()
    {
        if(Core()->getGroupId()>0)
        {
            return Core()->redirect('index', 'view', 'webournal');
        }
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
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_index');
        
        $entry = Core()->Menu()->findBy('label', 'OVERVIEW');
        if(is_null($entry))
        {
            Core()->Menu()->addControllerEntry('OVERVIEW', 'Overview', 'index', 'index', 'webournal', array(), -2);
        }
        
        return true;
    }
}

