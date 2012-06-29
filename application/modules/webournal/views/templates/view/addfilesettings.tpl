<form action="{$Core->url(null, 'view', 'webournal', $add_params)}" method="post">
    <input type="hidden" name="ignore" value="{$add_ignore|escape:"htmlall"}" />
    <table class="login ui-widget ui-widget-content">
        {if $add_error_name}
        <tr>
            <td colspan="2" class="red">Kein Name angegeben!</td>
        </tr>
        {else if $add_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td><label for="name">Name *:</label></td>
            <td><input type="texts" name="name" id="name" value="{$add_name|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="number">Dokumentennummer:<br />Optional, hilfreich z.B. bei Aktenzeichen</label></td>
            <td><input type="text" name="number" id="number" value="{$add_number|escape:"htmlall"}" /></td>
        </tr>

        <tr>
            <td><label for="description">Beschreibung:</label></td>
            <td><input type="text" name="description" id="description" value="{$add_description|escape:"htmlall"}"></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="add" value="Datei hinzuf&uuml;gen" id="addbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>