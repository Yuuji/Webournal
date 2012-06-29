<form action="{$Core->url(null, 'view', 'webournal', $add_params)}" method="post">
    <table class="login ui-widget ui-widget-content">
        <tr>
            <td colspan="2" class="red">Datei bereits im System hinterlegt?</td>
        </tr>
        {foreach $add_files as $file}
        <tr>
                <td><input type="radio" name="use" value="{$file.id|escape:"htmlall"}"{if $file@first} checked{/if}></td>
                <td><a href="{$file.url}" target="_blank">{$file.name|escape:"htmlall"}</a></td>
        </tr>
        {/foreach}
        <tr>
                <td><input type="radio" name="use" value="new"></td>
                <td>Datei ist nicht doppelt! Neu anlegen!</td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="add" value="Datei hinzuf&uuml;gen" id="addbutton" /></td>
        </tr>
    </table>
</form>
<small>* Pflichtfeld</small>