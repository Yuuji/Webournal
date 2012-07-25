<?php
class webournal_Rest_LoginController extends Core_REST_Controller
{
    const VERSION = 1;
    
    public function indexAction()
    {
        $this->_forward('get');
    }
    
    public function getAction()
    {
        $logedin = Core()->Auth()->hasIdentity();
        
        if($logedin)
        {
            $username = Core()->getUsername();
        }
        else
        {
            $username = '';
        }
        
        Core()->outputREST(array(
            'logedin' => $logedin,
            'username' => $username
        ));
    }
    
    public function postAction()
    {
        if(     $this->_request->getParam('login_user', false)===false ||
                $this->_request->getParam('login_password', false)===false
            )
        {
            return $this->output('', false, new Exception('No login data', 1));
        }
        
        if(!Core()->Auth()->hasIdentity())
        {
            if($this->view->login_error)
            {
                return $this->output('', false, new Exception('Login not correct', 2));
            }
            else
            {
                return $this->output('', false, new Exception('No login data', 1));
            }
        }
        
        return $this->output('', true);
    }
    
    public function deleteAction()
    {
        $this->output('', false, new Exception('Please use POST', 1));
    }
    
    public function putAction()
    {
        $this->output('', false, new Exception('Please use POST', 1));
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
        Core()->ACL()->addDefaultPermissions('allow', 3, 'webournal_rest_login');

        return true;
    }
}
