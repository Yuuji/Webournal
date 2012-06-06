<form method="POST">
    {if $saved}<b>{translate name="SETTINGSSAVED"}Settings saved{/translate}</b>{/if}
{foreach $settings as $groupname => $group}
    <h2>{translate name="SETTINGSFORGROUP"}Settings for group{/translate} "{$groupname|escape:"htmlall"}"</h2>
    <table>
        <tr class="header">
            <th>{translate name="NAME"}Name{/translate}</th>
            <th>{translate name="VALUE"}Value{/translate}</th>
        </tr>
        {foreach $group as $name => $value}
            <tr>
                <td>{$name|escape:"htmlall"}</td>
                <td><input type="text" name="settings[{$groupname|escape:"htmlall"}][{$name|escape:"htmlall"}]" value="{$value|escape:"htmlall"}" /></td>
            </tr>
        {/foreach}
    </table>
{/foreach}
<input type="submit" name="save" value="{translate name="SAVE"}Save{/translate}" />
</form>