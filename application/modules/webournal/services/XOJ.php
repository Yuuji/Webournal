<?php
class webournal_Service_XOJ
{
    const VERSION = 2;
    
    /**
     *
     * @var webournal_Service_Files
     */
    private static $_files = null;
    
    public function __construct($files)
    {
        self::$_files = $files;
    }
    
    /**
     *
     * @return webournal_Service_Files
     */
    private static function Files()
    {
        if(is_null(self::$_files))
        {
            self::$_files = new webournal_Service_Files(new webournal_Service_Directories());
        }
        
        return self::$_files;
    }
    
    public static function getXOJObject($fileId, $userId = false, $groupId=null)
    {
        return self::getXOJObjectIntern($fileId, 'file', $userId, $groupId);
    }

    public static function getAttachmentXOJObject($fileId, $userId = false, $groupId=null)
    {
        return self::getXOJObjectIntern($fileId, 'attachment', $userId, $groupId);
    }

    private static function getXOJObjectIntern($fileId, $type='file', $userId = false, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }

        switch($type)
        {
            case 'attachment':
                $file = self::Files()->getFileAttachmentById($fileId, $groupId);
                break;
            case 'file':
            default:
                $file = self::Files()->getFileById($fileId, $groupId);
                break;
        }
        
        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        $xoj = new stdClass();
        
        $xoj->version = '0.4.5';
        $xoj->title = $file['name'];
        
        $pages = self::getPagesIntern($fileId);
        
        $xoj->pages = array();
        reset($pages);
        foreach($pages as $page)
        {
            $pageObj = new stdClass();
            
            $pageObj->width = $page['width'];
            $pageObj->height = $page['height'];
            
            $background = self::getBackgroundIntern($page['id']);
            
            if(isset($background['type']))
            {
                $backgroundObj = new stdClass();
                foreach(array('type', 'color', 'style', 'pageno') as $key)
                {
                    $backgroundObj->$key = $background[$key];
                }
                
                if(!is_null($background['fileid']))
                {
                    switch($type)
                    {
                        case 'attachment':
                            $file = self::Files()->getFileAttachmentById($background['fileid'], $groupId);
                            break;
                        case 'file':
                        default:
                            $file = self::Files()->getFileById($background['fileid'], $groupId);
                            break;
                    }
                    $backgroundObj->filename = $file['url'];
                    $backgroundObj->domain='absolute';
                }
                
                $pageObj->background = $backgroundObj;
            }
            
            $layers = self::getLayersIntern($page['id']);
            
            $pageObj->layer = array();
            
            $editLayer = false;
            
            if(is_array($layers))
            {
                $layerId=0;
                foreach($layers as $layer)
                {
                    $layerObj = new stdClass();
                    // user_id just for internal
                    foreach(array('name', 'time') as $key)
                    {
                        $layerObj->$key = $layer[$key];
                    }
                    
                    if($userId!==false && $layer['user_id']==$userId)
                    {
                        $editLayer = $layerId;
                    }
                    
                    $layerObj->strokes = array();
                    
                    $strokes = self::getStrokesIntern($layer['id']);
                    
                    if(is_array($strokes))
                    {
                        foreach($strokes as $stroke)
                        {
                            $strokeObj = new stdClass();
                            // user_id just for internal
                            foreach(array('tool', 'color', 'width', 'value') as $key)
                            {
                                $strokeObj->$key = $stroke[$key];
                            }
                            
                            $layerObj->strokes[] = $strokeObj;
                        }
                    }
                    
                    $layerObj->texts = array();
                    
                    $texts = self::getTextsIntern($layer['id']);
                    
                    if(is_array($texts))
                    {
                        foreach($texts as $text)
                        {
                            $textObj = new stdClass();
                            // user_id just for internal
                            foreach(array('font', 'size', 'x', 'y', 'color', 'value') as $key)
                            {
                                $textObj->$key = $text[$key];
                            }
                            
                            $layerObj->texts[] = $textObj;
                        }
                    }
                    
                    $pageObj->layer[$layerId] = $layerObj;
                    $layerId++;
                }
                
                if($editLayer!==false)
                {
                    $pageObj->editLayer = $editLayer;
                }
            }
            
            $xoj->pages[] = $pageObj;
        }
        
