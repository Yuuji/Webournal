<?php

class Ext_Service_Etherpad
{
    private $_connected = false;
    private $_scheme = 'http';
    private $_host = '';
    private $_port = '';
    private $_pad  = '';
    private $_cookiefile = '';
    
    public function __destruct()
    {
        if(!empty($this->_cookiefile))
        {
            unlink($this->_cookiefile);
        }
    }
    
    private function getCookieFile()
    {
        if(empty($this->_cookiefile))
        {
            $this->_cookiefile = tempnam(trim(sys_get_temp_dir()), 'Webournal');
        }
        
        return $this->_cookiefile;
    }
    
    private function buildHostURL()
    {
        $url  = $this->_scheme . '://';
        $url .= $this->_host;
        
        if(!empty($this->_port))
        {
            $url .= ':' . $this->_port;
        }
        
        return $url;
    }
    
    private function doRequest($url, $doPost = false, $variables = null, $setOpts = array())
    {
        if(empty($url))
        {
            throw new Exception('Keine URL angegeben');
        }
        
        $ch=curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookieFile());
        
        if($doPost)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($variables));
        }
        
        if(is_array($setOpts) && count($setOpts) > 0)
        {
            foreach($setOpts as $key => $val)
            {
                curl_setopt($ch, $key, $val);
            }
        }
        
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        return array(
            'url' => $url,
            'info' => $info,
            'content' => $content,
        );
    }
    
    public function open($url)
    {
        try
        {
            $parts = parse_url($url);
            
            if(isset($parts['host']) && isset($parts['path']))
            {
                if(isset($parts['scheme']))
                {
                    $this->_scheme = $parts['scheme'];
                }
                else
                {
                    $this->_scheme = 'http';
                }
                
                $this->_host = $parts['host'];
                
                if(isset($parts['port']))
                {
                   $this->_port = $parts['port'];
                }
                
                $pad = substr($parts['path'],1);
                $pad = trim($pad);
                if(empty($pad))
                {
                    throw new Exception('no path');
                }
                $this->_pad = $pad;
                
                $this->_connected = true;
            }
            else
            {
                throw new Exception('no host or no path');
            }
        }
        catch(Exception $e)
        {
            $this->_scheme = '';
            $this->_host = '';
            $this->_port = '';
            $this->_pad = '';
            $this->_connected = false;
            return false;
        }
    }
    
    public function exists()
    {
        $url = $this->buildHostURL() . '/' . $this->_pad; 
        $return = $this->doRequest($url);
        $newUrl = $return['info']['url'];
        if(($pos = strpos($newUrl, '?'))!==false)
        {
            $newUrl = substr($newUrl, 0, $pos);
        }

        if(strtolower($newUrl)==strtolower($url))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function getContent()
    {
        $url = $this->buildHostURL() . '/' . $this->_pad; 
        $return = $this->doRequest($url);
        $lines = explode("\n", $return['content']);
        foreach($lines as $line)
        {
            $line = trim($line);
            if(substr($line,0,14)=='var clientVars')
            {
                $line = substr($line,strpos($line, '{'));
                $line = substr($line, 0, strrpos($line, ';'));
                $line = preg_replace('/\\\x([0-9a-f]{1,2})/ei', 'chr(hexdec("\\1"))',$line);
                $content = json_decode($line);
                
                if(is_null($content))
                {
                    return false;
                }
                
                if(!isset($content->collab_client_vars->initialAttributedText))
                {
                    return false;
                }
                
                $text = $content->collab_client_vars->initialAttributedText;
                
                if(!isset($text->text))
                {
                    return false;
                }
                
                $text->numToAttrib = $content->collab_client_vars->apool->numToAttrib;
                
                return $text;
            }
        }
        return false;
    }
    
    private function applyAttribs($content, $replaceArray)
    {
        $text = $content->text;
        $parts = array();
        $ignoreNext = false;
        $part = '';
        for($i=0; $i<strlen($content->attribs); $i++)
        {
            $char = mb_substr($content->attribs,$i,1);
            
            if(in_array($char, array('+', '*', '|')))
            {
                if(!$ignoreNext)
                {
                    if(strlen(trim($part))>0)
                    {
                        $parts[] = $part;
                    }
                    $part = '';
                }
                
                $ignoreNext = false;
            }
            
            if($char=='|')
            {
                $ignoreNext = true;
            }
            
            $part .= $char;
        }
        
        if(strlen(trim($part))>0)
        {
            $parts[] = $part;
        }
        
        $prepareTypes = function(&$openedTypes, &$types, &$strPos, &$replaceArray, &$data, $movePos=0) {
            if(!isset($data['data']))
            {
                $data = array(
                    'data' => array(),
                    'tmp' => array()
                );
            }
            
            reset($openedTypes);
            foreach($openedTypes as $type => $subtype)
            {
                if(!isset($types[$type]))
                {
                    if(isset($replaceArray[$type]))
                    {
                        if(!isset($data['data'][$strPos]))
                        {
                            $data['data'][$strPos] = array(
                                'open' => array(),
                                'close' => array()
                            );
                        }
                        
                        $opened = $data['tmp'][$type];
                        
                        $data['data'][$opened]['open'][] = array(
                            'type' => array('type' => $type, 'subtype' => $subtype),
                            'closed' => $strPos
                        );

                        
                        $data['data'][$strPos]['close'][] = array(
                            'type' => array('type' => $type, 'subtype' => $subtype),
                            'opened' => $opened,
                            'movePos' => $movePos
                        );
                    }
                    unset($openedTypes[$type]);
                }
            }
            reset($types);
            foreach($types as $type => $subtype)
            {
                if(!isset($openedTypes[$type]))
                {
                    $openedTypes[$type] = $subtype;
                    if(isset($replaceArray[$type]))
                    {
                        if(!isset($data['data'][$strPos]))
                        {
                            $data['data'][$strPos] = array(
                                'open' => array(),
                                'close' => array()
                            );
                        }
                        
                        $data['tmp'][$type] = $strPos;
                    }
                }
            }
            $types = array();
        };
        
        $commitTypes = function(&$data, &$text, &$replaceArray)
        {
            $strPosPlus=0;
            $tempOpenedPos = array();
            reset($data['data']);
            while(count($data['data'])>0)
            {
                reset($data['data']);
                $strPos = key($data['data']);
                $set = $data['data'][$strPos];
                unset($data['data'][$strPos]);
                
                usort($set['close'], function($a, $b) {
                    if($a['opened'] == $b['opened'])
                    {
                        return 0;
                    }
                    return ($a['opened'] > $b['opened'] ? -1 : 1);
                });
                
                reset($set['close']);
                foreach($set['close'] as $type)
                {
                    $replaceStr = '';
                    $tillLineEnd = false;
                    $cP = null;
                    if(isset($replaceArray[$type['type']['type']][1]))
                    {
                        $replaceStr = $replaceArray[$type['type']['type']][1];
                        
                        if(isset($replaceArray[$type['type']['type']][2]))
                        {
                            $tillLineEnd = $replaceArray[$type['type']['type']][2];
                        }
                    }
                    else if(isset($replaceArray[$type['type']['type']][$type['type']['subtype']][1]))
                    {
                        $replaceStr = $replaceArray[$type['type']['type']][$type['type']['subtype']][1];
                        
                        if(isset($replaceArray[$type['type']['type']][$type['type']['subtype']][2]))
                        {
                            $tillLineEnd = $replaceArray[$type['type']['type']][$type['type']['subtype']][2];
                        }
                    }

                    if($tillLineEnd)
                    {
                        if(!isset($type['movedType']))
                        {
                            $removePos = $strPos - $type['opened'];
                            $text = mb_substr($text, 0, $strPos + $strPosPlus - $removePos) . mb_substr($text, $strPos + $strPosPlus);
                            $strPosPlus -= $removePos;
                        }
                        $type['movedType'] = true;
                        
                        if(isset($replaceArray[$type['type']['type']][0]))
                        {
                            $tmpPlus = $tempOpenedPos[$type['type']['type']];
                        }
                        else if(isset($replaceArray[$type['type']['type']][$type['type']['subtype']][0]))
                        {
                            $tmpPlus = $tempOpenedPos[$type['type']['type']][$type['type']['subtype']];
                        }
                        
                        $found = false;
                        for($i =($type['opened']+$tmpPlus); $i<$strPos + $strPosPlus; $i++)
                        {
                            if(mb_substr($text, $i, 1)=="\n")
                            {
                                $found = true;
                                break;
                            }
                        }
                        
                        if(!$found)
                        {
                            if(!isset($data['data'][$strPos+1]))
                            {
                                $data['data'][$strPos+1] = array(
                                    'open' => array(),
                                    'close' => array()
                                );
                            }
                            $data['data'][$strPos+1]['close'][] = $type;
                            ksort($data['data']);
                            continue;
                        }
                    }
                    
                    $movePos = 0;
                    if(isset($type['movePos']))
                    {
                        //$movePos = $type['movePos'];
                    }
                    
                    $text = mb_substr($text,0, $strPos + $strPosPlus + $movePos) . $replaceStr . mb_substr($text,$strPos + $strPosPlus + $movePos);
                    $strPosPlus += strlen($replaceStr);
                }
                
                usort($set['open'], function($a, $b) {
                    if($a['closed'] == $b['closed'])
                    {
                        return 0;
                    }
                    return ($a['closed'] > $b['closed'] ? -1 : 1);
                });
                
                reset($set['open']);
                foreach($set['open'] as $type)
                {
                    $replaceStr = '';
                    if(isset($replaceArray[$type['type']['type']][0]))
                    {
                        $replaceStr = $replaceArray[$type['type']['type']][0];
                    }
                    else if(isset($replaceArray[$type['type']['type']][$type['type']['subtype']][0]))
                    {
                        $replaceStr = $replaceArray[$type['type']['type']][$type['type']['subtype']][0];
                    }
                    
                    $text = mb_substr($text,0, $strPos + $strPosPlus) . $replaceStr . mb_substr($text,$strPos + $strPosPlus);
                    $strPosPlus += strlen($replaceStr);
                    
                    if(isset($replaceArray[$type['type']['type']][0]))
                    {
                        $tempOpenedPos[$type['type']['type']] = $strPosPlus;
                    }
                    else if(isset($replaceArray[$type['type']['type']][$type['type']['subtype']][0]))
                    {
                        if(!isset($tempOpenedPos[$type['type']['type']]))
                        {
                            $tempOpenedPos[$type['type']['type']] = array();
                        }
                        $tempOpenedPos[$type['type']['type']][$type['type']['subtype']] = $strPosPlus;
                    }
                    
                }
            }
        };
        
        // small fix for serializes version
        
        reset($content->numToAttrib);
        $newNumToAttrib = new stdClass();
        foreach($content->numToAttrib as $key => $val)
        {
            $key = (string)$key;
            $newNumToAttrib->$key = $val;
        }
        
        $content->numToAttrib = $newNumToAttrib;
        
        reset($parts);
        $strPos = 0;
        $types = array();
        $openedTypes = array();
        $data = array();
        foreach($parts as $part)
        {
            switch (mb_substr($part,0,1))
            {
                case '+':
                    $prepareTypes(&$openedTypes, &$types, &$strPos, &$replaceArray, &$data);
                    $strPos += intval(mb_substr($part,1),36);
                    break;
                case '*':
                    $typePos = intval(mb_substr($part,1),36);
                    if(isset($content->numToAttrib->$typePos))
                    {
                        $attrib = $content->numToAttrib->$typePos;
                        $typeName = $attrib['0'];
                        $types[$typeName] = $attrib['1'];
                    }
                    else
                    {
                        $types['dummy'] = true;
                    }
                    break;
                case '|':
                    $movePos = 0;
                    if(preg_match('/^([a-zA-Z0-9]{1,})([+-=]{1})([a-zA-Z0-9]{1,})$/', mb_substr($part,1),$jumpParts)>0)
                    {
                        $L = intval($jumpParts[1],36);
                        $T = $jumpParts[2];
                        $N = intval($jumpParts[3],36);
                        
                        /** @todo Understand what = meens */
                        if($T!='=')
                        {
                            if($T=='-')
                            {
                                $N = -1 * $N;
                            }
                            
                            $movePos = $N;
                        }
                    }
                    $prepareTypes(&$openedTypes, &$types, &$strPos, &$replaceArray, &$data, $movePos);
                    $strPos += $movePos;
                    break;
                default:
                    $prepareTypes(&$openedTypes, &$types, &$strPos, &$replaceArray, &$data);
            }
        }
        $prepareTypes(&$openedTypes, &$types, &$strPos, &$replaceArray, &$data);
        
        $commitTypes(&$data, &$text,&$replaceArray);
        
        return $text;
    }
    
    public function convertToHTML($content)
    {
        /** @todo Leerzeichen */
        $contentStr = $this->applyAttribs($content, array(
            'bold' => array('<b>', '</b>'),
            'strikethrough' => array('<s>', '</s>'),
            'italic' => array('<i>', '</i>'),
            'underline' => array('<u>', '</u>'),
            'list' => array(
                'bullet1' => array('<ul><li>', '</li></ul>', true),
                'bullet2' => array('<ul><ul><li>', '</li></ul></ul>', true),
                'bullet3' => array('<ul><ul><ul><li>', '</li></ul></ul></ul>', true),
                'bullet4' => array('<ul><ul><ul><ul><li>', '</li></ul></ul></ul></ul>', true),
                'bullet5' => array('<ul><ul><ul><ul><ul><li>', '</li></ul></ul></ul></ul></ul>', true),
                'bullet6' => array('<ul><ul><ul><ul><ul><ul><li>', '</li></ul></ul></ul></ul></ul></ul>', true),
                'bullet7' => array('<ul><ul><ul><ul><ul><ul><ul><li>', '</li></ul></ul></ul></ul></ul></ul></ul>', true),
                'bullet8' => array('<ul><ul><ul><ul><ul><ul><ul><ul><li>', '</li></ul></ul></ul></ul></ul></ul></ul></ul>', true),
            )
        ));
        $contentStr = nl2br($contentStr);
        
        $contentStr = Ext_Helper_Parser::parseLinks($contentStr, "<a href=\"\$1\" target=\"_blank\">", "</a>");
            
        return $contentStr;
    }
    
    public function convertToMediawiki($content)
    {
        /** @todo Leerzeichen */
        $contentStr = $this->applyAttribs($content, array(
            'bold' => array("'''", "'''"),
            'strikethrough' => array('<del>', '</del>'),
            'italic' => array("''", "''"),
            'underline' => array('<ins>', '</ins>'),
            'list' => array(
                'bullet1' => array('*', '', true),
                'bullet2' => array('**', '', true),
                'bullet3' => array('***', '', true),
                'bullet4' => array('****', '', true),
                'bullet5' => array('*****', '', true),
                'bullet6' => array('******', '', true),
                'bullet7' => array('*******', '', true),
                'bullet8' => array('********', '', true),
            )
        ));
        //$contentStr = nl2br($contentStr);
        
       return $contentStr;
    }
}