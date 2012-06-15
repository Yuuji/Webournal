<form action="{$Core->url('index', 'search', 'webournal')}" method="post">
    <table class="ui-widget ui-widget-content">
        {if $error_noinput}
        <tr>
            <td colspan="2" class="red">Bitte einen Suchbegriff eingeben!</td>
        </tr>
        {elseif $error_tooshort}
        <tr>
            <td colspan="2" class="red">Jeder Suchbegriff muss mindestens drei Zeichen lang sein!</td>
        </tr>
        {/if}
        <tr>
            <td colspan="2">Jeder Suchbegriff muss mindestens drei Zeichen lang sein!</td>
        </tr>
        <tr>
            <td><label for="search">Suchen nach:</label></td>
            <td><input type="text" name="search" id="search" /></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="Suchen" /></td>
        </tr>
    </table>
</form>