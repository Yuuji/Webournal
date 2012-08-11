<?php

class Core_Service_PHP
{
    /**
     * Returns all classes of a .php-file
     * @param string $filepath
     * @return array 
     */
    public static function getPHPClassesOfFile($filepath)
    {
        $php_code = file_get_contents($filepath);
        $classes = self::getPHPClasses($php_code);
        return $classes;
    }
    
    
    /**
     * Returns all classes of a PHP code string
     * @param string $php_code
     * @return array
     */
    public static function getPHPClasses($php_code)
    {
        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++)
        {
            if (   $tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING)
            {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }
        return $classes;
    }

	public static function getControllerFiles($path)
	{
		$return = array();
		foreach(scandir($path) as $file)
		{
			if(substr($file, 0, 1)==='.')
			{
				continue;
			}

			if (strstr($file, "Controller.php") !== false)
			{
				$controller = substr($file, 0, strpos($file, "Controller"));
				$return[] = $controller;
			}
			else if(is_dir($path . '/' . $file))
			{
				$temp = self::getControllerFiles($path . '/' . $file);

				foreach($temp as $controller)
				{
					$return[] = $file . '_' . $controller;
				}
			}
		}	

		return $return;
	}
    
    /**
     * Returns all controller of project
     * @return array 
     */
    public static function getControllers()
    {
        $front = Zend_Controller_Front::getInstance();
        $return = array();

        foreach ($front->getControllerDirectory() as $module => $path)
        {

			$temp = self::getControllerFiles($path);
			if(count($temp)>0)
			{
				$return[$module] = $temp;
			}
        }
        
		return $return;
    }
    
    
    /**
     * Returns all services of project
     * @return array 
     */
    public static function getServices()
    {
        $front = Zend_Controller_Front::getInstance();
        $return = array();
        
        foreach ($front->getControllerDirectory() as $module => $tmp)
        {
            $path = $front->getModuleDirectory($module) . DIRECTORY_SEPARATOR . 'services';
            if(!file_exists($path))
            {
                continue;
            }
            $return[$module] = array();
            foreach (scandir($path) as $file)
            {
				if(substr($file, 0, 1)==='.')
				{
					continue;
				}
                if (strstr($file, ".php") !== false)
                {
                    $service = substr($file, 0, strpos($file, ".php"));
                    $return[$module][] = $service;
                }
            }
        }
        return $return;
    }
    
    
    /**
     * Includes service file
     * @param string $module Modulename
     * @param string $service Servicename
     * @return boolean true on success
     */
    public static function includeService($module, $service)
    {
        $front = Zend_Controller_Front::getInstance();
        $path = $front->getModuleDirectory($module) . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . $service . '.php';
        if(!file_exists($path))
        {
            return false;
        }
        
        include_once($path);
        
        return true;
    }
    
    
    /**
     * Returns all actions of a controller
     * @var $module Modulename
     * @var $controller Controllername
     * @return array 
     */
    public static function getActionsOfControllers($module, $controller)
    {
        $front = Zend_Controller_Front::getInstance();
        $return = array();

        $path = $front->getControllerDirectory($module);
        if(is_null($path))
        {
            return array();
        }

		$path2 = str_replace('_', '/', $controller);
		$pos = strrpos($path2, '/');
		
		if($pos!==false)
		{
			$path .= '/' . substr($path2, 0, $pos);
			$filename_controller = substr($path2, $pos+1);
		}
		else
		{
			$filename_controller = $controller;
		}
		
        $return = array();
        foreach (scandir($path) as $file)
        {
            if (strstr($file, "Controller.php") !== false)
            {
                $file_controller = substr($file, 0, strpos($file, "Controller"));
                if(strtolower($file_controller)==strtolower($filename_controller))
                {
                    include_once($path . '/' . $file);
                    if($module==Core()->getDefaultModule())
                    {
                        $className = $controller .'Controller';
                    }
                    else
                    {
                        $className = $module . '_' . $controller .'Controller';
                    }
                    $methods = get_class_methods($className);
                    if(is_array($methods))
                    {
                        foreach($methods as $method)
                        {
                            if(substr($method, -6)=='Action')
                            {
                                $action = substr($method, 0, -6);
                                $return[] = $action;
                            }
                        }
                    }
                    
                    break;
                }
            }
        }
        
		return $return;
    }
    
    
    /**
     * Returns filename of a controller
     * @param string $module
     * @param string $controller
     * @return string
     */
    public static function getControllerFilename($module, $controller)
    {
        $filename = str_replace('_', DIRECTORY_SEPARATOR, $controller) . 'Controller.php';

        return $filename;
    }
    
    
    /**
     * Return last change of a controller
     * @param string $module
     * @param string $controller 
     */
    public static function getLastChangeOfController($module, $controller)
    {
        $front = Zend_Controller_Front::getInstance();
        
        if(substr($controller,0,8)=='service_')
        {
            
            $dir = $front->getModuleDirectory($module);

            if(is_null($dir))
            {
                return false;
            }
            
            $filename = realpath($dir) . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . substr($controller,8) . '.php';
        }
        else
        {
            $dirs = $front->getControllerDirectory();

            $module = strtolower($module);

            if(!isset($dirs[$module]))
            {
                return false;
            }
            $filename = $dirs[$module] . DIRECTORY_SEPARATOR . self::getControllerFilename($module, $controller);
        }
        
        return @filemtime($filename);
    }
}
