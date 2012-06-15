<?php
class webournal_Service_Files
{
    const VERSION = 2;
    
    private static $_hashes = array();
    /**
     *
     * @var webournal_Service_Directories 
     */
    private static $_directories = null;
    
    public function __construct($directories)
    {
        self::$_directories = $directories;
    }
    
    public static function getTagID($name)
    {
        return Core()->Db()->fetchOne('
            SELECT
                `id`
            FROM
                `webournal_tags`
            WHERE
                `name` = ?
        ', array(
            $name
        ));
    }
    
    private static function addTag($name)
    {
        $id = self::getTagID($name);
        
        if($id===false)
        {
            $id = Core()->Db()->insert('webournal_tags', array(
                'name' => $name
            ));
        }
        
        return $id;
    }
    
    public static function getFileIdsByHash($hash, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        return Core()->Db()->fetchCol('
            SELECT
                wf.`id`
            FROM
                `webournal_files` wf
            INNER JOIN
                `webournal_files_directory` wfd
            ON
                wfd.`file_id` = wf.`id`
            INNER JOIN
                `webournal_directory` wd
            ON
                wd.`id` = wfd.`directory_id`
            WHERE
                wf.`hash` = ? AND
                wd.`group_id` = ?
            GROUP BY
                wf.`id`
        ', array(
            $hash,
            $groupId
        ));
    }
    
    /**
     *
     * @param int $id
     * @param int $groupId
     * @return array
     * @todo Cache (delete on change)
     */
    public static function getFileById($id, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        $file = Core()->Db()->fetchRow('
            SELECT
                wf.*, wd.`group_id`
            FROM
                `webournal_files` wf
            INNER JOIN
                `webournal_files_directory` wfd
            ON
                wfd.`file_id` = wf.`id`
            INNER JOIN
                `webournal_directory` wd
            ON
                wd.`id` = wfd.`directory_id`
            WHERE
                wf.`id` = ? AND
                wd.`group_id` = ?
        ', array(
            $id,
            $groupId
        ));

        if($file===false)
        {
            return false;
        }
        
        $file = self::prepareFilesArray(array($file));
        return $file[0];
    }
    
    public static function getFiles($directoryId, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        $files = Core()->Db()->fetchAll('
            SELECT
                wf.*, wd.`group_id`
            FROM
                `webournal_files` wf
            INNER JOIN
                `webournal_files_directory` wfd
            ON
                wfd.`file_id` = wf.`id`
            INNER JOIN
                `webournal_directory` wd
            ON
                wd.`id` = wfd.`directory_id`
            WHERE
                wfd.`directory_id` = ? AND
                wd.`group_id` = ?
        ', array(
            $directoryId,
            $groupId
        ));
        
        return self::prepareFilesArray($files);
    }
    
    public static function getFileDirectories($fileId, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        return Core()->Db()->fetchCol('
            SELECT
                wd.`id`
            FROM
                `webournal_files` wf
            INNER JOIN
                `webournal_files_directory` wfd
            ON
                wfd.`file_id` = wf.`id`
            INNER JOIN
                `webournal_directory` wd
            ON
                wd.`id` = wfd.`directory_id`
            WHERE
                wf.`id` = ? AND
                wd.`group_id` = ?
        ', array(
            $fileId,
            $groupId
        ));
    }
    
    private static function prepareFilesArray($files)
    {
        reset($files);
        foreach($files as $key => $file)
        {
            $files[$key]['filename'] = realpath(Core()->getPublicUploadDirectory() . '/' . $file['group_id'] . '_' . $file['id'] . '_' . $file['hash'] . '.pdf');
            $files[$key]['url'] = Core()->getPublicUploadPath() . '/' . $file['group_id'] . '_' . $file['id'] . '_' . $file['hash'] . '.pdf';
        }
        
        reset($files);
        return $files;
    }
    
    public static function calcHash($filename)
    {
        if(!isset(self::$_hashes[$filename]))
        {
            self::$_hashes[$filename] = sha1_file($filename);
        }
        return self::$_hashes[$filename];
    }
    
    public static function checkFile($filename, $groupId=null, &$hash=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        try
        {
            $pdf = new Core_Service_PDF($filename);
        }
        catch(Exception $e)
        {
            if($e->getMessage()=== 'Cross-reference streams are not supported yet.')
            {
                try
                {
                    // version > 1.4
                    //exec('/usr/bin/gs -sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER -dCompatibilityLevel=1.4 -sOutputFile=' . $filename . '_tmp ' . $filename);
                    //rename($filename . '_tmp', $filename);
                    set_time_limit(0);
                    exec('/usr/bin/pdftops -level3 ' . $filename . ' ' . $filename . '.ps');
                    exec('/usr/bin/ps2pdf14 ' . $filename . '.ps ' . $filename);

                    unlink($filename. '.ps');

                    $pdf = new Core_Service_PDF($filename);
                }
                catch(Exception $e)
                {
                    throw new Exception('Not a PDF file', 30);
                }
            }
            else
            {
                throw new Exception('Not a PDF file', 30);
            }
        }
        
        $hash = self::calcHash($filename);
        
        $check = self::getFileIdsByHash($hash, $groupId);
        
        if(is_array($check) && count($check)>0)
        {
            reset($check);
            foreach($check as $key => $id)
            {
                $check[$key] = self::getFileById($id, $groupId);
            }
            return $check;
        }
        
        return true;
    }
    
    public static function addFile($filename, $directory, $name, $number='', $description='', $ignoreHash = false, $groupId = null)
    {
        Core()->Db()->beginTransaction();
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        if(empty($name))
        {
            throw new Exception('Name not set', 11);
        }
        
        $hash = '';
        $check = self::checkFile($filename, $groupId, $hash);
        if(!$ignoreHash)
        {
            if($check!==true)
            {
                Core()->Db()->rollBack();
                throw new Exception('File duplicated', 10);
            }
        }
        
        $check = Core()->Db()->insert('webournal_files', array(
            'name' => $name,
            'number' => $number,
            'description' => $description,
            'hash' => $hash,
            'created' => date('Y-m-d h:i:s')
        ));
        
        if($check!==1)
        {
            Core()->Db()->rollBack();
            throw new Exception('File could not add', 12);
        }
        
        $id = Core()->Db()->lastInsertId('webournal_files');
        
        if($id===false || !is_numeric($id))
        {
            Core()->Db()->rollBack();
            throw new Exception('File could not add', 12);
        }
        
        $newfilename = realpath(Core()->getPublicUploadDirectory()) . '/' . $groupId . '_' . $id . '_' . $hash . '.pdf';
        if(!rename($filename, $newfilename))
        {
            Core()->Db()->rollBack();
            throw new Exception('File could not add', 12);
        }
        
        @chmod($newfilename, 0644);
        
        try
        {
            self::addFileToDirectoryIntern($id, $directory);
        }
        catch(Exception $e)
        {
            Core()->Db()->rollBack();
            rename($newfilename, $filename);
            throw new Exception('File could not add', 12);
        }
        
        $check = Core()->Events()->trigger('webournal_Service_Files_newFile',array($id, $newfilename, $groupId));
        
        if($check===false)
        {
            Core()->Db()->rollBack();
            rename($newfilename, $filename);
            throw new Exception('File could not add', 12);
        }
        Core()->Db()->commit();
        
        return (int)$id;
    }
    
    public static function addFileToDirectory($fileId, $directoryId, $groupId = null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        $directory = self::$_directories->getDirectoryById($directoryId, $groupId);
        
        if($directory===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        $file = self::getFileById($fileId, $groupId);
        
        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        self::addFileToDirectoryIntern($fileId, $directoryId);
    }
    
    private static function addFileToDirectoryIntern($fileId, $directoryId)
    {
        Core()->Db()->query('
            REPLACE INTO
                `webournal_files_directory`
            SET
                `file_id` = ?,
                `directory_id` = ?
        ', array(
            $fileId,
            $directoryId
        ));
    }

    public static function editFile($fileId, $name, $number='', $description='', $groupId = null)
    {
        Core()->Db()->beginTransaction();
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }

        $file = self::getFileById($fileId, $groupId);

        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }

        if(empty($name))
        {
            throw new Exception('Name not set', 11);
        }

        Core()->Db()->query('
            UPDATE
                `webournal_files`
            SET
                `name` = ?,
                `number` = ?,
                `description`= ?
            WHERE
                `id` = ?
        ', array(
            $name,
            $number,
            $description,
            $fileId
        ));
        Core()->Db()->commit();

        return true;
    }
    
    private static function removeFile($fileId, $groupId)
    {
        $file = Core()->Db()->fetchRow('
            SELECT
                * 
            FROM
                `webournal_files`
            WHERE
                `id` = ?
        ', array(
            $fileId
        ));
        
        if(is_array($file) && isset($file['hash']))
        {
            $filename = realpath(Core()->getPublicUploadDirectory()) . '/' . $groupId . '_' . $fileId . '_' . $file['hash'] . '.pdf';
            
            $check = Core()->Events()->trigger('webournal_Service_Files_removeFile', array($fileId, $filename, $groupId));
    
            if($check===false)
            {
                throw new Exception('Could not remove file', 51);
            }
            
            @unlink($filename);
            
            Core()->Db()->query('
                DELETE FROM
                    `webournal_files`
                WHERE
                    `id` = ?
            ', array(
                $fileId
            ));
        }
    }
    
    public static function removeFileFromDirectory($fileId, $directoryId, $groupId = null)
    {
        $doTransaction = true;
        try
        {
            Core()->Db()->beginTransaction();
        }
        catch(Exception $e)
        {
            $doTransaction = false;
        }
        
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }

        $file = self::getFileById($fileId, $groupId);

        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        Core()->Db()->query('
            DELETE FROM
                `webournal_files_directory`
            WHERE
                `file_id` = ? AND
                `directory_id` = ?
        ', array(
            $fileId,
            $directoryId
        ));
        
        $check = Core()->Events()->trigger('webournal_Service_Files_removeFileFromDirectory', array($fileId, $directoryId, $groupId));
        
        if($check!==false)
        {
            if($doTransaction)
            {
                Core()->Db()->commit();
            }
        }
        else
        {
            if($doTransaction)
            {
                Core()->Db()->rollBack();
            }
            throw new Exception('Could not remove file', 51);
        }
    }
    
    public static function removeDirectoryEvent($directoryId, $groupId)
    {
        try
        {
            $files = self::getFiles($directoryId, $groupId);

            reset($files);

            foreach($files as $file)
            {
                self::removeFileFromDirectory($file['id'], $directoryId, $groupId);
            }
            
            return true;
        }
        catch(Exception $e)
        {
            
        }
        
        return false;
    }
    
    public static function removeFileFromDirectoryEvent($fileId, $directoryId, $groupId)
    {
        try
        {
            $dirs = self::getFileDirectories($fileId, $groupId);
            
            if(!is_array($dirs) || count($dirs)==0)
            {
                self::removeFile($fileId, $groupId);
            }
            
            return true;
        }
        catch(Exception $e)
        {
            
        }
        return false;
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
                'webournal_Service_Files_removeFileFromDirectory',
                'webournal_Service_Files',
                'removeFileFromDirectoryEvent'
            );
        
            Core()->Events()->addSubscription(
                'webournal_Service_Directories_removeDirectory',
                'webournal_Service_Files',
                'removeDirectoryEvent'
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
            CREATE TABLE IF NOT EXISTS `webournal_files` (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(200) NOT NULL,
              `number` varchar(50) NOT NULL,
              `description` text NOT NULL,
              `hash` varchar(40) NOT NULL,
              `created` timestamp NULL default NULL,
              `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
              PRIMARY KEY  (`id`),
              KEY `hash` (`hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_tags` (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(50) NOT NULL,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_files_directory` (
              `file_id` int(11) NOT NULL,
              `directory_id` int(11) NOT NULL,
              PRIMARY KEY  (`file_id`,`directory_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_files_tags` (
              `file_id` int(11) NOT NULL,
              `tag_id` int(11) NOT NULL,
              PRIMARY KEY  (`file_id`,`tag_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Events()->addListener('webournal_Service_Files_newFile');
        Core()->Events()->addListener('webournal_Service_Files_removeFile');
        Core()->Events()->addListener('webournal_Service_Files_removeFileFromDirectory');
        return true;
    }
}