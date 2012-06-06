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
                        <tr>
                            <td>Webournal:</td>
                            <td><a href="{$Webournal->url('view', 'view', 'webournal', ['id' => $file.id])}" target="_blank">anzeigen / bearbeiten</a></td>
                        </tr>
                        <tr>
                            <td>PDF:</td>
                            <td><a href="{$file.url}" target="_blank">anzeigen / downloaden</a>{* - <a href="{$file.url}" type="application/octet-stream">downloaden</a> *}</td>
                        </tr>
                        <tr>
                            <td>XOJ:</td>
                            <td><a href="{$Webournal->url('viewxoj', 'view', 'webournal', ['id' => $file.id, 'type' => 'download'])}" target="_blank">downloaden</a></td>
                        </tr>
                        {assign var="checkEdit" value=$Webournal->checkAccessRights('webournal', 'view', 'editfile')}
                        {assign var="checkDelete" value=$Webournal->checkAccessRights('webournal', 'view', 'deletefile')}
                        {if $checkEdit || $checkDelete}
                            <tr>
                                <td>Datei:</td>
                                <td>
                                    {if $checkEdit}<a href="{$Webournal->url('editfile', 'view', 'webournal', ['directory' => $directory_id, 'id' => $file.id])}">&Auml;ndern</a>{/if}
                                    {if $checkDelete}<a href="{$Webournal->url('removefile', 'view', 'webournal', ['directory' => $directory_id, 'id' => $file.id])}">L&ouml;schen</a>{/if}
                                </td>
                            </tr>
                        {/if}
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>