<?php

class Ext_Service_Mediawiki
{
    private $_domainConfigs = array();
    private $_domain = null;
    private $_cookieFileLocation = null;
    
    private function doRequest($variables = null, $setOpts = array())
    {
        $url = $this->_domainConfigs[$this->_domain]['api'];
        
        $ch=curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, "Webournal/1.0");
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        
        if(is_null($this->_cookieFileLocation))
        {
            $this->_cookieFileLocation = tempnam(sys_get_temp_dir(),'WEBOURNALCURL');
        }
        
        curl_setopt($ch,CURLOPT_COOKIEJAR,$this->_cookieFileLocation); 
        curl_setopt($ch,CURLOPT_COOKIEFILE,$this->_cookieFileLocation); 
        
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post = http_build_query($variables));

        if(is_array($setOpts) && count($setOpts) > 0)
        {
            foreach($setOpts as $key => $val)
            {
                curl_setopt($ch, $key, $val);
            }
        }
        
        $content = curl_exec($ch);
        
        curl_close($ch);
        
        return $content;
    }
    
    public function __construct($domain, $username=null, $password=null, $logindomain=null, $prePath=null, $api=null)
    {
        $config = Core()->Application()->getOption('webournal');
        $config = new Zend_Config($config['default']['mediawiki']);
        
        foreach($config as $key => $val)
        {
            $this->_domainConfigs[$val->domain] = array(
                'username'  => $val->login->username,
                'password'  => $val->login->password,
                'domain'    => $val->login->domain,
                'prePath'   => $val->prePath,
                'api'       => $val->api
            );
        }
        
        if(is_null($username) || is_null($password) || is_null($logindomain) || is_null($prePath) || is_null($api))
        {
            if(!isset($this->_domainConfigs[$domain]))
            {
                throw new Exception('Mediawiki: No access data for domain found!');
            }
        }
        else
        {
            $this->_domainConfigs[$domain] = array(
                'username'  => $username,
                'password'  => $password,
                'domain'    => $logindomain,
                'prePath'   => $prePath,
                'api'       => $api
            );
        }
        
        $this->_domain = $domain;
        
        $this->login();
    }
    
    public function __destruct()
    {
        $this->doRequest(array('action' => 'logout'));
        
        if(!is_null($this->_cookieFileLocation))
        {
            unlink($this->_cookieFileLocation);
        }
    }
    
    public function login($token=null)
    {
        $variables = array(
            'action' => 'login',
            'lgname' => $this->_domainConfigs[$this->_domain]['username'],
            'lgpassword' => $this->_domainConfigs[$this->_domain]['password'],
            'lgdomain' => $this->_domainConfigs[$this->_domain]['domain'],
            'format' => 'php'
        );
        
        if(!is_null($token))
        {
            $variables['lgtoken'] = $token;
        }
        
        $result = $this->doRequest($variables);
        $result = unserialize($result);
        
        if(!isset($result['login']) || !isset($result['login']['result']))
        {
            throw new Exception('Could not log in mediawiki! Unknown error');
        }
        
        
        if(is_null($token))
        {
            switch($result['login']['result'])
            {
                case 'NeedToken':
                    $this->login($result['login']['token']);
                    break;
                default:
                    throw new Exception('Could not log in mediawiki! Error: "' . $result['login']['result'] . '"');
            }
        }
        else
        {
            if($result['login']['result']!=='Success')
            {
                throw new Exception('Could not log in mediawiki! Error: "' . $result['login']['result'] . '"');
            }
        }
    }
    
    public function getPageInfo($path)
    {
        $prePath = $this->_domainConfigs[$this->_domain]['prePath'];

        if(substr($prePath,-1)!=='/')
        {
            $prePath .= '/';
        }
        if(substr($path,0,strlen($prePath))==$prePath)
        {
            $path = substr($path,strlen($prePath));
        }
        
        $result = $this->doRequest(array(
            'action'    => 'query',
            'prop'      => 'info',
            'intoken'   => 'edit',
            'titles'    => $path,
            'format'    => 'php'
        ));
        
        $result = unserialize($result);
        
        if(!isset($result['query']) || !isset($result['query']['pages']))
        {
            throw new Exception('Could not get page info from mediawiki! Unknown error');
        }
        
        return $result['query']['pages'];
    }
    
    public function editPage($path, $content, $summary = '')
    {
        $info = $this->getPageInfo($path);
        
        reset($info);
        $id = key($info);
        $info = $info[$id];
        
        $result = $this->doRequest(array(
            'action'    => 'edit',
            'title'     => $info['title'],
            'text'      => $content,
            'token'     => $info['edittoken'],
            'summary'   => $summary,
            'format'    => 'php'
        ));
        
        $result = unserialize($result);
        
        if(!isset($result['edit']) || !isset($result['edit']['result']))
        {
            throw new Exception('Could not edit page in mediawiki! Unknown error');
        }
        /** @todo handle captchas */
        switch($result['edit']['result'])
        {
            case 'Success':
                break;
            default:
                throw new Exception('Could not get page info from mediawiki! Error "' . $result['edit']['result'] .  '"');
        }
    }
}