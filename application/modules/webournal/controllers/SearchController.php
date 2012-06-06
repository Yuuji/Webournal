<?php
/**
 * Index-Controller
 */
class webournal_SearchController extends Zend_Controller_Action
{
    const VERSION = 1;

    public function indexAction()
    {
        $error_noinput = false;
        $error_tooshort = false;

        $search = $this->_request->getParam('search', false);

        if($search!==false)
        {
            if(!empty($search))
            {
                $parts = explode(' ', $search);

                foreach($parts as $key => $part)
                {
                    if(empty($part))
                    {
                        unset($parts[$key]);
                    }
                    else
                    {
                        if(strlen($part)<3)
                        {
                            $error_tooshort = true;
                        }
                    }
                }

                if(!$error_tooshort)
                {
                    $this->_request->setParam('search', implode(' ', $parts));

                    define('webournal_search_checked', true);
                    return $this->_forward('search');
                }
            }
            else
            {
                $error_noinput = true;
            }
        }

        $this->view->error_noinput = $error_noinput;
        $this->view->error_tooshort = $error_tooshort;
    }

    public function searchAction()
    {
        $search = $this->_request->getParam('search', false);

        if(!defined('webournal_search_checked') || $search===false)
        {
            return $this->_redirect(array('action' => 'index'));
        }

        $files = Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `webournal_search` ws
            WHERE
                `query` = ? AND
                `groupid` = ?
        ', array(
            $this->escapeSphinxQL($search),
            Core()->getGroupId()
        ));

        $this->view->search_files = $files;
    }

    private function escapeSphinxQL($string)
    {
        $from = array ( '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', "'", "\x00", "\n", "\r", "\x1a" );
        $to   = array ( '\\\\', '\\\(','\\\)','\\\|','\\\-','\\\!','\\\@','\\\~','\\\"', '\\\&', '\\\/', '\\\^', '\\\$', '\\\=', "\\'", "\\x00", "\\n", "\\r", "\\x1a" );
        return str_replace ( $from, $to, $string );
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
        $config = Core()->Config()->sphinx;
        
        if($config && $config->enabled)
        {
            Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_search');
        
            $check = Core()->Db()->fetchAll('SHOW ENGINES');
            
            foreach($check as $engine)
            {
                if(strtolower($engine['Engine'])==='sphinx')
                {
                    $check = true;
                    break;
                }
            }
            
            if($check!==true)
            {
                return false;
            }
            
            Core()->Db()->query('
                CREATE TABLE webournal_search
                (
                    id          INTEGER UNSIGNED NOT NULL,
                    weight      INTEGER NOT NULL,
                    query       VARCHAR(3072) NOT NULL,
                    groupid     INTEGER,
                    INDEX(query)
                ) ENGINE=SPHINX CONNECTION=?;
            ', array(
                'sphinx://' . $config->hostname . ':' . $config->port . '/' . $config->table
            ));
        }

        return true;
    }
}