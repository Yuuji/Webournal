{if $this->pageCount}
    <div class="paginationControl">
        {$this->firstItemNumber} - {$this->lastItemNumber} {translate name="OF"}of{/translate} {$this->totalItemCount}
        {if isset($this->previous)}
            <a href="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $paginatorCount, 'page' => $this->first]))}">
                {translate name="FIRST"}First{/translate}
            </a> |
        {else}
            <span class="disabled">{translate name="FIRST"}First{/translate}</span> |
        {/if}
        {if isset($this->previous)}
            <a href="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $paginatorCount, 'page' => $this->previous]))}">
                &lt; {translate name="PREVIOUS"}Previous{/translate}
            </a> |
        {else}
            <span class="disabled">&lt; {translate name="PREVIOUS"}Previous{/translate}</span> |
        {/if}
        {foreach $this->pagesInRange as $page}
            {if $page != $this->current}
                <a href="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $paginatorCount, 'page' => $page]))}">{$page}</a>
            {else}
                {$page}
            {/if}
            |
        {/foreach}

        {if isset($this->next)}
            <a href="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $paginatorCount, 'page' => $this->next]))}">{translate name="NEXT"}Next{/translate} &gt;</a> |
        {else}
            <span class="disabled">{translate name="NEXT"}Next{/translate} &gt;</span> |
        {/if}
        
        {if isset($this->next)}
            <a href="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $paginatorCount, 'page' => $this->last]))}">{translate name="LAST"}Last{/translate}</a>
        {else}
            <span class="disabled">{translate name="LAST"}Last{/translate}</span>
        {/if}
        <div class="countselect">
            {translate name="SHOW"}Show{/translate}
            <select name="count" onchange="document.location.href = this.value;">
                {foreach $this->paginatorAllowedCounts as $count}
                    <option value="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => $count, 'page' => 1]))}"{if $count==$paginatorCount} selected{/if}>{$count}</option>
                {/foreach}
                <option value="{$this->Core()->url(null, null, null, array_merge($paginatorParams, ['sort' => $paginatorSort, 'order' => $paginatorOrder, 'count' => -1, 'page' => 1]))}"{if $paginatorCount==-1} selected{/if}>{translate name="ALL"}all{/translate}</option>
            </select>
            {translate name="ITEMS"}Items{/translate}
        </div>
    </div>
{/if}