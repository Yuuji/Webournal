<?php

/**
 * Installer plugin
 */
class Core_Installer_Installer
{
    /**
     * Runs installer
     */
    public static function run()
    {
        $db = Core()->Db();

        try
        {
            $db->getConnection();
        }
        catch(Exception $e)
        {
            echo 'Could not connect to database! <br /><br />';
            echo $e->getMessage();
            exit;
        }
        // Check if application is installed

        $sql = 'SHOW TABLES LIKE "applications"';

        $check = $db->fetchOne($sql);

        if($check===false)
        {
            // Application is not installed

            $sql = 'CREATE TABLE `applications` (
                `module` VARCHAR( 50 ) NOT NULL ,
                `controller` VARCHAR( 50 ) NOT NULL ,
                `version` INT NOT NULL ,
                `filetime` TIMESTAMP NOT NULL DEFAULT  "0000-00-00",
                PRIMARY KEY (  `module`, `controller` )
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8';

            $db->exec($sql);

            $sql = 'INSERT INTO
                        `applications`
                    SET
                        `module`="application",
                        `controller`="main",
                        `version`=0';

            $db->exec($sql);
        }
        
        // events
        
        $db->query('
            CREATE TABLE IF NOT EXISTS `events` (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(100) NOT NULL,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        // events_subscriptions
        
        $db->query('
            CREATE TABLE IF NOT EXISTS `events_subscriptions` (
              `id` int(11) NOT NULL auto_increment,
              `event_id` int(11) NOT NULL,
              `class` varchar(100) NOT NULL,
              `method` varchar(100) NOT NULL,
              `priority` int(11) NOT NULL,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `event_id` (`event_id`,`class`,`method`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        // acl_resources
        
        $sql = 'SHOW TABLES LIKE "acl_resources"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_resources` (
                  `module` varchar(50) NOT NULL,
                  `controller` varchar(50) NOT NULL,
                  `action` varchar(50) NOT NULL,
                  `sub` varchar(50) NOT NULL,
                  UNIQUE KEY `module` (`module`,`controller`,`action`,`sub`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);
        }
        
        // acl_permissions_default
        
        $sql = 'SHOW TABLES LIKE "acl_permissions_default"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_permissions_default` (
                  `type` enum("allow","deny") NOT NULL,
                  `permissions_group_id` int(11) NOT NULL,
                  `group_id` int(11) NULL,
                  `resource` varchar(100) NOT NULL,
                  UNIQUE KEY `type` (`type`,`permissions_group_id`,`resource`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);
        }
        
        // menu
        $db->exec('
            CREATE TABLE IF NOT EXISTS `menu` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `parent` int(11) DEFAULT NULL,
              `label` varchar(100) NOT NULL,
              `defaultTranslation` varchar(100) NOT NULL,
              `type` enum("container","controller","url") NOT NULL,
              `module` varchar(200) DEFAULT NULL,
              `controller` varchar(200) DEFAULT NULL,
              `action` varchar(200) DEFAULT NULL,
              `params` varchar(1000) DEFAULT NULL,
              `url` varchar(500) DEFAULT NULL,
              `class` varchar(100) NOT NULL,
              `order` int(11) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');
        
        // translations
        
        $db->exec('
            CREATE TABLE IF NOT EXISTS `translations` (
              `module` varchar(50) NULL DEFAULT NULL,
              `controller` varchar(50) NULL DEFAULT NULL,
              `action` varchar(50) NULL DEFAULT NULL,
              `name` varchar(100) NOT NULL,
              `language` varchar(50) NOT NULL,
              `value` text NOT NULL,
              UNIQUE KEY `module` (`module`,`controller`,`action`,`name`,`language`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');
        
        $db->exec('
            CREATE TABLE IF NOT EXISTS `translations_missing` (
              `module` varchar(50) NULL DEFAULT NULL,
              `controller` varchar(50) NULL DEFAULT NULL,
              `action` varchar(50) NULL DEFAULT NULL,
              `name` varchar(100) NOT NULL,
              `language` varchar(50) NOT NULL,
              `default_value` text NULL DEFAULT NULL,
              PRIMARY KEY (`module`,`controller`,`action`,`name`,`language`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');

        $sql = 'SELECT
                    module, controller, version
                FROM
                    `applications`';

        $applications = $db->fetchAll($sql);

        $controllerArray = Core()->PHP()->getControllers();
        
        reset($applications);
        foreach($applications as $row)
        {
            if($row['module'] != 'application')
            {
                $key = array_search($row['controller'], $controllerArray[$row['module']]);
                
                if($key!==false)
                {
                    unset($controllerArray[$row['module']][$key]);
                }
            }
        }
        
        reset($controllerArray);
        foreach($controllerArray as $module => $controllers)
        {
            foreach($controllers as $controller)
            {
                $sql = 'REPLACE INTO
                            `applications`
                        SET
                            `module`= ?,
                            `controller`= ?,
                            `version`=0';
                $db->query($sql, array($module, $controller));
                
                self::addACLResource($module, $controller);
            }
        }
        
        $serviceArray = Core()->PHP()->getServices();

        reset($applications);
        foreach($applications as $row)
        {
            if($row['module'] != 'application' && substr($row['controller'],0,8) === 'service_')
            {
				$controller = substr($row['controller'], 8);
                $key = array_search($controller, $serviceArray[$row['module']]);
                
                if($key!==false)
                {
                    unset($serviceArray[$row['module']][$key]);
                }
            }
        }
        
        reset($serviceArray);
        foreach($serviceArray as $module => $services)
        {
            foreach($services as $service)
            {
                $sql = 'REPLACE INTO
                            `applications`
                        SET
                            `module`= ?,
                            `controller`= ?,
                            `version`=0';
                $db->query($sql, array($module, 'service_' . $service));
            }
        }
    }
    
    public static function addACLResource($module, $controller, $action='', $sub='')
    {
        Core()->Db()->query('
            REPLACE INTO
                `acl_resources`
            SET
                `module` = ?,
                `controller` = ?,
                `action` = ?,
                `sub` = ?
        ', array($module, $controller, $action, $sub));
    }
    
    public static function addACLDefaultPermissions($group, $resource, $type='deny')
    {
        if($type!='deny' && $type!='allow')
        {
            throw new Exception('unknown type');
        }
        
        if(is_int($group))
        {
            $group_id = Core()->Db()->fetchOne('
                SELECT
                    id
                FROM
                    `acl_permissions_groups`
                WHERE
                    `id` = ?
            ', array($group));
        }
        else
        {
            $group_id = Core()->Db()->fetchOne('
                SELECT
                    id
                FROM
                    `acl_permissions_groups`
                WHERE
                    `name` = ?
            ', array($group));
        }
        
        if($group_id===false || intval($group_id)<1)
        {
            throw new Exception('Unknown group');
        }
        
        Core()->Db()->query('
            REPLACE INTO
                `acl_permissions_default`
            SET
                type = ?,
                permissions_group_id = ?,
                resource = ?
        ', array($type, $group_id, $resource));
    }
}
