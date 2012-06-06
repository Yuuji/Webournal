<form action="{$Core->url('changepassword', 'user', 'core')}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $changepassword_error}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_PASSWORD"}Passwords do not match or password is to short!{/translate}</td>
        </tr>
        {else if $changepassword_success}
        <tr>
            <td colspan="2" class="green">{translate name="PASSWORD_CHANGED"}Password changed!{/translate}</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2">{translate name="REGISTER_REQUIRED"}Password must be at least 8 characters long!{/translate}</td>
        </tr>
        <tr>
            <td><label for="password">{translate name="NEW_PASSWORD"}New password{/translate}:</label></td>
            <td><input type="password" name="password" id="password" /></td>
        </tr>
        <tr>
            <td><label for="password2">{translate name="PASSWORD_REPEAT"}Repeat password{/translate}:</label></td>
            <td><input type="password" name="password2" id="password2" /></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" name="change" value="{translate name="CHANGE_PASSWORD"}Change password{/translate}" id="changebutton" /></td>
        </tr>
    </table>
</form>