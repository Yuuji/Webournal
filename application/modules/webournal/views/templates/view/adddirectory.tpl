<form action="{$Core->url('adddirectory', 'view', 'webournal', $addParams)}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $add_error_name_empty}
        <tr>
            <td colspan="2" class="red">Kein Name angegeben!</td>
        </tr>
        {else if $add_error_date_empty}
        <tr>
            <td colspan="2" class="red">Kein Termindatum angegeben!</td>
        </tr>
        {else if $add_error_date_incorrect}
        <tr>
            <td colspan="2" class="red">Termindatum nicht korrekt!</td>
        </tr>
        {else if $add_error_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td><label for="name">Name *:</label></td>
            <td><input type="text" name="name" id="name" value="{$add_name|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="type">Typ:</label></td>
            <td><select name="type" id="type">
                <option value="directory" {if $add_type=="directory"}selected{/if}>Ordner</option>
                <option value="date" {if $add_type=="date"}selected{/if}>Termin</option>
            </select></td>
        </tr>
        <tr id="type_date"{if $add_type!='date'} style="display: none"{/if}>
            <td><label for="date">Datum und Uhrzeit *:<br />
                (z.B. {$smarty.now|date_format:"Y-m-d H:m:s"})</label></td>
            <td><input type="text" name="date" id="date" value="{$add_date|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="description">Beschreibung:</label></td>
            <td><input type="text" name="description" id="description" value="{$add_description|escape:"htmlall"}"></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="add" value="Ordner hinzuf&uuml;gen" id="addbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>