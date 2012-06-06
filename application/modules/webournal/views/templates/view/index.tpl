{if count($directories)> 0 || is_null($directory_id)}
<h2>Ordner</h2>
<table>
    <tr>
        <th>Name</th>
        <th>Typ</th>
        <th>Zeitpunkt (bei Typ "Termin")</th>
        <th>Beschreibung</th>
        <th>Erstellt am</th>
    </tr>
    {foreach $directories as $cdirectory}
        <tr>
            <td><a href="{$Webournal->url('index', 'view', 'webournal', ['id' => $cdirectory.id])}">{$cdirectory.name|escape:"htmlall"}</a></td>
            <td>{if $cdirectory.type=="date"}Termin{else}Ordner{/if}</td>
            <td>{if $cdirectory.type=="date"}{$cdirectory.directory_time|date_format}{else}&nbsp;{/if}</td>
            <td>{$cdirectory.description|escape:"htmlall"}</td>
            <td>{$cdirectory.created|date_format}</td>
        </tr>
    {foreachelse}
        <tr>
            <td colspan="5">Noch keine Ordner vorhanden!</td>
        </tr>
    {/foreach}
</table>
{/if}
{if !is_null($directory_id)}
<h2>Dateien</h2>
<table>
    {foreach $files as $file}
        {include file="../view/view_file.tpl"}
    {foreachelse}
        <tr>
            <td>Keine Dateien vorhanden!</td>
        </tr>
    {/foreach}
</table>
{/if}