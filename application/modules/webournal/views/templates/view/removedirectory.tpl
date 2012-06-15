<form action="{$Core->url('removedirectory', 'view', 'webournal', ['id' => $remove_id])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $remove_error}
        <tr>
            <td colspan="2" class="red"><b>Ein unbekannter Fehler ist aufgetreten!</b></td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">
                Wollen Sie den folgenden Ordner wirklich l&ouml;schen?<br />
                Alle Unterordner und m&ouml;gliche Inhalte werden automatisch mitgel&ouml;scht!<br />
                Eine L&ouml;schung kann <b>NICHT</b> r&uuml;ckg&auml;ngig gemacht werden!
             </td>
        </tr>
        <tr>
            <td>Name:</td>
            <td>{$remove_name|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>Typ:</td>
            <td>{if $remove_type=="directory"}Ordner{else if $remove_type=="date"}Termin{/if}</td>
        </tr>
        <tr{if $remove_type!='date'} style="display: none"{/if}>
            <td>Datum und Uhrzeit:</td>
            <td><{$remove_date|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>Beschreibung:</td>
            <td>{$remove_description|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="remove" value="Ordner eng&uuml;ltig l&ouml;schen" id="removebutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>