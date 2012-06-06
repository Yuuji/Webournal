<h2>Admin-Zugriffsrechte der Gruppe {$group.name} ({$group.url}.{$maindomain})</h2>
<form action="{$Core->url('addgrouprights', 'admin', 'webournal', ['id' => $group.id])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $error_empty}
        <tr>
            <td colspan="2" class="red">Bitte einen Usernamen und/oder eine E-Mail-Adresse angeben!</td>
        </tr>
        {elseif $error_username}
        <tr>
            <td colspan="2" class="red">Username nicht gefunden!</td>
        </tr>
        {elseif $error_email}
        <tr>
            <td colspan="2" class="red">E-Mail-Adresse nicht gefunden</td>
        </tr>
        {elseif $error_username_email}
        <tr>
            <td colspan="2" class="red">Username und E-Mail-Adresse geh&ouml;ren nicht zusammen!</td>
        </tr>
        {elseif $error_unknown}
        <tr>
            <td colspan="2" class="red">Es ist ein unbekannter Fehler aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td><label for="username">Username*:</label></td>
            <td><input type="text" name="username" id="username" value="{$username|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td><label for="email">E-Mail-Adresse*:</label></td>
            <td><input type="text" name="email" id="email" value="{$email|escape:"htmlall"}" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" name="add" value="Rechte hinzuf&uuml;gen" id="addbutton" />
            </td>
        </tr>
    </table>
    <small>* Username und/oder E-Mail-Adresse ausf&uuml;llen</small>
</form>