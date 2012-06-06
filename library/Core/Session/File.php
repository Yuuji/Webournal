<?php
class Core_Session_File implements Zend_Session_SaveHandler_Interface
{
    /**
     * Session save path
     *
     * @var string
     */
    protected $_sessionSavePath;

    /**
     * Session name
     *
     * @var string
     */
    protected $_sessionName;
    
    /**
     * cache for session content
     * @var array
     */
    protected static $_cache = array();

    public function __construct($config=array())
    {
        $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

    /**
     * Set session lifetime and optional whether or not the lifetime of an existing session should be overridden
     *
     * $lifetime === false resets lifetime to session.gc_maxlifetime
     *
     * @param int $lifetime
     * @param boolean $overrideLifetime (optional)
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setLifetime($lifetime, $overrideLifetime = null)
    {
        if ($lifetime < 0) {
            throw new Zend_Session_SaveHandler_Exception();
        } else if (empty($lifetime)) {
            $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        } else {
            $this->_lifetime = (int) $lifetime;
        }

        if ($overrideLifetime != null) {
            $this->setOverrideLifetime($overrideLifetime);
        }

        return $this;
    }

    /**
     * Retrieve session lifetime
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * Set whether or not the lifetime of an existing session should be overridden
     *
     * @param boolean $overrideLifetime
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setOverrideLifetime($overrideLifetime)
    {
        $this->_overrideLifetime = (boolean) $overrideLifetime;

        return $this;
    }

    /**
     * Retrieve whether or not the lifetime of an existing session should be overridden
     *
     * @return boolean
     */
    public function getOverrideLifetime()
    {
        return $this->_overrideLifetime;
    }

    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        if(empty($save_path))
        {
            $this->_sessionSavePath = sys_get_temp_dir();
        }
        else
        {
            $this->_sessionSavePath = $save_path;
        }
        $this->_sessionName     = $name;

        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }
    
    private function getFilePrefix()
    {
        return $this->_sessionSavePath . '/zendsess_';
    }
    
    private function getFilename($id)
    {
        return $this->getFilePrefix() . $id;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $filename = $this->getFilename($id);
        
        if(!file_exists($filename))
        {
            return false;
        }
        
        self::$_cache[$filename] = (string)@file_get_contents($filename);
        
        return self::$_cache[$filename];
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $filename = $this->getFilename($id);
        
        if(isset(self::$_cache[$filename]))
        {
            // nothing change
            if(self::$_cache[$filename]==$data)
            {
                @touch($filename);
                return true;
            }
        }
        
        if($fp = @fopen($filename, 'w'))
        {
            $return = fwrite($fp, $data);
            fclose($fp);
            return $return;
        }
        
        return false;
                
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $filename = $this->getFilename($id);
        
        return (@unlink($filename));
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        foreach (glob($this->getFilePrefix() . '*') as $filename)
        {
            if (filemtime($filename) + $this->getLifetime() < time())
            {
                @unlink($filename);
            }
        }
        return true;
    }

    /**
     * Calls other protected methods for individual setup tasks and requirement checks
     *
     * @return void
     */
    protected function _setup()
    {
        $this->setLifetime($this->_lifetime);
    }
}
