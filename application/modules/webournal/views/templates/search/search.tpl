{include file="../view/files_pagination.tpl"}
<b>Suche nach: {$search_search|escape:"htmlall"}</b><br /><br />
{call filespagination files=$search_files params=['action' => 'index', 'search' => $search_search]}