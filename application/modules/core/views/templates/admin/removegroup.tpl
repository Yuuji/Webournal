<form action="{$Core->url('removegroup', 'admin', 'core', ['id' => $id])}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $remove_error}
        <tr>
            <td colspan="2" class="red"><b>{translate name="ERROR_UNKNOWN"}An unexpected error occurred{/translate}</b></td>
        </tr>
        {/if}
        <tr>
            <td colspan="2" class="red">
                {translate name="WARNING_DELETE_GROUP"}Do you really want to delete the following group?<br />
                All content will be deleted automatically!<br />
                A deletion can <b>NOT</b> be reversed!{/translate}
             </td>
        </tr>
        <tr>
            <td>{translate name="NAME"}Name{/translate}:</td>
            <td>{$group.name|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td>{translate name="URL"}URL{/translate}:</td>
            <td>{$group.url|escape:"htmlall"}.{$maindomain}</td>
        </tr>
        <tr>
            <td>{translate name="DESCRIPTION"}Description{/translate}:</td>
            <td>{$group.description|escape:"htmlall"}</td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="remove" value="{translate name="DELETE_GROUP"}Delete group finally{/translate}" id="removebutton" /></td>
        </tr>
    </table>
</form>
<small>* {translate name="REQUIRED"}required{/translate}</small>