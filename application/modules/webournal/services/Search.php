<?php

class webournal_Service_Search extends Core_View_Paginator
{
    private function escapeSphinxQL($string)
    {
        $from = array ( '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', "'", "\x00", "\n", "\r", "\x1a" );
        $to   = array ( '\\\\', '\\\(','\\\)','\\\|','\\\-','\\\!','\\\@','\\\~','\\\"', '\\\&', '\\\/', '\\\^', '\\\$', '\\\=', "\\'", "\\x00", "\\n", "\\r", "\\x1a" );
        return str_replace ( $from, $to, $string );
    }

    private function getSelect($search, $group_id=null)
    {
        if(is_null($group_id))
        {
            $group_id = Core()->getGroupId();
        }

        $select = Core()->Db()->select()->from(array('ws' => 'webournal_search'));
        $select->joinInner(array('wf' => 'webournal_files'), 'wf.id=ws.id', '');

        if($group_id!==false && $group_id>0)
        {
            $select->where('ws.groupid = ?', $group_id);
        }
        $select->where('ws.query = ?', $this->escapeSphinxQL($search));

        return $select;
    }

    public function getFiles($search, $group_id=null)
    {
        return $this->getSelect($search, $group_id);
    }

    public function getFilesAllowedSort()
    {
        return array(
            'weight'    => ''
        );
    }

    public function getFilesDefaultSort()
    {
        return 'weight';
    }
}
