<h2>Admin-Zugriffsrechte der Gruppe {$group.name} ({$group.url}.{$maindomain})</h2>
<table>
    <tr>
        <th>Name</th>
        <th>E-Mail-Adresse</th>
        <th>Optionen</th>
    </tr>
    {foreach $admins as $admin}
        <tr>
            <td>{$admin.username|escape:"htmlall"}</td>
            <td>{$admin.email|escape:"htmlall"}</td>
            <td>
                <a href="{$Core->url('removegrouprights','admin','core', ['id' => $group.id, 'userid' => $admin.id])}">Rechte entziehen</a>
            </td>
        </tr>
    {foreachelse}
        <tr>
            <td colspan="2">Keine Admins? Irgendwas ist hier faul!</td>
        </tr>
    {/foreach}
</table>
<a href="{$Core->url('addgrouprights','admin','core', ['id' => $group.id])}">Rechte hinzuf&uuml;gen</a>