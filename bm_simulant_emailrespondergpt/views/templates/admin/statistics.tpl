{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> {l s='AI Email Statistics' mod='bm_simulant_emailrespondergpt'}
    </div>
    
    <!-- Date Range Filter -->
    <form id="statistics-date-range" class="form-inline" action="{$current_url|escape:'html':'UTF-8'}" method="post">
        <div class="form-group">
            <label>{l s='From' mod='bm_simulant_emailrespondergpt'}</label>
            <input type="date" name="date_from" value="{$dateFrom|escape:'html':'UTF-8'}" class="form-control">
        </div>
        <div class="form-group">
            <label>{l s='To' mod='bm_simulant_emailrespondergpt'}</label>
            <input type="date" name="date_to" value="{$dateTo|escape:'html':'UTF-8'}" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">{l s='Filter' mod='bm_simulant_emailrespondergpt'}</button>
    </form>

    <!-- Basic Stats -->
    <div class="row statistics-summary">
        <div class="col-md-3">
            <div class="panel">
                <div class="panel-heading">{l s='Total Emails' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$totalEmailsAnswered|intval}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel">
                <div class="panel-heading">{l s='Successful Responses' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$totalAIResponses|intval}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel">
                <div class="panel-heading">{l s='Pending' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$pendingResponses|intval}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel">
                <div class="panel-heading">{l s='Failed' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$failedResponses|intval}</div>
            </div>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="row statistics-performance">
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-heading">{l s='Average Response Time' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$averageResponseTime|string_format:"%.2f"} {l s='minutes' mod='bm_simulant_emailrespondergpt'}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-heading">{l s='Success Rate' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$responseSuccessRate|string_format:"%.2f"}%</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-heading">{l s='API Calls' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">{$openAICalls|intval}</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">{l s='Daily Statistics' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">
                    <canvas id="dailyStatsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">{l s='Language Distribution' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">
                    <canvas id="languageChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Analysis -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">{l s='Peak Hours' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">
                    <canvas id="peakHoursChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">{l s='Busy Days' mod='bm_simulant_emailrespondergpt'}</div>
                <div class="panel-body">
                    <canvas id="busyDaysChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}