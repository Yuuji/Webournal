{function pagination cols=[] params=[] data=[]}
{function name="paginationRow"}

{/function}
{function name="paginationRowClass"}

{/function}
<table class="pagination">
    <tr class="paginationheader">
        {foreach $cols as $key => $col}
            {if isset($col.key)}
                {$col = [$col]}
            {/if}
            {if isset($col.display)}
                {$display = $col.display}
            {else}
                {$display = "among"}
            {/if}
            <th class="pagination_cell{$key}">{foreach $col as $colkey => $colvalue}
                {if $colkey!=='display'}
                    {if $colvalue.key==$paginatorSort && $paginatorOrder=="ASC"}
                        {$newOrder = "DESC"}
                    {else}
                        {$newOrder = "ASC"}
                    {/if}
                    {if $colvalue.sortable}<a href="{$Core->url(null, null, null, array_merge($params, ['sort' => $colvalue.key, 'order' => $newOrder, 'count' => $data->getItemCountPerPage(), 'page' => 1]))}">{/if}{translate name=$colvalue.name}{$colvalue.defaultName|escape:"htmlall"}{/translate}{if $colvalue.sortable}</a>{/if}
                    {if !$colvalue@last}{if $display=='side'} / {else}<br />{/if}{/if}
                {/if}
            {/foreach}</th>
        {/foreach}
    </tr>
    {cycle name="paginationTR" values="odd,even" print=false reset=true}
{foreach $data as $row}
    <tr class="{cycle name="paginationTR"} {paginationRowClass row=$row}">
        {paginationRow row=$row}
    </tr>
{/foreach}
    <tr class="paginationcontrol">
        <td colspan="{$cols|@count}">
            {$this->paginationControlParams($data, $params)}
        </td>
    </tr>
</table>
{/function}