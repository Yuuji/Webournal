<h2>Admin-Zugriffsrechte der Gruppe {$group.name} ({$group.url}.{$maindomain})</h2>
<form action="{$Core->url('removegrouprights', 'admin', 'core', ['id' => $group.id, 'userid' => $userId])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $error_noemailadmin}
        <tr>
            <td colspan="2" class="red">Benutzerrechte k&ouml;nnen nicht entfernt werden, da sonst niemand mehr Rechte mit E-Mail-Adresse hat!</td>
        </tr>
        {elseif $error_unknown}
        <tr>
            <td colspan="2" class="red">Es ist ein unbekannter Fehler aufgetreten!</td>
        </tr>
        {/if}
        <tr>
            <td>Username:</td>
            <td>{$user.username|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>E-Mail-Adresse:</td>
            <td>{$user.email|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" name="remove" value="Rechte entziehen" id="removebutton" />
            </td>
        </tr>
    </table>
</form>