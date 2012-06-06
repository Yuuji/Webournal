<?php
class Core_Route_Hostname extends Zend_Controller_Router_Route_Hostname
{

    /**
     * Helper var that holds a count of route pattern's static parts
     * for validation
     * @var int
     */
    private $_staticCount = 0;
    
    protected $_staticPart = '';
    
    /**
     *
     * @var Zend_Config
     */
    protected $_chains = null;
    
    /**
     * Instantiates route based on passed Zend_Config structure
     *
     * @param Zend_Config $config Configuration object
     */
    public static function getInstance(Zend_Config $config)
    {
        $reqs   = ($config->reqs instanceof Zend_Config) ? $config->reqs->toArray() : array();
        $defs   = ($config->defaults instanceof Zend_Config) ? $config->defaults->toArray() : array();
        $scheme = (isset($config->scheme)) ? $config->scheme : null;
        $static = (isset($config->static)) ? $config->static : '';
        $chains = (isset($config->chains)) ? $config->chains : null;
        return new self($config->route, $defs, $reqs, $scheme, $static, $chains);
    }
    
    public function __construct($route, $defaults = array(), $reqs = array(), $scheme = null, $static = '', $chains = null)
    {
        $this->_staticPart = $static;
        $this->_chains = $chains;
        parent::__construct($route, $defaults, $reqs, $scheme);
    }
    
    public function setScheme($scheme)
    {
        $this->_scheme = $scheme;
    }
    

    /**
     * Matches a user submitted path with parts defined by a map. Assigns and
     * returns an array of variables on a successful match.
     *
     * @param Zend_Controller_Request_Http $request Request to get the host from
     * @return array|false An array of assigned values or a false on a mismatch
     */
    public function match($request)
    {
        // Check the scheme if required
        if ($this->_scheme !== null) {
            $scheme = $request->getScheme();

            if ($scheme !== $this->_scheme) {
                return false;
            }
        }

        // Get the host and remove unnecessary port information
        $host = $request->getHttpHost();
        if (preg_match('#:\d+$#', $host, $result) === 1) {
            $host = substr($host, 0, -strlen($result[0]));
        }
        
        // Remove static part
        if(substr($host, -1 * strlen($this->_staticPart))== $this->_staticPart)
        {
            $host = substr($host, 0, -1 * strlen($this->_staticPart) - 1);
        }
        
        
        $hostStaticCount = 0;
        $values = array();
        $prevalues = array();

        $host = trim($host, '.');

        if ($host != '') {
            $host = explode('.', $host);

            foreach ($host as $pos => $hostPart) {
                // Host is longer than a route, it's not a match
                if (!array_key_exists($pos, $this->_parts)) {
                    return false;
                }

                $name = isset($this->_variables[$pos]) ? $this->_variables[$pos] : null;
                $hostPart = urldecode($hostPart);

                // If it's a static part, match directly
                if ($name === null && $this->_parts[$pos] != $hostPart) {
                    return false;
                }

                // If it's a variable with requirement, match a regex. If not - everything matches
                if ($this->_parts[$pos] !== null && !preg_match($this->_regexDelimiter . '^' . $this->_parts[$pos] . '$' . $this->_regexDelimiter . 'iu', $hostPart)) {
                    return false;
                }

                // If it's a variable store it's value for later
                if ($name !== null) {
                    $prevalues[] = $hostPart;
                } else {
                    $hostStaticCount++;
                }
            }
            
            $prepos = count($this->_variables) - count($prevalues);
            foreach($prevalues as $pos => $value)
            {
                $name = $this->_variables[$prepos + $pos];
                
                $values[$name] = $value;
            }
        }
        
        // Check if all static mappings have been matched
        if ($this->_staticCount != $hostStaticCount) {
            return false;
        }
        
        if(!is_null($this->_chains))
        {
           $type = $this->_chains->index->type;
           $subrouter = $type::getInstance($this->_chains->index);
           
           $path = $request->getPathInfo();

           if(is_string($path))
           {
               $subValues = $subrouter->match($path);
               
               if(is_array($subValues))
               {
                   $values = array_merge($values, $subValues);
               }
           }
        }

        $return = $values + $this->_defaults;

        // Check if all map variables have been initialized
        foreach ($this->_variables as $var) {
            if (!array_key_exists($var, $return)) {
                return false;
            }
        }
        
        $this->_values = $values;
        
        return $return;

    }

    /**
     * Assembles user submitted parameters forming a hostname defined by this route
     *
     * @param  array $data An array of variable and value pairs used as parameters
     * @param  boolean $reset Whether or not to set route defaults with those provided in $data
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = false, $partial = false, $ignoreParts = false)
    {
        $host = array();
        $flag = false;
        $return = '';

        if(!$ignoreParts)
        {
            foreach ($this->_parts as $key => $part) {
                $name = isset($this->_variables[$key]) ? $this->_variables[$key] : null;

                $useDefault = false;
                if (isset($name) && array_key_exists($name, $data) && $data[$name] === null) {
                    $useDefault = true;
                }

                if (isset($name)) {
                    if (isset($data[$name]) && !empty($data[$name]) && !$useDefault) {
                        $host[$key] = $data[$name];
                        unset($data[$name]);
                    } elseif (!$reset && !$useDefault && isset($this->_values[$name])) {
                        $host[$key] = $this->_values[$name];
                    } elseif (isset($this->_defaults[$name])) {
                        if(!empty($this->_defaults[$name])/* || count($host)>0 */)
                        {
                            $host[$key] = $this->_defaults[$name];
                        }
                    } else {
                        require_once 'Zend/Controller/Router/Exception.php';
                        throw new Zend_Controller_Router_Exception($name . ' is not specified');
                    }
                } else {
                    $host[$key] = $part;
                }
            }

            foreach (array_reverse($host, true) as $key => $value) {
                if ($flag || !isset($this->_variables[$key]) || $value !== $this->getDefault($this->_variables[$key]) || $partial) {
                    if ($encode) $value = urlencode($value);
                    $return = '.' . $value . $return;
                    $flag = true;
                }
            }
        }

        $url = trim($return, '.');

        if ($this->_scheme !== null) {
            $scheme = $this->_scheme;
        } else {
            $request = $this->getRequest();
            if ($request instanceof Zend_Controller_Request_Http) {
                $scheme = $request->getScheme();
            } else {
                $scheme = 'http';
            }
        }

        $host[] = $this->_staticPart;

        $hostname = implode('.', $host);
        $url      = $scheme . '://' . $hostname;

        if(!is_null($this->_chains))
        {
           $type = $this->_chains->index->type;
           $subrouter = $type::getInstance($this->_chains->index);
           
           $subdata = $data;
           
           if(!$ignoreParts)
           {
               reset($this->_variables);
               foreach($this->_variables as $name)
               {
                   unset($subdata[$name]);
               }
           }
           
           $suburl = $subrouter->assemble($subdata, $reset, $encode, $partial);
           $url .= '/' . $suburl;
        }
        
        return $url;
    }
    
    public function getParts()
    {
        return $this->_parts;
    }
}
