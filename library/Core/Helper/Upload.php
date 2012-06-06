<?php

class Core_Helper_Upload
{
    public function __construct()
    {
        $this->clean();
    }
    
    private function clean()
    {
        $maxage = Core()->getTempMaxAge();
        
        $files = Core()->Db()->fetchAll('
            SELECT
                `id`, `group_id`
            FROM
                `tmp_files`
            WHERE
                (UNIX_TIMESTAMP( NOW() ) - UNIX_TIMESTAMP( `created` )) > ?
        ', array(
            $maxage
        ));
        
        foreach($files as $file)
        {
            $this->delete($file['id'], $file['group_id']);
        }
    }
    
    public function getById($fileId, $groupId = null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        $file = Core()->Db()->fetchRow('
            SELECT
                *
            FROM
                `tmp_files`
            WHERE
                `id` = ? AND
                `group_id` = ?
        ', array(
            $fileId,
            $groupId
        ));
        
        if(!is_array($file) || !isset($file['filearray']))
        {
            return false;
        }

        $file['data'] = unserialize($file['filearray']);
        unset($file['filearray']);
        
        return $file;
    }
    
    public function delete($fileId, $groupId = null)
    {
        $file = $this->getById($fileId, $groupId);
        
        if($file!==false)
        {
            @unlink($file['tmpname']);
            Core()->Db()->query('
                DELETE FROM
                    `tmp_files`
                WHERE
                    `id` = ?
            ', array(
                $fileId
            ));
        }
    }
    
    public function upload($file_variable_name)
    {
        if(!isset($_FILES[$file_variable_name]))
        {
            throw new Exception('File not found', 20);
        }
        
        $file = $_FILES[$file_variable_name];
        
        if($file['error']>0 || $file['size']==0)
        {
            throw new Exception('Error on file upload', 21);
        }
        
        $dir = Core()->getTempUploadDirectory();
        $tempfile = tempnam($dir, 'UTF');
        
        if($tempfile===false)
        {
            @unlink($file['tmp_name']);
            throw new Exception('Could not create temp file', 22);
        }
        
        move_uploaded_file($file['tmp_name'], $tempfile);
        
        $check = Core()->Db()->insert('tmp_files', array(
            'group_id' => Core()->getGroupId(),
            'tmpname' => $tempfile,
            'filearray' => serialize($file)
        ));
        
        if($check!=1)
        {
            @unlink($tempfile);
            throw new Exception('Could not insert uploaded file', 23);
        }
        
        $id = Core()->Db()->lastInsertId('tmp_files');
        
        if($id===false || !is_numeric($id))
        {
            @unlink($tempfile);
            throw new Exception('Could not insert uploaded file', 23);
        }
        
        return (int)$id;
    }
}