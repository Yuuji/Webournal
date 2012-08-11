<?php
class webournal_Service_Directories
{
    const VERSION = 3;
    
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

	private static function addRootNode($group_id, $name)
	{
		$check = Core()->Db()->fetchOne('
			SELECT
				`id`
			FROM
				`webournal_directory`
			WHERE
				`lft` = 1 AND
				`group_id` = ?
		', array($group_id));

		if($check===false)
		{
			Core()->Db()->query('
				INSERT INTO
					`webournal_directory`
				SET
					`name` = ?,
					`type` = "directory",
					`lft` = 1,
					`rgt` = 2,
					`group_id` = ?
			', array(
				$name,
				$group_id
			));
		}
	}

	private static function editRootNode($group_id, $name)
	{
		Core()->Db->query('
			UPDATE
				`webournal_directory`
			SET
				`name` = ?
			WHERE
				`group_id` = ? AND
				`ltf` = 1
		', array(
			$name,
			$group_id
		));
	}

	private static function removeRootNode($group_id)
	{
		Core()->Db()->query('
			DELETE FROM
				`webournal_directory`
			WHERE
				`group_id` = ?
		', array(
			$group_id
		));
	}

	/**
	 * Adds a node to nested tree
	 *
	 * @param int|null $parent Parent node
	 * @param int $group_id ACL group id
	 * @param string $name Name
	 * @param string $type Type
	 * @param string $directory_time Directory time
	 * @param string $description Description
	 * @param int|null $id DON'T SET THIS! ONLY FOR MIGRATION!
	 * @param string|null $created DON'T SET THIS! ONLY FOR MIGRATION!
	 * @return int ID of new node
	 */
	private static function addNode($parent, $group_id, $name, $type, $directory_time, $description, $id=null, $created=null)
	{
		if(is_null($parent))
		{
			$parentNode = Core()->Db()->fetchRow('
				SELECT
					`lft`, `rgt`
				FROM
					`webournal_directory`
				WHERE
					`lft` = 1 AND
					`group_id` = ?
			', array(
				$group_id
			));
		}
		else
		{
			$parentNode = Core()->Db()->fetchRow('
				SELECT
					`lft`, `rgt`
				FROM
					`webournal_directory`
				WHERE
					`id` = ? AND
					`group_id` = ?
			', array(
				$parent,
				$group_id
			));
		}

		if(!is_array($parentNode) || !isset($parentNode['rgt']))
		{
			throw new Exception('Parent id is not correct', 1);
		}

		$RGT = $parentNode['rgt'];
		$LFT = $parentNode['lft'];

		Core()->Db()->query('
			UPDATE
				`webournal_directory`
			SET
				`rgt`=`rgt` + 2
			WHERE
				`rgt` >= ? AND
				`group_id` = ?
		', array(
			$RGT,
			$group_id
		));

		Core()->Db()->query('
			UPDATE
				`webournal_directory`
			SET
				`lft`=`lft` + 2
			WHERE
				`lft` > ? AND
				`group_id` = ?
		', array(
			$RGT,
			$group_id
		));

        $sql = '
            INSERT INTO
                `webournal_directory`
            SET
				`lft` = ?,
				`rgt` = ?,
                `name` = ?,
                `type` = ?,
                `description` = ?,
                `directory_time` = ?,
                `group_id` = ?,
                `parent` = ';

        $values = array(
			$RGT,
			$RGT+1,
            $name,
            $type,
            $description,
            $directory_time,
            $group_id
        );

        if(is_null($parent))
        {
            $sql .= 'NULL';
        }
        else
        {
            $sql .= '?';
            $values[] = $parent;
        }

		if(!is_null($id))
		{
			$sql .= ',
				`id` = ?';
			$values[] = $id;
		}

		if(!is_null($created))
		{
			$sql .= ',
				`created` = ?';
			$values[] = $created;
		}

        Core()->Db()->query($sql, $values);

		if(!is_null($id))
		{
        	$id = Core()->Db()->lastInsertId('webournal_directory');
		}

        if(!is_numeric($id))
        {
            throw new Exception('Could not add directory, unknown error', 99);
        }

        return (int)$id;
	}

	private function removeNode($id, $group_id)
	{
		$node = Core()->Db()->fetchRow('
			SELECT
				`lft`, `rgt`
			FROM
				`webournal_directory`
			WHERE
				`id` = ? AND
				`group_id` = ?
		', array(
			$id,
			$group_id
		));

		if(!is_array($node) || !isset($node['rgt']))
		{
			throw new Exception('id is not correct', 1);
		}

		$LFT = $node['lft'];
		$RGT = $node['rgt'];

		Core()->Db()->query('
			DELETE FROM
				`webournal_directory`
			WHERE
				`group_id` = ? AND
				`lft` BETWEEN ? AND ?
		', array(
			$group_id,
			$LFT,
			$RGT
		));

		Core()->Db()->query('
			UPDATE
				`webournal_directory`
			SET
				`lft` = `lft` - ROUND((?-?+1))
			WHERE
				`lft` > ?
		', array(
			$RGT,
			$LFT,
			$RGT
		));

		Core()->Db()->query('
			UPDATE
				`webournal_directory`
			SET
				`rgt` = `rgt` - ROUND((?-?+1))
			WHERE
				`rgt` > ?
		', array(
			$RGT,
			$LFT,
			$RGT
		));
	}

    public function addDirectory($name, $type, $description = '', $date = '', $parent = null, $groupid = null)
    {
        $this->checkValues($name, $type, $date);
        
        if(is_null($groupid))
        {
            $groupid = Core()->getGroupId();
        }
		
		return self::addNode($parent, $groupid, $name, $type, $date, $description);
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

	public static function addGroupEvent($id, $url, $name, $adminemail, $description)
	{
		self::addRootNode($id, $name);
	}

	public static function editGroupEvent($id, $name, $description)
	{
		self::editRootNode($id, $name);
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

		self::removeRootNode($id);
        
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

			self::removeNode($id, $group_id);
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

	private static function migrateToNested($group_id, $parent=null)
	{
		if(is_null($parent))
		{
			$dirs = Core()->Db()->fetchAll('
				SELECT
					*
				FROM
					`webournal_directory_update`
				WHERE
					`group_id` = ? AND
					`parent` IS NULL
			',array(
				$group_id
			));
		}
		else
		{
			$dirs = Core()->Db()->fetchAll('
				SELECT
					*
				FROM
					`webournal_directory_update`
				WHERE
					`group_id` = ? AND
					`parent` = ? 
			',array(
				$group_id,
				$parent
			));
		}

		foreach($dirs as $dir)
		{
			self::addNode($parent, $group_id, $dir['name'], $dir['type'], $dir['directory_time'], $dir['description'], $dir['id'], $dir['created']);

			self::migrateToNested($group_id, $dir['id']);

			Core()->Db()->query('
				DELETE FROM
					`webournal_directory_update`
				WHERE
					`id` = ?
			', array(
				$dir['id']
			));
		}
	}

	private static function update3()
	{
		// We want to move to nested set
		// We keep parent id till we are sure the new system works

		$ai = Core()->Db()->fetchRow('SHOW TABLE STATUS WHERE `Name` = "webournal_directory"');
		$ai = $ai['Auto_increment'];

		Core()->Db()->query('RENAME TABLE `webournal_directory` TO `webournal_directory_update`;');
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_directory` (
              `id` int(12) NOT NULL auto_increment,
              `group_id` int(11) NOT NULL,
              `parent` INT(12) NULL,
			  `lft` INT(12) NULL,
			  `rgt` INT(12) NULL,
              `name` varchar(100) NOT NULL,
              `type` enum("directory","date") NOT NULL,
              `directory_time` timestamp NULL default NULL,
              `description` varchar(500) NOT NULL,
              `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
              PRIMARY KEY  (`id`),
			  KEY lft (`lft`),
			  KEY rgt (`rgt`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=' . intval($ai) . ';
        ', array());

		// Now do magic
		$groups = Core()->Db()->fetchAll('
			SELECT
				`id`, `name`
			FROM
				`acl_groups`
		');

		foreach($groups as $group)
		{
			self::addRootNode($group['id'], $group['name']);
			self::migrateToNested($group['id'], null);
		}

		Core()->Db()->query('DROP TABLE `webournal_directory_update`');

        Core()->Events()->addSubscription(
            'core_Service_Groups_addGroup',
            'webournal_Service_Directories',
            'addGroupEvent'
        );
        
		Core()->Events()->addSubscription(
            'core_Service_Groups_editGroup',
            'webournal_Service_Directories',
            'editGroupEvent'
        );

		return true;
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
