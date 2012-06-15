<form action="{$Core->url('editdirectory', 'view', 'webournal', ['id' => $edit_id])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $edit_error_name_empty}
        <tr>
            <td colspan="2" class="red">Kein Name angegeben!</td>
        </tr>
        {else if $edit_error_date_empty}
        <tr>
            <td colspan="2" class="red">Kein Termindatum angegeben!</td>
        </tr>
        {else if $edit_error_date_incorrect}
        <tr>
            <td colspan="2" class="red">Termindatum nicht korrekt!</td>
        </tr>
        {else if $edit_error_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td><label for="name">Name *:</label></td>
            <td><input type="text" name="name" id="name" value="{$edit_name|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="type">Typ:</label></td>
            <td><select name="type" id="type">
                <option value="directory" {if $edit_type=="directory"}selected{/if}>Ordner</option>
                <option value="date" {if $edit_type=="date"}selected{/if}>Termin</option>
            </select></td>
        </tr>
        <tr id="type_date"{if $edit_type!='date'} style="display: none"{/if}>
            <td><label for="date">Datum und Uhrzeit *:<br />
                (z.B. {$smarty.now|date_format:"Y-m-d H:m:s"})</label></td>
            <td><input type="text" name="date" id="date" value="{$edit_date|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="description">Beschreibung:</label></td>
            <td><input type="text" name="description" id="description" value="{$edit_description|escape:"htmlall"}"></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="edit" value="Ordner &auml;ndern" id="editbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>