<form action="{$Core->url('editfile', 'view', 'webournal', ['directory' => $edit_directoryId, 'id' => $edit_fileId])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $edit_error_name}
        <tr>
            <td colspan="2" class="red">Kein Name angegeben!</td>
        </tr>
        {else if $edit_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">Wichtig: Die Daten der Datei werden in jedem zugewiesenen Ordner ge&auml;ndert!</td>
        </tr>
        <tr>
            <td><label for="name">Name *:</label></td>
            <td><input type="text" name="name" id="name" value="{$edit_name|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="number">Dokumentennummer:<br />Optional, hilfreich z.B. bei Aktenzeichen</label></td>
            <td><input type="text" name="number" id="number" value="{$edit_number|escape:"htmlall"}" /></td>
        </tr>

        <tr>
            <td><label for="description">Beschreibung:</label></td>
            <td><input type="text" name="description" id="description" value="{$edit_description|escape:"htmlall"}"></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="edit" value="Datei &auml;ndern" id="editbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>