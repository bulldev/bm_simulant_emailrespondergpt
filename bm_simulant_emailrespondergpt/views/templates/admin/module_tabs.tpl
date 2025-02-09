<ul class="nav nav-tabs">
    <li class="{if $currentTab == 'settings'}active{/if}">
        <a href="{$settingsUrl|escape:'html':'UTF-8'}">
            <i class="icon-cogs"></i> {l s='Settings' mod='bm_simulant_emailrespondergpt'}
        </a>
    </li>
    <li class="{if $currentTab == 'logs'}active{/if}">
        <a href="{$logsUrl|escape:'html':'UTF-8'}">
            <i class="icon-list"></i> {l s='Logs' mod='bm_simulant_emailrespondergpt'}
        </a>
    </li>
    <li class="{if $currentTab == 'statistics'}active{/if}">
        <a href="{$statisticsUrl|escape:'html':'UTF-8'}">
            <i class="icon-bar-chart"></i> {l s='Statistics' mod='bm_simulant_emailrespondergpt'}
        </a>
    </li>
</ul>

<div class="tab-content panel">
    {if $currentTab == 'settings'}
        {include file="../templates/admin/settings.tpl"}
    {elseif $currentTab == 'logs'}
        {include file="../templates/admin/logs.tpl"}
    {elseif $currentTab == 'statistics'}
        {include file="../templates/admin/statistics.tpl"}
    {/if}
</div>