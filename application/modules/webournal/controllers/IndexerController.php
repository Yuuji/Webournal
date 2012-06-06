<?php

class webournal_IndexerController extends Zend_Controller_Action
{
    const VERSION = 2;
    
    public function cmd_exec($cmd, &$stdout, &$stderr)
    {
        $outfile = tempnam(".", "cmd");
        $errfile = tempnam(".", "cmd");
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("file", $outfile, "w"),
            2 => array("file", $errfile, "w")
        );
        $proc = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($proc)) return 255;

        fclose($pipes[0]);    //Don't really want to give any input

        $exit = proc_close($proc);
        $stdout = file_get_contents($outfile);
        $stderr = file_get_contents($errfile);

        unlink($outfile);
        unlink($errfile);
        return $exit;
    }
    
    public function indexAction()
    {
        if(!defined('IS_CLI'))
        {
            return Core()->redirectMain('index', 'index');
        }
        
        $xmlwriter = new xmlWriter;
        $xmlwriter->openMemory();
        $xmlwriter->setIndent(true);
        $xmlwriter->startDocument('1.0', 'UTF-8');
        $xmlwriter->startElement('sphinx:docset');

        $xmlwriter->startElement('sphinx:schema');

        $xmlwriter->startElement('sphinx:attr');
        $xmlwriter->writeAttribute("name", "groupid");
        $xmlwriter->endElement();

        $xmlwriter->startElement('sphinx:field');
        $xmlwriter->writeAttribute("name", "content");
        $xmlwriter->endElement();

        $xmlwriter->endElement();
        
        echo $xmlwriter->flush();
        
        if(!isset($_SERVER['argv']))
        {
            $modus = '-all';
        }
        else
        {
            if(in_array('--all', $_SERVER['argv']))
            {
                $modus = '--all';
            }
            else if(in_array('--new', $_SERVER['argv']))
            {
                $modus = '--new';
            }
        }
        
        switch($modus)
        {
            case '--new':
                $items = Core()->Db()->query('
                    SELECT
                        wf.`id`, wd.`group_id`
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
                    LEFT OUTER JOIN
                        `webournal_indexer_files` wif
                    ON
                        wif.`file_id` = wf.`id`
                    WHERE
                        wif.`last_update` IS NULL OR
                        wif.`last_update` < wf.`updated`
                ');
                break;
            case '--all':
            default:
                $items = Core()->Db()->query('
                    SELECT
                        wf.`id`, wd.`group_id`
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
                ');
                break;
        }
        
        while($item = $items->fetch())
        {
            $file = webournal_Service_Files::getFileById($item['id'], $item['group_id']);
            
            if(!is_array($file) || !isset($file['filename']) || empty($file['filename']))
            {
                /**
                 * @todo delete on --new?
                 */
                continue;
            }
            
            $xmlwriter->startElement('sphinx:document');
            $xmlwriter->writeAttribute("id", $file['id']);
            
            $xmlwriter->startElement('groupid');
            $xmlwriter->text($file['group_id']);
            $xmlwriter->endElement();

            $xmlwriter->startElement('content');
            $content = '';
            $error = '';
            $this->cmd_exec('/usr/bin/pdftotext -nopgbrk -enc "UTF-8" "' . $file['filename'] . '" - ', $content, $error);
            $xmlwriter->text($content);
            $xmlwriter->endElement();

            $xmlwriter->endElement();
            echo $xmlwriter->flush();
            
            Core()->Db()->query('
                REPLACE INTO
                    `webournal_indexer_files`
                SET
                    `file_id` = ?,
                    `last_update` = ?
            ', array(
                $file['id'],
                $file['updated']
            ));
        }
        
        if($modus==='--new')
        {
            $items = Core()->Db()->query('
                SELECT
                    wif.`file_id`
                FROM
                    `webournal_indexer_files` wif
                LEFT OUTER JOIN
                    `webournal_files` wf
                ON
                    wif.`file_id` = wf.`id`
                WHERE
                    wf.`id` IS NULL
            ');

            if($items->rowCount()>0)
            {
                $xmlwriter->startElement('sphinx:killlist');
                while($item = $items->fetch())
                {
                    $xmlwriter->startElement('id');
                    $xmlwriter->text($item['file_id']);
                    $xmlwriter->endElement();
                }
                $xmlwriter->endElement();
            }
        }
        
        $xmlwriter->endElement();
        
        echo $xmlwriter->flush();
        
        die();
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
        Core()->Db()->query('
            CREATE TABLE IF NOT EXISTS `webournal_indexer_files` (
                `file_id` int(11) NOT NULL,
                `last_update` timestamp NOT NULL,
                PRIMARY KEY (`file_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ');
        return true;
    }

    private static function update1()
    {
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_indexer_index');
        return true;
    }
}