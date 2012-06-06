<?php

class Core_Helper_Events
{
    public function addListener($name)
    {
        $check = $this->getListener($name);
        
        if($check!==false)
        {
            $id = $check['id'];
        }
        else
        {
            Core()->Db()->query('
                REPLACE INTO
                    `events`
                SET
                    `name`   = ?
            ', array(
                $name
            ));

            $id = Core()->Db()->lastInsertId('events');

            if($id===false)
            {
                throw new Exception('Could not add event', 20);
            }
        }
        
        return $id;
    }
    
    public function getListener($name)
    {
        return Core()->Db()->fetchRow('
            SELECT
                *
            FROM
                `events`
            WHERE
                `name` = ?
        ', array(
            $name
        ));
    }
    
    public function addSubscription($event, $class, $method, $priority=1)
    {
        if(!is_numeric($event))
        {
            $event = $this->getListener($event);
            
            if($event===false)
            {
                throw new Exception('Event not found', 21);
            }
            
            $event = $event['id'];
        }
        
        $check = $this->getSubscription($event, $class, $method);
        
        $id = false;
        if($check!==false)
        {
            $id = $check['id'];
            if($check['priority']!=$priority)
            {
                Core()->Db()->query('
                    UPDATE
                        `events_subscriptions`
                    SET
                        `priority` = ?
                    WHERE
                        `event_id` = ?,
                        `class` = ?,
                        `method` = ?
                ', array(
                    $priority,
                    $event,
                    $class,
                    $method
                ));
            }
        }
        else
        {
            Core()->Db()->query('
                INSERT INTO
                    `events_subscriptions`
                SET
                    `event_id` = ?,
                    `class` = ?,
                    `method` = ?,
                    `priority` = ?
            ', array(
                $event,
                $class,
                $method,
                $priority
            ));
            
            $id = Core()->Db()->lastInsertId('events_subscriptions');
            
            if($id===false)
            {
                throw new Exception('Could not add event subscription', 20);
            }
        }
        
        return $id;
    }
    
    public function getSubscription($event, $class, $method)
    {
        if(!is_numeric($event))
        {
            $event = $this->getListener($event);
            
            if($event===false)
            {
                throw new Exception('Event not found', 21);
            }
            $event = $event['id'];
        }
        
        return Core()->Db()->fetchRow('
            SELECT
                *
            FROM
                `events_subscriptions`
            WHERE
                `event_id` = ? AND
                `class` = ? AND
                `method` = ?
        ', array(
            $event,
            $class,
            $method
        ));
    }
    
    public function getSubscriptionsByEvent($event)
    {
        if(!is_numeric($event))
        {
            $event = $this->getListener($event);
            if($event===false)
            {
                throw new Exception('Event not found', 21);
            }
            $event = $event['id'];
        }
        
        return Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `events_subscriptions`
            WHERE
                `event_id` = ?
            ORDER BY
                `priority` DESC
        ', array(
            $event
        ));
    }
    
    public function trigger($event, $arguments=null)
    {
        try
        {
            $subs = $this->getSubscriptionsByEvent($event);
            
            if(!is_array($subs))
            {
                return false;
            }
            
            foreach($subs as $sub)
            {
                if(method_exists($sub['class'], $sub['method'])===false)
                {
                    var_dump($sub);
                    return false;
                }
                
                $check = call_user_func_array(array($sub['class'], $sub['method']), $arguments);
                
                if($check===false)
                {
                    return false;
                }
            }
            
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }
}