<?php
class core_Service_Groups
{
    const VERSION = 1;

    public function getGroups()
    {
        /** @todo add admin */
        $groups = Core()->Db()->fetchAll('
            SELECT
                ag.*
            FROM
                `acl_groups` ag
            WHERE
                ag.url!="www"
        ');

        if(!is_array($groups))
        {
            $groups = array();
        }

        return $groups;
    }

    public function getGroupById($id)
    {
        /** @todo add admin email */
        return Core()->Db()->fetchRow('
            SELECT
                ag.*
            FROM
                `acl_groups` ag
            WHERE
                ag.`id` = ?
        ', array(
            $id
        ));
    }

    public function getGroupByURL($url)
    {
        /** @todo add admin email */
        return Core()->Db()->fetchRow('
            SELECT
                ag.*
            FROM
                `acl_groups` ag
            WHERE
                ag.`url` = ?
        ', array(
            $url
        ));
    }
    
    public function checkUserParams($adminemail = null, $username = null)
    {
        $error_email = false;
        $error_username = false;
        if(!is_null($adminemail) && ($adminemail===false || strlen($adminemail)==0))
        {
            $error_email = true;
        }
        
        if(!is_null($username) && ($username===false || strlen($username)==0))
        {
            $error_username = true;
        }
        
        if($error_email && strlen($adminemail)==0 && !$error_username)
        {
            $adminemail = null;
            $error_email = false;
        }
        else if($error_username && strlen($username)==0 && !$error_email)
        {
            $username = null;
            $error_username = false;
        }
        
        if($error_email && (is_null($username) || $error_username))
        {
            throw new Exception('Email not set', 12);
        }
        
        if($error_username && is_null($adminemail))
        {
            throw new Exception('Username not set', 16);
        }
        
        $userIdEmail = false;
        $userIdUsername = false;
        
        if(!is_null($adminemail) && ($userIdEmail = Core()->AccessControl()->getUserIdByEmail($adminemail))===false)
        {
            throw new Exception('Email not found', 15);
        }
        
        if(!is_null($username) && ($userIdUsername = Core()->AccessControl()->getUserIdByUsername($username))===false)
        {
            throw new Exception('Username not found', 17);
        }
        
        
        if($userIdEmail!==false && $userIdUsername!==false && $userIdEmail!=$userIdUsername)
        {
            throw new Exception('User id of email address is not equal to user id of username', 18);
        }
        
        $userId = $userIdEmail;
        
        if($userId===false)
        {
            $userId = $userIdUsername;
        }
        
        return array(
            'userId' => $userId
        );
    }

    public function checkParams($name, $description = '', $url = null, $adminemail = null, $username = null)
    {
        $userId = null;
        $success = false;

        if(!is_null($url) && ($url===false || strlen($url)==0))
        {
            throw new Exception('URL not set', 10);
        }
        else if($name===false || strlen($name)==0)
        {
            throw new Exception('Name not set', 11);
        }
        else if(!is_null($adminemail) && ($adminemail===false || strlen($adminemail)==0))
        {
            throw new Exception('Email not set', 12);
        }
        else if(!is_null($url) && $this->getGroupByURL($url)!==false)
        {
            throw new Exception('URL is duplicated', 13);
        }
        else if(!is_null($url) && $this->checkURL($url)===false)
        {
            throw new Exception('URL not correct', 14);
        }
        else if(!is_null($adminemail) && ($userId = Core()->AccessControl()->getUserIdByEmail($adminemail))===false)
        {
            throw new Exception('Email not found', 15);
        }

        $success = true;

        return array(
            'success' => $success,
            'userId'  => $userId
        );
    }

    public function addGroup($url, $name, $adminemail, $description='')
    {
        $checkParams = $this->checkParams($name, $description, $url, $adminemail);

        try
        {
            Core()->Db()->beginTransaction();

            Core()->Db()->query('
                INSERT INTO
                    `acl_groups`
                SET
                    `name` = ?,
                    `url` = ?,
                    `description` = ?
            ', array(
                $name,
                $url,
                $description
            ));

            $id = Core()->Db()->lastInsertId('acl_groups');

            if($id===false || !is_numeric($id))
            {
                throw new Exception('Unknown error', 100);
            }

            Core()->AccessControl()->addUserGroup($checkParams['userId'], $id, 1);

            $check = Core()->Events()->trigger('core_Service_Groups_addGroup', array($id, $url, $name, $adminemail, $description));

            if($check===false)
            {
                throw new Exception('Unknown error', 100);
            }

            Core()->Db()->commit();
        }
        catch(Exception $e)
        {
            Core()->Db()->rollBack();
            throw new Exception('Unknown error', 100);
        }

        return (int)$id;
    }

    public function editGroup($id, $name, $description='')
    {
        $group = $this->getGroupById($id);

        if($group===false)
        {
            throw new Exception('Access denied', 99);
        }

        $checkParams = $this->checkParams($name, $description);

        try
        {
            Core()->Db()->beginTransaction();

            Core()->Db()->query('
                UPDATE
                    `acl_groups`
                SET
                    `name` = ?,
                    `description` = ?
                WHERE
                    `id` = ?
            ', array(
                $name,
                $description,
                $id
            ));

            $check = Core()->Events()->trigger('core_Service_Groups_editGroup', array($id, $name, $description));

            if($check===false)
            {
                throw new Exception('Unknown error', 100);
            }

            Core()->Db()->commit();
        }
        catch(Exception $e)
        {
            Core()->Db()->rollBack();
            throw new Exception('Unknown error', 100);
        }
    }
    
    public function removeGroup($id)
    {
        $group = $this->getGroupById($id);

        if($group===false)
        {
            throw new Exception('Access denied', 99);
        }

        try
        {
            Core()->Db()->beginTransaction();
            
            $check = Core()->Events()->trigger('core_Service_Groups_removeGroup', array($id));

            if($check===false)
            {
                throw new Exception('Unknown error', 100);
            }
            
            /** @todo better move this to access management */
            Core()->Db()->query('
                DELETE FROM
                    `acl_users_groups`
                WHERE
                    `group_id` = ?
            ', array(
                $id
            ));

            Core()->Db()->query('
                DELETE FROM
                    `acl_groups`
                WHERE
                    `id` = ?
            ', array(
                $id
            ));

            Core()->Db()->commit();
        }
        catch(Exception $e)
        {
            Core()->Db()->rollBack();
            throw new Exception('Unknown error', 100);
        }
    }
    
    public function getAdminsOfGroup($id)
    {
        $admins = Core()->Db()->fetchAll('
            SELECT
                au.`id`, au.`username`, au.`email`
            FROM
                `acl_users_groups` aug
            INNER JOIN
                `acl_users` au
            ON
                au.`id` = aug.`user_id`
            WHERE
                aug.`group_id` = ? AND
                aug.`permissions_group_id` = 1
        ', array(
            $id
        ));
        
        $return = array();
        
        reset($admins);
        foreach($admins as $admin)
        {
            $return[$admin['id']] = $admin;
        }
        
        return $return;
    }
    
    public function addAdminToGroup($groupId, $username, $email)
    {
        $userData = $this->checkUserParams($email, $username);
        
        try
        {
            Core()->Db()->beginTransaction();
            Core()->AccessControl()->addUserGroup($userData['userId'], $groupId, 1);
            
            Core()->Db()->commit();
        }
        catch(Exception $e)
        {
            Core()->Db()->rollBack();
            throw new Exception('Unknown error', 100);
        }
    }
    
    public function removeAdminFromGroup($groupId, $userId)
    {
        /** @todo better move this to access management */
        Core()->Db()->query('
            DELETE FROM
                `acl_users_groups`
            WHERE
                `group_id` = ? AND
                `user_id` = ? AND
                `permissions_group_id` = 1
        ', array(
            $groupId,
            $userId
        ));
    }

    public function checkURL($url)
    {
        $check = preg_match('/^[0-9a-z-]+$/i', $url);

        if($check>0)
        {
            return true;
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

    private static function update1()
    {
        Core()->Events()->addListener('core_Service_Groups_addGroup');
        Core()->Events()->addListener('core_Service_Groups_editGroup');
        Core()->Events()->addListener('core_Service_Groups_removeGroup');
        return true;
    }
}
