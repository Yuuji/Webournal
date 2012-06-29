{if !isset($type)}{assign var="type" value="file"}{/if}
<tr>
    <td>
        <table class="file">
            <tr>
                <th colspan="2" class="filename">{$file.name|escape:"htmlall"}{if $file.number} (Dokumentennummer: {$file.number|escape:"htmlall"}){/if}</th>
            </tr>
            <tr>
                <td class="col1">
                    <table class="description">
                        <tr>
                            <td class="label">Beschreibung:</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="value" colspan="2">{$file.description|escape:"htmlall"}</td>
                        </tr>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="label">Erstellt am:</td>
                            <td class="value">{$file.created|escape:"htmlall"}</td>
                        </tr>
                        <tr>
                            <td class="label">Letzte &Auml;ndernung:</td>
                            <td class="value">{$file.updated|escape:"htmlall"}</td>
                        </tr>
                    </table>
                </td>
                <td class="col2">
                    <table>
                        {if $type!='attachment'}
                        <tr>
                            <td class="label">Anh&auml;nge:</td>
                            <td>{assign var="showLink" value=false}{if count($file.attachments)>0 || $Core->checkAccessRights('webournal', 'view', 'addattachment')}{assign var="showLink" value=true}{/if}{if $showLink}<a href="{$Core->url('viewattachments', 'view', 'webournal', ['id' => $file.id, 'directory' => $directory_id])}">{/if}{if count($file.attachments)==0}Keine{else}{$file.attachments|@count} Dateien{/if}{if $showLink}</a>{/if}</td>
                        </tr>
                        {/if}
                        <tr>
                            <td>Webournal:</td>
                            <td>
                                {if $type=='file'}
                                    <a href="{$Core->url('view', 'view', 'webournal', ['id' => $file.id])}" target="_blank">anzeigen / bearbeiten</a></td>
                                {elseif $type=='attachment'}
                                    <a href="{$Core->url('view', 'view', 'webournal', ['id' => $attachedtofile.id, 'attachment' => $file.id])}" target="_blank">anzeigen / bearbeiten</a></td>
                                {/if}
                        </tr>
                        <tr>
                            <td>PDF:</td>
                            <td><a href="{$file.url}" target="_blank">anzeigen / downloaden</a>{* - <a href="{$file.url}" type="application/octet-stream">downloaden</a> *}</td>
                        </tr>
                        <tr>
                            <td>XOJ:</td>
                            <td><a href="{$Core->url('viewxoj', 'view', 'webournal', ['id' => $file.id, 'type' => 'download'])}" target="_blank">downloaden</a></td>
                        </tr>
                        {if $type=='file'}
                            {assign var="checkEdit" value=$Core->checkAccessRights('webournal', 'view', 'editfile')}
                            {assign var="checkDelete" value=$Core->checkAccessRights('webournal', 'view', 'deletefile')}
                        {elseif $type=='attachment'}
                            {assign var="checkEdit" value=$Core->checkAccessRights('webournal', 'view', 'editattachment')}
                            {assign var="checkDelete" value=$Core->checkAccessRights('webournal', 'view', 'deleteattachment')}
                        {else}
                            {assign var="checkEdit" value=false}
                            {assign var="checkDelete" value=false} 
                        {/if}
                        {if $checkEdit || $checkDelete}
                            <tr>
                                <td>Datei:</td>
                                <td>
                                    {if $type=='file'}
                                        {if $checkEdit}<a href="{$Core->url('editfile', 'view', 'webournal', ['directory' => $directory_id, 'id' => $file.id])}">&Auml;ndern</a>{/if}
                                        {if $checkDelete}<a href="{$Core->url('removefile', 'view', 'webournal', ['directory' => $directory_id, 'id' => $file.id])}">L&ouml;schen</a>{/if}
                                    {elseif $type=='attachment'}
                                        {if $checkEdit}<a href="{$Core->url('editattachment', 'view', 'webournal', ['directory' => $directory_id, 'id' => $attachedtofile.id, 'attachment' => $file.id])}">&Auml;ndern</a>{/if}
                                        {if $checkDelete}<a href="{$Core->url('removeattachment', 'view', 'webournal', ['directory' => $directory_id, 'id' => $attachedtofile.id, 'attachment' => $file.id])}">L&ouml;schen</a>{/if}
                                    {/if}
                                </td>
                            </tr>
                        {/if}
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>