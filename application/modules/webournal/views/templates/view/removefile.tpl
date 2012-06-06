<form action="{$Webournal->url('removefile', 'view', 'webournal', ['directory' => $remove_directoryId, 'id' => $remove_fileId])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $remove_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">Wichtig: Die Datei wird nur aus dem Ordner entfernt! Hat die Datei danach keine Zuordnung mehr, wird sie endgültig gel&ouml;scht!</td>
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
        <tr>
            <td>Ordnername:</td>
            <td>{$remove_directory.name|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="remove" value="Datei entfernen" id="removebutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>