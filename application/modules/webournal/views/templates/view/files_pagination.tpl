{function filespagination params=[] files=[]}
<h2>Dateien</h2>
<table class="pagination">
{foreach $files as $fileid}
    {assign var="file" value=webournal_Service_Files::getFileById($fileid['id'])}
    {include file="../view/view_file.tpl"}
{foreachelse}
        <tr>
            <td>Keine Dateien vorhanden!</td>
        </tr>
{/foreach}
    <tr class="paginationcontrol">
        <td>
            {$this->paginationControlParams($files, $params)}
        </td>
    </tr>
</table>
{/function}