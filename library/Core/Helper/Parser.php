<?php

class Core_Helper_Parser
{
    /**
     *
     * @param string $text
     * @param string $pre Use $1 for url placehilder
     * @param string $post Use $1 for url placehilder
     * @return string
     */
    public static function parseLinks($text, $pre, $post)
    {
        $pre = str_replace('$1', '\\0', $pre);
        $post = str_replace('$1', '\\0', $post);
        $text = preg_replace("|([a-z]+:(?://)?)([a-z\x80-\xff][a-z0-9\x80-\xff-]*[a-z0-9\x80-\xff](?:\.[a-z\x80-\xff][a-z0-9\x80-\xff-]*[a-z0-9\x80-\xff])*)((?:/[^\s/?&#<]+)*)(\?[^\s<#]+)?(#[^\s<]*)?|i", $pre . "\\0" . $post, $text);
        return $text;
    }
    
    public static function getBBCode($text, $name)
    {
        $return = array();
        
        preg_match_all('/\[' . $name . '([^\]]*)\](.*)\[\/' . $name . ']/isU', $text, $matches, PREG_SET_ORDER);
        
        reset($matches);
        foreach($matches as $match)
        {
            $content = $match[2];
            $inner = $match[1];
            preg_match_all('/(([^=]*)="([^"]*)")/is', $inner, $inner_matches, PREG_SET_ORDER);
            
            $attribs = array();
            
            reset($inner_matches);
            foreach($inner_matches as $inner_match)
            {
                $tag = trim($inner_match[2]);
                $value = $inner_match[3];
                $attribs[$tag] = $value;
            }
            $return[] = array(
                'content' => trim($content),
                'attributes' => $attribs
            );
        }
        return $return;
    }
    
    public static function replaceBBCode($text, $name, $replace)
    {
        $return = $text;
        foreach($replace as $newText)
        {
            $return = preg_replace('/\[' . $name . '([^\]]*)\](.*)\[\/' . $name . ']/isU', $newText, $return, 1);
        }
        
        return $return;
    }
}