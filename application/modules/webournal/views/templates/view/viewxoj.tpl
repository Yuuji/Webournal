<?xml version="1.0" standalone="no"?>
<xournal version="{$xoj->version|escape:"htmlall"}">
<title>{$xoj->title|escape:"htmlall"}</title>
{foreach $xoj->pages as $page}
    <page width="{$page->width|escape:"htmlall"}" height="{$page->height|escape:"htmlall"}">
    {if $page->background->type === "solid"}
        <background type="solid" color="{$page->background->color|escape:"htmlall"}" style="{$page->background->style|escape:"htmlall"}" />
    {else if $page->background->type === "pixmap"}
        <background type="pixmap" domain="{$page->background->domain|escape:"htmlall"}" filename="{$page->background->filename|escape:"htmlall"}" />
    {else if $page->background->type === "pdf"}
        <background type="pdf"{if $page->background->domain} domain="{$page->background->domain|escape:"htmlall"}"{/if}{if $page->background->filename} filename="{$page->background->filename|escape:"htmlall"}"{/if} pageno="{$page->background->pageno|escape:"htmlall"}" />
    {/if}
    
    {foreach $page->layer as $layer}
    <layer name="{$layer->name|escape:"htmlall"}" time="{$layer->time|escape:"htmlall"}">
        {foreach $layer->strokes as $stroke}
            <stroke tool="{$stroke->tool|escape:"htmlall"}" color="{$stroke->color|escape:"htmlall"}" width="{$stroke->width|escape:"htmlall"}">{$stroke->value|escape:"htmlall"}</stroke>
        {/foreach}
        {foreach $layer->texts as $text}
            <text font="{$text->font|escape:"htmlall"}" size="{$text->size|escape:"htmlall"}" x="{$text->x|escape:"htmlall"}" y="{$text->y|escape:"htmlall"}" color="{$text->color|escape:"htmlall"}">{$text->value|escape:"htmlall"}</text>
        {/foreach}
    </layer>
    {/foreach}
    </page>
{/foreach}
</xournal>