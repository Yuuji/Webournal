<?php

/**
 * Updater
 */
class Core_Installer_Updater
{
    /**
     * Version of main application
     */
    const MAIN_VERSION = 1;

    /**
     * Runs updater
     */
    public static function run()
    {
        $db = Core()->Db();

        $sql = 'SELECT
                    module, controller, version, filetime
                FROM
                    `applications`';

        $applications = $db->fetchAll($sql);

        reset($applications);
        foreach($applications as $row)
        {
            if($row['module']=='application')
            {
                self::checkMain($row['controller'], $row['version']);
            }
            else
            {
                self::checkController($row['module'], $row['controller'], $row['version'], $row['filetime']);
            }
        }
    }

    /**
     * Checks the update status for main project
     * @param string $module
     * @param int $version 
     */
    public static function checkMain($module, $version)
    {
        switch($module)
        {
            case 'main':
                $newVersion = self::updateMain($version);
                break;
        }

        if(isset($newVersion) && $newVersion!=$version)
        {
            self::updateVersion('application', $module, $newVersion);
        }
    }

    /**
     * Checks the update status for controller
     * @param string $module
     * @param string $controller
     * @param int $version 
     * @param timestamp $filetime
     */
    public static function checkController($module, $controller, $version, $filetime)
    {
        $lastchange = Core()->PHP()->getLastChangeOfController($module, $controller);
        if($filetime==='0000-00-00 00:00:00' || strtotime($filetime)<$lastchange)
        {
            if(substr($controller, 0, 8)!='service_')
            {
                $actions = Core()->PHP()->getActionsOfControllers($module, $controller);

                reset($actions);
                foreach($actions as $action)
                {
                    Core_Installer_Installer::addACLResource($module, $controller, $action);
                }

                if($module==Core()->getDefaultModule())
                {
                    $class = $controller . 'Controller';
                }
                else
                {
                    $class = $module . '_' . $controller . 'Controller';
                }
            }
            else
            {
                $name = substr($controller,8);
                $class = $module . '_Service_' . $name;
                Core()->PHP()->includeService($module, $name);
            }

            if(method_exists($class, 'updater'))
            {
                $newVersion = call_user_func(array($class, 'updater'), $version);
            }
            else
            {
                $newVersion = $version;
            }
            
            self::updateVersion($module, $controller, $newVersion, $lastchange);
        }
    }

    /**
     * Updates the version status in database
     * @param string $module
     * @param string $controller
     * @param int $version 
     * @param timestamp $lastchange
     */
    public static function updateVersion($module, $controller, $version, $lastchange='0000-00-00')
    {
        $db = Core()->Db();

        $sql = 'UPDATE
                    `applications`
                SET
                    `version`= ?,
                    `filetime`=?
                WHERE
                    `module`= ? AND
                    `controller`= ?';
        $db->query($sql, array($version, ($lastchange == '0000-00-00' ? $lastchange : date('Y-m-d H:i:s', $lastchange)), $module, $controller));
    }



    /**
     * Updates main project
     * @param int $version
     * @return int New version 
     */
    private static function updateMain($version)
    {
        if($version<self::MAIN_VERSION)
        {
            for($i=$version+1; $i<=self::MAIN_VERSION; $i++)
            {
                $function = 'updateMain' . $i;
                if(!self::$function())
                {
                    return $i-1;
                }
            }
        }

        return self::MAIN_VERSION;
    }

    /**
     * Updates main project from version 0 to 1 (Installer)
     */
    private static function updateMain1()
    {
        $db = Core()->Db();

        // acl_opermissions

        $sql = 'SHOW TABLES LIKE "acl_permissions"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_permissions` (
                    `group` int(11) NOT NULL,
                    `permission` varchar(50) collate utf8_bin NOT NULL,
                    UNIQUE KEY `group` (`group`,`permission`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);
        }

        // acl_permissions_groups

        $sql = 'SHOW TABLES LIKE "acl_permissions_groups"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_permissions_groups` (
                    `id` int(11) NOT NULL auto_increment,
                    `name` varchar(20) collate utf8_bin NOT NULL,
                    `description` varchar(200) collate utf8_bin NOT NULL,
                    PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);

            $sql = '
                INSERT INTO
                    `acl_permissions_groups`
                SET
                    `id`=1,
                    `name`="admin",
                    `description`="Administratoren"
            ';

            $db->exec($sql);

            $sql = '
                INSERT INTO
                    `acl_permissions_groups`
                SET
                    `id`=2,
                    `name`="visitor",
                    `description`="Besucher"
            ';

            $db->exec($sql);
            
            $sql = '
                INSERT INTO
                    `acl_permissions_groups`
                SET
                    `id`=3,
                    `name`="guest",
                    `description`="Gäste"
            ';

            $db->exec($sql);
        }
        
        // acl_groups

        $sql = 'SHOW TABLES LIKE "acl_groups"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_groups` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(100) NOT NULL,
                  `url` varchar(100) NOT NULL,
                  `description` varchar(100) NOT NULL,
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `url` (`url`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);

            $sql = '
                INSERT INTO
                    `acl_groups`
                SET
                    `id`=0,
                    `name`="www",
                    `url`="www",
                    `description`="www"
            ';

            $db->exec($sql);
        }

        // acl_users

        $sql = 'SHOW TABLES LIKE "acl_users"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_users` (
                  `id` int(11) NOT NULL auto_increment,
                  `username` varchar(50) NOT NULL,
                  `password` varchar(200) NOT NULL,
                  `email` varchar(255) NULL,
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);
        }
        
        // acl_users_groups

        $sql = 'SHOW TABLES LIKE "acl_users_groups"';

        if($db->fetchOne($sql)===false)
        {
            $sql = '
                CREATE TABLE IF NOT EXISTS `acl_users_groups` (
                  `user_id` int(11) NOT NULL,
                  `group_id` int(11) default NULL,
                  `permissions_group_id` int(11) NOT NULL,
                  UNIQUE KEY `user_id` (`user_id`,`group_id`,`permissions_group_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ';

            $db->exec($sql);
        }

        $user_id = Core()->AccessControl()->addUser('admin', 'admin');
        Core()->AccessControl()->addUserGroup($user_id, NULL, 1);

        $db->query('
            CREATE TABLE IF NOT EXISTS `tmp_files` (
              `id` int(11) NOT NULL auto_increment,
              `group_id` int(11) NOT NULL,
              `tmpname` varchar(500) NOT NULL,
              `filearray` text NOT NULL,
              `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `settings` (
              `acl_group` int(11) NOT NULL,
              `group` varchar(100) NOT NULL,
              `name` varchar(100) NOT NULL,
              `value` varchar(200) NOT NULL,
              PRIMARY KEY (`acl_group`, `group`, `name`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
        ');
        
        return true;
    }
}