        return $xoj;
    }
    
    public static function getPages($fileId, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        $file = self::Files()->getFileById($fileId, $groupId);
        
        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        return self::getPagesIntern($fileId);
    }
    
    private static function getPagesIntern($fileId)
    {
        $pages = Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `webournal_xoj_pages`
            WHERE
                `file_id` = ?
            ORDER BY
                `pageno` ASC
        ', array(
            $fileId
        ));
        
        if(!is_array($pages))
        {
            $pages = array();
        }
        
        return $pages;
    }
    
    private static function getPageIdIntern($fileId, $pageno)
    {
        return Core()->Db()->fetchOne('
            SELECT
                `id`
            FROM
                `webournal_xoj_pages`
            WHERE
                `file_id` = ? AND
                `pageno`= ?
        ', array(
            $fileId,
            $pageno
        ));
    }
    
    private static function getBackgroundIntern($pageId)
    {
        $background = Core()->Db()->fetchRow('
            SELECT
                *
            FROM
                `webournal_xoj_backgrounds`
            WHERE
                `page_id` = ?
        ', array(
            $pageId
        ));
        
        if(!is_array($background))
        {
            $background = array(
                
            );
        }
        
        return $background;
    }
    
    private static function addPageIntern($fileId, $pageno, $width, $height, $groupId)
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
        
        $check = Core()->Db()->insert('webournal_xoj_pages', array(
            'file_id' => $fileId,
            'pageno' => $pageno,
            'width' => $width,
            'height' => $height
        ));
        
        if($check===false || $check!==1)
        {
            if($doTransaction)
            {
                Core()->Db()->rollBack();
            }
            throw new Exception('Could not add page', 41);
        }
        
        $pageId = Core()->Db()->lastInsertId('webournal_xoj_pages');
        
        if($pageId===false || !is_numeric($pageId))
        {
            if($doTransaction)
            {
                Core()->Db()->rollBack();
            }
            throw new Exception('Could not add page', 41);
        }
        
        $check = Core()->Events()->trigger('webournal_Service_XOJ_newPage', array($fileId, $pageno, $width, $height, $groupId));
        
        if($check===false)
        {
            if($doTransaction)
            {
                Core()->Db()->rollBack();
            }
            throw new Exception('Could not add page', 41);
        }
        
        if($doTransaction)
        {
            Core()->Db()->commit();
        }
        
        return (int)$pageId;
    }
    
    private static function setSolidBackgroundIntern($pageId, $color, $style)
    {
        return self::setBackgroundIntern($pageId, 'pdf', $color, $style);
    }
    
    private static function setPixmapBackgroundIntern($pageId, $fileid)
    {
        return self::setBackgroundIntern($pageId, 'pdf', null, null, $fileid);
    }
    
    private static function setPDFBackgroundIntern($pageId, $fileid, $pageno)
    {
        return self::setBackgroundIntern($pageId, 'pdf', null, null, $fileid, $pageno);
    }
    
    private static function setBackgroundIntern($pageId, $type, $color=null, $style=null, $fileid=null, $pageno=null)
    {
        $type = strtolower($type);
        
        $sql = 'REPLACE INTO
                `webournal_xoj_backgrounds`
            SET
                `page_id` = ?,
                `type` = ?,
        ';
        
        $params = array(
            $pageId,
            $type
        );
        
        switch($type)
        {
            case 'solid':
                $sql .= '
                    `color` = ?,
                    `style` = ?,
                    `fileid` = NULL,
                    `pageno` = NULL
                ';
                $params[] = $color;
                $params[] = $style;
                break;
            case 'pixmap':
                $sql .= '
                    `color` = NULL,
                    `style` = NULL,
                    `fileid` = ?,
                    `pageno` = NULL
                ';
                $params[] = $fileid;
                break;
            case 'pdf':
                $sql .= '
                    `color` = NULL,
                    `style` = NULL,
                    `fileid` = ?,
                    `pageno` = ?
                ';
                $params[] = $fileid;
                $params[] = $pageno;
                break;
        }
        
        Core()->Db()->query($sql, $params);
    }
    
    private static function removeBackgroundIntern($pageId)
    {
        Core()->Db()->query('
            DELETE FROM
                `webournal_xoj_backgrounds`
            WHERE
                `page_id` = ?
        ', array(
            $pageId
        ));
    }
    
    private static function removePageIntern($fileId, $pageno, $groupId)
    {
        $pageId = self::getPageIdIntern($fileId, $pageno);
        $check = Core()->Events()->trigger('webournal_Service_XOJ_removePage', array($fileId, $pageno, $pageId, $groupId));
        
        if($check===false)
        {
            throw new Exception('Could not remove page', 51);
        }
        Core()->Db()->query('
            DELETE FROM
                `webournal_xoj_pages`
            WHERE
                `file_id` = ? AND
                `pageno` = ?
        ', array(
            $fileId,
            $pageno
        ));
    }
    
    private static function getLayersIntern($pageId)
    {
        return Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `webournal_xoj_layers`
            WHERE
                `page_id` = ?
        ', array(
            $pageId
        ));
    }
    
    public static function getLayerIdByUserId($fileId, $page, $userId=null, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        if(is_null($userId))
        {
            $userId = Core()->getUserId();
        }
        
        $file = self::Files()->getFileById($fileId, $groupId);
        
        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        $pageId = self::getPageIdIntern($fileId, $page);
        
        if($pageId===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        return self::getLayerIdByUserIdIntern($pageId, $userId);
    }
    
    private static function getLayerIdByUserIdIntern($pageId, $userId)
    {
        return Core()->Db()->fetchOne('
            SELECT
                `id`
            FROM
                `webournal_xoj_layers`
            WHERE
                `page_id` = ? AND
                `user_id` = ?
        ', array(
            $pageId,
            $userId
        ));
    }
    
    private static function addLayerIntern($pageId, $userId)
    {
        Core()->Db()->query('
            INSERT INTO
                `webournal_xoj_layers`
            SET
                `page_id` = ?,
                `user_id` = ?,
                `time` = NOW()
        ', array(
            $pageId,
            $userId
        ));
        
        $id = Core()->Db()->lastInsertId('webournal_xoj_layers');
        
        if($id===false)
        {
            throw new Exception('Unknown error', 100);
        }
        
        return $id;
    }
    
    private static function setLayerIntern($layerId, $name=null)
    {
        if(is_null($name))
        {
            Core()->Db()->query('
                UPDATE
                    `webournal_xoj_layers`
                SET
                    `name` = NULL,
                    `time` = NOW()
                WHERE
                    `id` = ?
            ', array(
                $layerId
            ));
        }
        else
        {
            Core()->Db()->query('
                UPDATE
                    `webournal_xoj_layers`
                SET
                    `name` = ?,
                    `time` = NOW()
                WHERE
                    `id` = ?
            ', array(
                $name,
                $layerId
            ));
        }
    }
    
    private static function getStrokesIntern($layerId)
    {
        return Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `webournal_xoj_strokes`
            WHERE
                `layer_id` = ?
        ', array(
            $layerId
        ));
    }
    
    private static function clearStrokesIntern($layerId)
    {
        Core()->Db()->query('
            DELETE FROM
                `webournal_xoj_strokes`
            WHERE
                `layer_id` = ?
        ', array(
            $layerId
        ));
    }
    
    private static function addStrokeIntern($layerId, $tool, $color, $width, $value)
    {
        Core()->Db()->query('
            INSERT INTO
                `webournal_xoj_strokes`
            SET
                `layer_id` = ?,
                `tool` = ?,
                `color` = ?,
                `width` = ?,
                `value` = ?
        ', array(
            $layerId,
            $tool,
            $color,
            $width,
            $value
        ));
        
        $id = Core()->Db()->lastInsertId('webournal_xoj_strokes');
        
        if($id===false)
        {
            throw new Exception('Unknown error', 100);
        }
        
        return $id;
    }
    
    private static function clearTextsIntern($layerId)
    {
        Core()->Db()->query('
            DELETE FROM
                `webournal_xoj_texts`
            WHERE
                `layer_id` = ?
        ', array(
            $layerId
        ));
    }
    
    private static function getTextsIntern($layerId)
    {
        return Core()->Db()->fetchAll('
            SELECT
                *
            FROM
                `webournal_xoj_texts`
            WHERE
                `layer_id` = ?
        ', array(
            $layerId
        ));
    }
    
    private static function addTextIntern($layerId, $font, $size, $x, $y, $color, $value)
    {
        Core()->Db()->query('
            INSERT INTO
                `webournal_xoj_texts`
            SET
                `layer_id` = ?,
                `font` = ?,
                `size` = ?,
                `x` = ?,
                `y` = ?,
                `color` = ?,
                `value` = ?
        ', array(
            $layerId,
            $font,
            $size,
            $x,
            $y,
            $color,
            $value
        ));
        
        $id = Core()->Db()->lastInsertId('webournal_xoj_texts');
        
        if($id===false)
        {
            throw new Exception('Unknown error', 100);
        }
        
        return $id;
    }
    
    public static function setLayers($fileId, $data, $userId=null, $groupId=null)
    {
        return self::setLayersIntern($fileId, $data, 'file', $userId, $groupId);
    }

    public static function setAttachmentLayers($fileId, $data, $userId=null, $groupId=null)
    {
        return self::setLayersIntern($fileId, $data, 'attachment', $userId, $groupId);
    }

    private static function setLayersIntern($fileId, $data, $type='file', $userId=null, $groupId=null)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        if(is_null($userId))
        {
            $userId = Core()->getUserId();
        }

        switch($type)
        {
            case 'attachment':
                $file = self::Files()->getFileAttachmentById($fileId, $groupId);
                break;
            case 'file':
            default:
                $file = self::Files()->getFileById($fileId, $groupId);
                break;
        }
        
        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        if(!is_object($data))
        {
            throw new Exception('Data not set', 60);
        }
        
        if(!isset($data->displayname) || !isset($data->pages))
        {
            throw new Exception('Data not correct', 61);
        }
        
        if(empty($data->displayname))
        {
            throw new Exception('Data not correct! Displayname not set!', 62);
        }
        
        try
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
            
            foreach($data->pages as $pagenum => $pageData)
            {
                $pageId = self::getPageIdIntern($fileId, $pagenum);

                if($pageId===false)
                {
                    throw new Exception('Data not correct', 61);
                }
                
                $layerId = self::getLayerIdByUserIdIntern($pageId, $userId);

                if($layerId===false)
                {
                    $layerId = self::addLayerIntern($pageId, $userId);
                }
                
                self::setLayerIntern($layerId, $data->displayname);
                
                self::clearStrokesIntern($layerId);
                
                if(isset($pageData->strokes) && is_array($pageData->strokes))
                {
                    foreach($pageData->strokes as $stroke)
                    {
                        if(!isset($stroke->tool) || !isset($stroke->color) || !isset($stroke->width) || !isset($stroke->value))
                        {
                            throw new Exception('Stroke data not correct', 63);
                        }
                        
                        if(!in_array($stroke->tool, array('pen', 'highlighter', 'eraser')))
                        {
                            throw new Exception('Stroke data not correct', 63);
                        }

                        self::addStrokeIntern($layerId, $stroke->tool, $stroke->color, $stroke->width, $stroke->value);
                    }
                }
                
                self::clearTextsIntern($layerId);
                
                if(isset($pageData->texts) && is_array($pageData->texts))
                {
                    foreach($pageData->texts as $text)
                    {
                        if(!isset($text->font) || !isset($text->size) || !isset($text->x) || !isset($text->y) || !isset($text->color) || !isset($text->value))
                        {
                            throw new Exception('Text data not correct', 63);
                        }
                        
                        self::addTextIntern($layerId, $text->font, $text->size, $text->x, $text->y, $text->color, $text->value);
                    }
                }
                    
                if($doTransaction)
                {
                    Core()->Db()->commit();
                }
            }
        }
        catch(Exception $e)
        {
            if($doTransaction)
            {
                Core()->Db()->rollBack();
            }
            switch($e->getCode())
            {
                case 61:
                case 62:
                case 63:
                case 99:
                    throw $e;
                    break;
                default:
                    throw new Exception('Unknown error', 100);
                    break;
            }
            
        }
    }

    public static function newFileAttachmentEvent($fileId, $attachedToFileId, $filename, $groupId)
    {
        return self::newFileEventIntern($fileId, $filename, $groupId, 'attachment');
    }
    
    public static function newFileEvent($fileId, $filename, $groupId)
    {
        return self::newFileEventIntern($fileId, $filename, $groupId, 'file');
    }

    private static function newFileEventIntern($fileId, $filename, $groupId, $type='file')
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }

        switch($type)
        {
            case 'attachment':
                $file = self::Files()->getFileAttachmentById($fileId, $groupId);
                break;
            case 'file':
            default:
                $file = self::Files()->getFileById($fileId, $groupId);
                break;
        }

        if($file===false)
        {
            throw new Exception('Access denied', 99);
        }
        
        try
        {
            $pdf = new Core_Service_PDF($filename);
            $pages = $pdf->getPageCount();
            
            for($i = 1; $i <= $pages; $i++)
            {
                $page = $pdf->getPage($i);
                
                $width = $page->getWidth();
                $height = $page->getHeight();
                
                $ref = $page->getPageDictionary();
                if($ref && $ref->Rotate && ($ref->Rotate->value==90 || $ref->Rotate->value==270))
                {
                    $tmp = $width;
                    $width = $height;
                    $height = $tmp;
                }
                $pageId = self::addPageIntern($fileId, $i, $width, $height, $groupId);
                self::setPDFBackgroundIntern($pageId, $fileId, $i);
                
                unset($page);
            }
        }
        catch(Exception $e)
        {
            return false;
        }
        
        return true;
    }
    
    public static function removeFileEvent($fileId, $filename, $groupId)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
     
        try
        {
            $pages = self::getPagesIntern($fileId);
            $pages = array_reverse($pages);
            reset($pages);
            foreach($pages as $page)
            {
                self::removePageIntern($fileId, $page['pageno'], $groupId);
            }
        }
        catch(Exception $e)
        {
            return false;
        }
        return true;
    }
    
    public static function removePageEvent($fileId, $pageno, $pageId, $groupId)
    {
        if(is_null($groupId))
        {
            $groupId = Core()->getGroupId();
        }
        
        try
        {
            self::removeBackgroundIntern($pageId);
        }
        catch(Exception $e)
        {
            return false;
        }
        
        return true;
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
                    'webournal_Service_Files_newAttachment',
                    'webournal_Service_XOJ',
                    'newFileAttachmentEvent'
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
            CREATE TABLE IF NOT EXISTS `webournal_xoj_pages` (
              `id` int(11) NOT NULL auto_increment,
              `file_id` int(11) NOT NULL,
              `pageno` int(11) NOT NULL,
              `width` float(8,2) NOT NULL,
              `height` float(8,2) NOT NULL,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `file_id` (`file_id`,`pageno`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_xoj_backgrounds` (
              `page_id` int(11) NOT NULL,
              `type` enum("solid","pixmap", "pdf") NOT NULL,
              `color` varchar(20) NULL,
              `style` enum("plain","lined", "ruled", "graph") NULL,
              `fileid` int(11) NULL,
              `pageno` int(11) NULL,
              PRIMARY KEY  (`page_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_xoj_layers` (
              `id` int(11) NOT NULL auto_increment,
              `page_id` int(11) NOT NULL,
              `user_id` int(11) NULL,
              `name` varchar(100) NULL,
              `time` timestamp NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_xoj_strokes` (
              `id` int(11) NOT NULL auto_increment,
              `layer_id` int(11) NOT NULL,
              `tool` enum("pen", "highlighter", "eraser") NOT NULL,
              `color` varchar(20) NOT NULL,
              `width` float(8,2) NOT NULL,
              `value` text NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_xoj_texts` (
              `id` int(11) NOT NULL auto_increment,
              `layer_id` int(11) NOT NULL,
              `font` varchar(50) NOT NULL,
              `size` float(8,2) NOT NULL,
              `x` float(8,2) NOT NULL,
              `y` float(8,2) NOT NULL,
              `color` varchar(20) NOT NULL,
              `value` text NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
        
        Core()->Events()->addListener('webournal_Service_XOJ_newPage');
        Core()->Events()->addListener('webournal_Service_XOJ_removePage');
        
        Core()->Events()->addListener('webournal_Service_XOJ_newBackground');
        Core()->Events()->addListener('webournal_Service_XOJ_removeBackground');
        
        Core()->Events()->addListener('webournal_Service_XOJ_newLayer');
        Core()->Events()->addListener('webournal_Service_XOJ_removeLayer');
        
        Core()->Events()->addListener('webournal_Service_XOJ_newStroke');
        Core()->Events()->addListener('webournal_Service_XOJ_removeStroke');
        
        Core()->Events()->addListener('webournal_Service_XOJ_newText');
        Core()->Events()->addListener('webournal_Service_XOJ_removeText');
        
        Core()->Events()->addSubscription(
                'webournal_Service_Files_newFile',
                'webournal_Service_XOJ',
                'newFileEvent'
        );
        
        Core()->Events()->addSubscription(
                'webournal_Service_Files_removeFile',
                'webournal_Service_XOJ',
                'removeFileEvent'
        );
        
        Core()->Events()->addSubscription(
                'webournal_Service_XOJ_removePage',
                'webournal_Service_XOJ',
                'removePageEvent'
        );
        
        Core()->Events()->addSubscription(
                'webournal_Service_XOJ_removeLayer',
                'webournal_Service_XOJ',
                'removeLayerEvent'
        );

        return true;
    }
}
