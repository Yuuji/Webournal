<form action="{$Core->url('editgroup', 'admin', 'core', ['id' => $id])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $edit_error_name}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_NAME"}Please enter a name!{/translate}</td>
        </tr>
        {elseif $edit_error_unknown}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_UNKNOWN"}An unexpected error occurred{/translate}</td>
        </tr>
        {/if}
        <tr>
            <td>{translate name="URL"}URL{/translate} (XXXX.{$maindomain})*:</td>
            <td>{$url|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td><label for="name">{translate name="NAME"}Name{/translate}*:</label></td>
            <td><input type="text" name="name" id="name" value="{if $name!==false}{$name|escape:"htmlall"}{/if}" /></td>
        </tr>
        <tr>
            <td><label for="description">{translate name="DESCRIPTION"}Description{/translate}:</label></td>
            <td><input type="text" name="description" id="description" value="{if $description!==false}{$description|escape:"htmlall"}{/if}" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" name="edit" value="{translate name="CHANGE_GROUP"}Change group{/translate}" id="editbutton" />
            </td>
        </tr>
    </table>
    <small>* {translate name="REQUIRED"}required{/translate}</small>
</form>