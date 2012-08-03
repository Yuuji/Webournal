<?xml version="1.0" standalone="no"?>
<xournal version="{$xoj->version|escape:"htmlall"}">
<title>{$xoj->title|escape:"htmlall"}</title>
{assign var="oldFilename" value=""}
{foreach $xoj->pages as $page}
	{assign var="setFilename" value=true}
	{if $page->background->filename}
		{assign var="filename" value=$page->background->filename|replace:"{$publicPath}/":""}
		{if $filename==$oldFilename}
			{assign var="setFilename" value=false}
		{else}
			{assign var="oldFilename" value=$filename}
		{/if}
	{/if}
    <page width="{$page->width|escape:"htmlall"}" height="{$page->height|escape:"htmlall"}">
    {if $page->background->type === "solid"}
        <background type="solid" color="{$page->background->color|escape:"htmlall"}" style="{$page->background->style|escape:"htmlall"}" />
    {else if $page->background->type === "pixmap"}
        <background type="pixmap"{if $setFilename} domain="{$page->background->domain|escape:"htmlall"}" filename="{$filename|escape:"htmlall"}"{/if} />
    {else if $page->background->type === "pdf"}
        <background type="pdf"{if $setFilename}{if $page->background->domain} domain="{$page->background->domain|escape:"htmlall"}"{/if}{if $page->background->filename} filename="{$filename|escape:"htmlall"}"{/if}{/if} pageno="{$page->background->pageno|escape:"htmlall"}" />
    {/if}
    
    {foreach $page->layer as $layer}
    <layer name="{$layer->name|escape:"htmlall"}" time="{$layer->time|escape:"htmlall"}">
        {foreach $layer->strokes as $stroke}
            <stroke tool="{$stroke->tool|escape:"htmlall"}" color="{$stroke->color|escape:"htmlall"}{if $stroke->tool=='highlighter'}7f{else}ff{/if}" width="{$stroke->width|escape:"htmlall"}">{$stroke->value|escape:"htmlall"}</stroke>
        {/foreach}
        {foreach $layer->texts as $text}
            <text font="{$text->font|escape:"htmlall"}" size="{$text->size|escape:"htmlall"}" x="{$text->x|escape:"htmlall"}" y="{$text->y|escape:"htmlall"}" color="{$text->color|escape:"htmlall"}ff">{$text->value|escape:"htmlall"}</text>
        {/foreach}
    </layer>
	{foreachelse}
		<layer></layer>
    {/foreach}
    </page>
{/foreach}
</xournal>
