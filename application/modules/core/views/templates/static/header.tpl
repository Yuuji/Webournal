{function name="menu"}
    {foreach $data as $entry}
        {ifallowed module=$entry.module controller=$entry.controller action=$entry.action}{if !isset($entry.params)}{$entry.params=null}{/if}
            <button><a href="{$Core->url($entry.action, $entry.controller, $entry.module, $entry.params)}" title="{$entry.name}">{$entry.name}</a></button>
        {/ifallowed}
    {/foreach}
{/function}

{block name="menu"}
<div id="menu" class="menu">
    {$menu = [
        [
            "module"        => "core",
            "name"          => "Login",
            "controller"    => "login",
            "action"        => "login"
        ],
        [
            "module"        => "core",
            "name"          => "Account",
            "controller"    => "user",
            "action"        => "index"
        ],
        [
            "module"        => "core",
            "name"          => "Admin",
            "controller"    => "admin",
            "action"        => "index"
        ],
        [
            "module"        => "core",
            "name"          => "Logout",
            "controller"    => "login",
            "action"        => "logout"
        ]
    ]}

    {menu data=$menu}
</div>
{/block}
<br />
{block name="submenu"}
{if isset($submenu) && count($submenu)>0}
    <div class="submenu">
        {menu data=$submenu}
    </div>
{/if}
{/block}