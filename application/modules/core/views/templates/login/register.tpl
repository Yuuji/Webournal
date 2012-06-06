<form action="{$Core->url('register', 'login', 'core')}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $register_error_pw}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_PASSWORD"}Passwords do not match or password is to short!{/translate}</td>
        </tr>
        {else if $register_error_user}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_USERNAME"}Username is already taken!{/translate}</td>
        </tr>
        {else if $register_error_email}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_EMAI"}There is already an account with this email address{/translate}</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2">
                {translate name="REGISTER_REQUIRED"}Password must be at least 8 characters long!{/translate}
                {if $loginconfig->register->allowwithoutemail}<br />{translate name="REGISTER_OPTIONALEMAIL"}Email address is optional. Without your email address it is not possible to send a new password!{/translate}{/if}
            </td>
        </tr>
        <tr>
            <td><label for="username">{translate name="USERNAME"}Username{/translate} *:</label></td>
            <td><input type="text" name="username" id="username" value="{if $username!==false}{$username|escape:"htmlall"}{/if}" /></td>
        </tr>
        <tr>
            <td><label for="password">{translate name="PASSWORD"}Password{/translate}: *:</label></td>
            <td><input type="password" name="password" id="password" /></td>
        </tr>
        <tr>
            <td><label for="password2">{translate name="PASSWORD_REPEAT"}Repeat password{/translate}: *:</label></td>
            <td><input type="password" name="password2" id="password2" /></td>
        </tr>
        <tr>
            <td><label for="email">{translate name="EMAIL"}Email{/translate}:</label></td>
            <td><input type="email" name="email" id="email" value="{if $email!==false}{$email|escape:"htmlall"}{/if}" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <small><a href="{$Core->url('login', 'login', 'core')}">{translate name="ACCOUNT_EXISTS"}I already have an account{/translate}</a></small><br />
                <input type="submit" name="register" value="{translate name="REGISTER"}Register{/translate}" id="registerbutton" />
            </td>
        </tr>
    </table>
    <small>* {translate name="REQUIRED"}required{/translate}</small>
</form>