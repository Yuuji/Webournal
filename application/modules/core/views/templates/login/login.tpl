<script type="text/javascript" src="/js/login.js"></script>
<form action="{$Core->url('login', 'login', 'core')}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $login_error}
        <tr>
            <td colspan="2" class="red">{translate name="INCORRECT_ACCESS_DATA"}Login incorrect!{/translate}</td>
        </tr>
        {/if}
        <tr>
            <td><label for="login_user">{translate name="USERNAME"}Username{/translate}:</label></td>
            <td><input type="text" name="login_user" id="login_user" /></td>
        </tr>
        <tr>
            <td><label for="login_password">{translate name="PASSWORD"}Password{/translate}:</label></td>
            <td><input type="password" name="login_password" id="login_password" /></td>
        </tr>
        <tr>
            <td class="label">&nbsp;</td>
            <td>
                {ifallowed module='core' controller='login' action='register'}<small><a href="{$Core->url('register', 'login', 'core')}">{translate name="CREATE_NEW_ACCOUNT"}Create new account{/translate}</a></small><br />{/ifallowed}
                <input type="submit" name="login" value="{translate name="LOGIN"}Login{/translate}" id="loginbutton" />
            </td>
        </tr>
    </table>
</form>