<h2>Anh&auml;nge</h2>
<table>
    {foreach $attachedtofile.attachments as $file}
        {include file="../view/view_file.tpl" type="attachment"}
    {foreachelse}
        <tr>
            <td>Keine Anh&auml;nge vorhanden!</td>
        </tr>
    {/foreach}
</table>