<form action="{$Core->url('addgroup', 'admin', 'core')}" method="post">
    <table class="login ui-widget ui-widget-content">
        {if $add_error_name}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_NAME"}Please enter a name!{/translate}</td>
        </tr>
        {elseif $add_error_url_empty}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_URL_EMPTY"}Please enter a URL!{/translate}</td>
        </tr>
        {elseif $add_error_url_incorrect}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_URL_INCORRECT"}URL invalid! Allowed characters:{/translate} a-z,0-9,-</td>
        </tr>
        {elseif $add_error_url_duplicated}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_URL_DUPLICATED"}URL already used{/translate}!</td>
        </tr>
        {elseif $add_error_email_empty}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_EMAIL_EMPTY"}Please enter the email address for the admin!{/translate}</td>
        </tr>
        {elseif $add_error_email_notfound}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_EMAIL_NOTFOUND"}Admin email address not found!{/translate}</td>
        </tr>
        {elseif $add_error_unknown}
        <tr>
            <td colspan="2" class="red">{translate name="ERROR_UNKNOWN"}An unexpected error occurred{/translate}</td>
        </tr>
        {/if}
        <tr>
            <td><label for="url">{translate name="URL"}URL{/translate} (XXXX.{$maindomain})*:</label></td>
            <td><input type="text" name="url" id="url" value="{if $url!==false}{$url|escape:"htmlall"}{/if}" /></td>
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
            <td><label for="email">{translate name="ADMIN_EMAIL"}Admin email address{/translate}*:</label></td>
            <td><input type="text" name="email" id="email" value="{if $email!==false}{$email|escape:"htmlall"}{/if}" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" name="add" value="{translate name="ADD_NEW_GROUP"}Add group{/translate}" id="addbutton" />
            </td>
        </tr>
    </table>
    <small>* {translate name="REQUIRED"}required{/translate}</small>
</form>