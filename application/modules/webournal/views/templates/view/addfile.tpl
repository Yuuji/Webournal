<form action="{$Core->url('addfile', 'view', 'webournal', ['id' => $add_id])}" method="post" enctype="multipart/form-data">
    <table class="login ui-widget ui-widget-content">
        {if $add_error_upload}
        <tr>
            <td colspan="2" class="red">Fehler beim Upload!</td>
        </tr>
        {else if $add_error_file_missing}
        <tr>
            <td colspan="2" class="red">Keine Datei angeben!</td>
        </tr>
        {else if $add_error_file_type}
        <tr>
            <td colspan="2" class="red">Keine PDF-Datei!</td>
        </tr>
        {else if $add_error_unknown}
        <tr>
            <td colspan="2" class="red">Ein unbekannter Fehler ist aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">Achtung: Die Datei wird &ouml;ffentlich abgelegt und hat keinen Schutz!
            <b>WICHTIG: Je nach Dateityp kann die Anfrage einige Minuten dauern! NICHT abbrechen! NICHT doppelt abschicken!</b> </td>
        </tr>
        <tr>
            <td><label for="addfile">Datei *:</label></td>
            <td><input type="file" name="addfile" id="name" /></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="add" value="Datei hinzuf&uuml;gen" id="addbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>