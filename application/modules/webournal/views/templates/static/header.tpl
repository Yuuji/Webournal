{function name="menu"}
    {if count($data)>0}
        <ul>
        {foreach $data as $entry}
            <li>{$url = $entry->href}
                {if !empty($url)}<a href="{$url}"{if $entry->active} class="active"{/if}>{else}<span>{/if}{translate name=$entry->label}{$entry->defaultTranslation}{/translate}{if !empty($url)}</a>{else}</span>{/if}
                {menu data=$entry}
            </li>
        {/foreach}
        </ul>
    {/if}
{/function}

{function name="submenu"}
    {foreach $data as $entry}
        {if $acl->has("{$entry.controller}_{$entry.action}")}
            {assign var="resource" value="{$entry.controller}_{$entry.action}"}
        {elseif $acl->has($entry.controller)}
            {assign var="resource" value=$entry.controller}
        {else}
            {assign var="resource" value=null}
        {/if}
        {if $acl->isAllowed($accesscontrol->group, $resource)}{if !isset($entry.params)}{$entry.params=null}{/if}
            <a href="{$Core->url($entry.action, $entry.controller, $entry.module, $entry.params)}" title="{$entry.name}"{if (!isset($entry.neverselected) || $entry.neverselected!==true) && $WEBOURNAL_CONTROLLER==$entry.controller && (!$submenu || $WEBOURNAL_ACTION==$entry.action)} class="selected"{/if}>{$entry.name}</a>
        {/if}
    {/foreach}
{/function}


{block name="logo"}
    <div id="logo" class="logo">
        <img src="/images/logo.png" alt="Webournal" title="Webournal" />
    </div>
{/block}


<div id="container">
    {block name="menu"}
    <div id="menu" class="menu">
        {$this->menuHelper()}{menu data=$menu}
        <div style="clear: both;"></div>
    </div>
    {/block}
    {block name="submenu"}
    {if isset($submenu) && count($submenu)>0}
        <div id="submenu">
            {submenu data=$submenu submenu=true}
        </div>
    {/if}
    {/block}
    <br />