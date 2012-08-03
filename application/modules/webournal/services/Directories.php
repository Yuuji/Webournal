<?php
class webournal_Service_Directories
{
    const VERSION = 2;
    
    public static function getDirectories($parent=null, $groupid=null)
    {
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }

        if(is_null($parent))
        {
            $directories = Core()->Db()->fetchAll('
                SELECT
                    d.*
                FROM
                    `webournal_directory` d
                WHERE
                    d.`group_id` = ? AND
                    d.`parent` IS NULL
                ORDER BY d.`directory_time` DESC, d.`name`
            ', array(
                $groupid
            ));
        }
        else
        {
            $directories = Core()->Db()->fetchAll('
                SELECT
                    d.*
                FROM
                    `webournal_directory` d
                WHERE
                    d.`group_id` = ? AND
                    d.`parent` = ?
                ORDER BY d.`directory_time` DESC, d.`name`
            ', array(
                $groupid,
                $parent
            ));
        }
        
        return $directories;
    }

	public static function getYearSpan($groupid=null)
	{
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }

        $span = Core()->Db()->fetchRow('
        	SELECT
            	YEAR(MIN(d.`directory_time`)) as `min`,
				YEAR(MAX(d.`directory_time`)) as `max`
            FROM
            	`webournal_directory` d
            WHERE
                d.`group_id` = ? AND
				d.`directory_time` IS NOT NULL AND
				d.`directory_time` != 0
        ', array(
            $groupid
        ));
        
        return $span;
	}

    public static function getDirectoriesByMonth($month, $year, $groupid=null)
    {
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }

        $directories = Core()->Db()->fetchAll('
        	SELECT
            	d.*, DAY(d.`directory_time`) as dayofmonth
            FROM
            	`webournal_directory` d
            WHERE
                d.`group_id` = ? AND
				MONTH(d.`directory_time`) = ? AND
				YEAR(d.`directory_time`) = ?
            ORDER BY d.`directory_time`, d.`name`
        ', array(
            $groupid,
			$month,
			$year
        ));
        
        return $directories;
    }

    public static function getDirectoryById($directoryId, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }

        return Core()->Db()->fetchRow('
                SELECT
                    d.*
                FROM
                    `webournal_directory` d
                WHERE
                    d.`group_id` = ? AND
                    d.`id` = ?
            ', array(
                $groupId,
                $directoryId
            ));
    }
    
    private static function checkValues($name, $type, $date)
    {
        if(empty($name))
        {
            throw new Exception('Name not set', 10);
        }
        else if($type == 'date' && empty($date))
        {
            throw new Exception('Date not set', 11);
        }
        else if($type == 'date' && ($datetime = strtotime($date))===false)
        {
            throw new Exception('Date incorrect', 12);
        }
    }

    public function addDirectory($name, $type, $description = '', $date = '', $parent = null, $groupid = null)
    {
        $this->checkValues($name, $type, $date);
        
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }

        $sql = '
            INSERT INTO
                `webournal_directory`
            SET
                `name` = ?,
                `type` = ?,
                `description` = ?,
                `directory_time` = ?,
                `group_id` = ?,
                `parent` = ';

        $values = array(
            $name,
            $type,
            $description,
            $date,
            $groupid
        );

        if(is_null($parent))
        {
            $sql .= 'NULL';
        }
        else
        {
            $check = $this->getDirectoryById($parent, $groupid);

            if($check===false)
            {
                throw new Exception('Parent id not correct', 1);
            }

            $sql .= '?';
            $values[] = $parent;
        }

        Core()->Db()->query($sql, $values);

        $id = Core()->Db()->lastInsertId('webournal_directory');

        if(!is_numeric($id))
        {
            throw new Exception('Could not add directory, unknown error', 99);
        }

        return (int)$id;
    }
    
    public function editDirectory($id, $name, $type, $description = '', $date = '', $groupid = null)
    {
        $this->checkValues($name, $type, $date);
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }
        
        $check = $this->getDirectoryById($id);
        
        if($check===false)
        {
            throw new Exception('id not correct', 1);
        }

        try {
            Core()->Db()->query('
                UPDATE
                    `webournal_directory`
                SET
                    `name` = ?,
                    `type` = ?,
                    `description` = ?,
                    `directory_time` = ?
                WHERE
                    `id` = ? AND
                    `group_id` = ?
            ', array(
                $name,
                $type,
                $description,
                $date,
                $id,
                $groupid
            ));
        }
        catch(Exception $e)
        {
            throw new Exception('Could not edit directory, unknown error', 99);
        }
        
        return true;
    }
    
    public static function removeGroupEvent($id)
    {
        $childs = self::getDirectories(null, $id);
        
        reset($childs);
        foreach($childs as $child)
        {
            try
            {
                self::removeDirectory($child['id'], $id);
            }
            catch(Exception $e)
            {
                return false;
            }
        }
        
        return true;
    }
    
    public static function removeDirectoryEvent($id, $groupid)
    {
        $childs = self::getDirectories($id, $groupid);
        
        reset($childs);
        foreach($childs as $child)
        {
            try
            {
                self::removeDirectory($child['id'], $groupid);
            }
            catch(Exception $e)
            {
                return false;
            }
        }
        
        return true;
    }
    
    public static function removeDirectory($id, $groupid = null)
    {
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }
        
        $check = self::getDirectoryById($id, $groupid);
        
        if($check===false)
        {
            throw new Exception('id not correct', 1);
        }
        
        try
        {
            $check = Core()->Events()->trigger('webournal_Service_Directories_removeDirectory', array($id, $groupid));
            
            if($check==false)
            {
                throw new Exception('Could not remove directory, sub remove failed', 98);
            }
            
            Core()->Db()->query('
                DELETE FROM
                    `webournal_directory`
                WHERE
                    `id` = ? AND
                    `group_id` = ?
            ', array(
                $id,
                $groupid
            ));
        }
        catch(Exception $e)
        {
            switch($e->getCode())
            {
                case 98:
                    throw $e;
                    break;
                default:
                    throw new Exception('Could not remove directory, unknown error', 99);
            }
        }
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
    
    private static function update2()
    {
        try
        {
            Core()->Events()->addSubscription(
                'webournal_Service_Directories_removeDirectory',
                'webournal_Service_Directories',
                'removeDirectoryEvent'
            );

            Core()->Events()->addSubscription(
                'core_Service_Groups_removeGroup',
                'webournal_Service_Directories',
                'removeGroupEvent'
            );
        }
        catch(Exception $e)
        {
            return false;
        }
        
        return true;
    }

    private static function update1()
    {
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_directory` (
              `id` int(11) NOT NULL auto_increment,
              `group_id` int(11) NOT NULL,
              `parent` INT(11) NULL,
              `name` varchar(100) NOT NULL,
              `type` enum("directory","date") NOT NULL,
              `directory_time` timestamp NULL default NULL,
              `description` varchar(500) NOT NULL,
              `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        Core()->Events()->addListener('webournal_Service_Directories_removeDirectory');
        
        return true;
    }
}
