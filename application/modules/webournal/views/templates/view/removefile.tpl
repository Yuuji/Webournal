<form action="{$Core->url(null, 'view', 'webournal', $remove_params)}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $remove_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">
                {if $remove_type==='attachment'}
                    Wichtig: Die Datei wird nur aus diesem Anhang entfernt! Hat die Datei danach keine Zuordnung mehr, wird sie endgültig gel&ouml;scht!
                {else}
                    Wichtig: Die Datei wird nur aus dem Ordner entfernt! Hat die Datei danach keine Zuordnung mehr, wird sie endgültig gel&ouml;scht!
                {/if}
            </td>
        </tr>
        <tr>
            <td>Dateiname:</td>
            <td>{$remove_file.name|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>Dokumentennummer:</td>
            <td>{$remove_file.number|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>Dateibeschreibung:</td>
            <td>{$remove_file.description|escape:"htmlall"}</td>
        </tr>
        {if $remove_type==='attachment'}
        <tr>
            <td>Anhang von:</td>
            <td>{$remove_attachedToFile.name|escape:"htmlall"}</td>
        </tr>
        {else}
        <tr>
            <td>Ordnername:</td>
            <td>{$remove_directory.name|escape:"htmlall"}</td>
        </tr>
        {/if}
        <tr>
            <td></td>
            <td><input type="submit" name="remove" value="Datei entfernen" id="removebutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>