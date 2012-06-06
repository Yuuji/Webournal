<?php
/**
 * Index-Controller
 */
class core_UserController extends Zend_Controller_Action
{
    const VERSION = 1;

    public function postDispatch()
    {
        $this->view->submenu = array(
            array(
                'module'        => 'core',
                'name'          => 'Passwort &auml;ndern',
                'controller'    => 'user',
                'action'        => 'changepassword'
            ),
        );
    }

    /**
     * default action
     */
    public function indexAction()
    {
        
    }

    public function changepasswordAction()
    {
        $error = false;
        $success = false;

        $change = $this->_request->getParam('change', false);

        if($change!==false)
        {
            $pw1 = $this->_request->getParam('password', false);
            $pw2 = $this->_request->getParam('password2', false);

            if($pw1 === false || $pw2 === false || $pw1 !== $pw2)
            {
                $error = true;
            }
            else
            {
                if(strlen($pw1)<8)
                {
                    $error = true;
                }
                else
                {
                    $username = Core()->getUsername();
                    Core()->AccessControl()->changePassword($username, $pw1);
                    $success = true;
                }
            }
        }

        $this->view->changepassword_error = $error;
        $this->view->changepassword_success = $success;
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
        Core()->ACL()->addDefaultPermissions('allow', 3, 'core_user');
        
        $parent = Core()->Menu()->findBy('label', 'ACCOUNT');
        if(is_null($parent))
        {
            $parent = Core()->Menu()->addContainerEntry('ACCOUNT', 'Account');
        }

        if(is_null($parent->findBy('label', 'CHANGEPASSWORD')))
        {
            $parent->addControllerEntry('CHANGEPASSWORD', 'Change password', 'changepassword', 'user', 'core');
        }
        
        return true;
    }
}