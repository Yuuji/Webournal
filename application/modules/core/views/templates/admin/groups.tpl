<h2>{translate name="GROUPS"}Groups{/translate}</h2>
<table>
    <tr>
        <th>{translate name="NAME"}Name{/translate}</th>
        <th>{translate name="URL"}URL{/translate}</th>
        <th>{translate name="DESCRIPTION"}Description{/translate}</th>
        <th>{translate name="OPTIONS"}Options{/translate}</th>
    </tr>
    {foreach $groups as $group}
        <tr>
            <td>{$group.name|escape:"htmlall"}</td>
            <td>{$group.url|escape:"htmlall"}</td>
            <td>{$group.description|escape:"htmlall"}</td>
            <td>
                <a href="{$Core->url('grouprights','admin','core', ['id' => $group.id])}">{translate name="EDIT_RIGHTS"}Edit rights{/translate}</a>
                <a href="{$Core->url('editgroup','admin','core', ['id' => $group.id])}">{translate name="EDIT_GROUP"}Edit group{/translate}</a>
                <a href="{$Core->url('removegroup','admin','core', ['id' => $group.id])}">{translate name="Remove_GROUP"}Remove group{/translate}</a>
            </td>
        </tr>
    {foreachelse}
        <tr>
            <td colspan="2">{translate name="NO_GROUPS"}No groups{/translate}</td>
        </tr>
    {/foreach}
</table>
<a href="{$Core->url('addgroup','admin','core')}">{translate name="ADD_GROUP"}Add group{/translate}</a>