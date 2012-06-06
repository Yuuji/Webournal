<?php
    class core_LoginController extends Zend_Controller_Action
    {
        const VERSION = 1;
        
        public function indexAction()
        {
            return Core()->redirect('login');
        }

        public function loginAction()
        {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity())
            {
                return Core()->redirect('index', 'index', 'core');
            }

            if(!isset($this->view->login_error))
            {
                $this->view->login_error = false;
            }
        }

        public function logoutAction()
        {
            Zend_Auth::getInstance()->clearIdentity();
            return Core()->redirect('index', 'index', 'core');
        }

        public function registerAction()
        {
            $error_pw = false;
            $error_user = false;
            $error_email = false;

            $register = $this->_request->getParam('register', false);
            $username = $this->_request->getParam('username', false);
            $email = $this->_request->getParam('email', false);

            if($register!==false && $username!==false)
            {
                $check_user = Core()->AccessControl()->getUserId($username);

                $check_email = false;
                
                if($email!==false && !empty($email))
                {
                    $check_email = Core()->AccessControl()->getUserIdByEmail($email);
                }

                if($check_user!==false)
                {
                    $error_user = true;
                }
                else if($email!==false && $check_email!==false)
                {
                    $error_email = true;
                }
                else
                {
                    $pw1 = $this->_request->getParam('password', false);
                    $pw2 = $this->_request->getParam('password2', false);

                    if($pw1 === false || $pw2 === false || $pw1 !== $pw2)
                    {
                        $error_pw = true;
                    }
                    else
                    {
                        if(strlen($pw1)<8)
                        {
                            $error_pw = true;
                        }
                        else
                        {
                            /** @todo check email */
                            Core()->AccessControl()->addUser($username, $pw1, $email);
                            Core()->redirect('login', 'login', 'core');
                        }
                    }
                }
            }
            
            $this->view->loginconfig = Core()->getLoginConfig();

            $this->view->username = $username;
            $this->view->email = $email;

            $this->view->register_error_pw = $error_pw;
            $this->view->register_error_user = $error_user;
            $this->view->register_error_email = $error_email;
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
            
            if(is_null($parent->findBy('label', 'LOGIN')))
            {
                $parent->addControllerEntry('LOGIN', 'Login', 'login', 'login', 'core', array(), -1);
            }
            
            if(is_null($parent->findBy('label', 'LOGOUT')))
            {
                $parent->addControllerEntry('LOGOUT', 'Logout', 'logout', 'login', 'core', array(),99999);
            }
            return true;
        }
    }
