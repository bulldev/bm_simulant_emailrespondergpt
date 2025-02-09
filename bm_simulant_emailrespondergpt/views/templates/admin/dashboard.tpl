{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="row">
    <!-- Status Card -->
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-info"></i> {l s='Module Status' mod='bm_simulant_emailrespondergpt'}
                <span class="badge badge-{if $moduleStatus.allConfigured}success{else}warning{/if} pull-right">
                    {if $moduleStatus.allConfigured}
                        {l s='Ready' mod='bm_simulant_emailrespondergpt'}
                    {else}
                        {l s='Needs Configuration' mod='bm_simulant_emailrespondergpt'}
                    {/if}
                </span>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert {if $moduleStatus.emailConfigured}alert-success{else}alert-warning{/if}">
                            <i class="icon-envelope"></i> {l s='Email Configuration' mod='bm_simulant_emailrespondergpt'}
                            {if !$moduleStatus.emailConfigured}
                                <p class="small">{l s='IMAP/SMTP settings needed' mod='bm_simulant_emailrespondergpt'}</p>
                            {/if}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert {if $moduleStatus.aiConfigured}alert-success{else}alert-warning{/if}">
                            <i class="icon-microchip"></i> {l s='AI Configuration' mod='bm_simulant_emailrespondergpt'}
                            {if !$moduleStatus.aiConfigured}
                                <p class="small">{l s='OpenAI API key needed' mod='bm_simulant_emailrespondergpt'}</p>
                            {/if}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert {if $moduleStatus.cronConfigured}alert-success{else}alert-warning{/if}">
                            <i class="icon-clock-o"></i> {l s='Cron Status' mod='bm_simulant_emailrespondergpt'}
                            {if !$moduleStatus.cronConfigured}
                                <p class="small">{l s='Cron job not detected' mod='bm_simulant_emailrespondergpt'}</p>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-bar-chart"></i> {l s='Quick Statistics' mod='bm_simulant_emailrespondergpt'}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="stat-box">
                            <span class="stat-number">{$stats.totalEmails}</span>
                            <span class="stat-label">{l s='Total Emails' mod='bm_simulant_emailrespondergpt'}</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stat-box">
                            <span class="stat-number">{$stats.todayEmails}</span>
                            <span class="stat-label">{l s='Today' mod='bm_simulant_emailrespondergpt'}</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stat-box">
                            <span class="stat-number">{$stats.avgResponseTime}m</span>
                            <span class="stat-label">{l s='Avg Response Time' mod='bm_simulant_emailrespondergpt'}</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stat-box">
                            <span class="stat-number">{$stats.successRate}%</span>
                            <span class="stat-label">{l s='Success Rate' mod='bm_simulant_emailrespondergpt'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-flash"></i> {l s='Quick Actions' mod='bm_simulant_emailrespondergpt'}
            </div>
            <div class="panel-body">
                <div class="btn-group-vertical w-100">
                    <a href="{$link->getAdminLink('AdminSimulantAISettings')}" class="btn btn-default btn-lg">
                        <i class="icon-cog"></i> {l s='Configure Settings' mod='bm_simulant_emailrespondergpt'}
                    </a>
                    <a href="{$link->getAdminLink('AdminAIEmailConversations')}" class="btn btn-default btn-lg">
                        <i class="icon-envelope"></i> {l s='View Conversations' mod='bm_simulant_emailrespondergpt'}
                    </a>
                    <a href="#" class="btn btn-default btn-lg" id="testConnection">
                        <i class="icon-refresh"></i> {l s='Test Connection' mod='bm_simulant_emailrespondergpt'}
                    </a>
                    <a href="#" class="btn btn-default btn-lg" id="checkInbox">
                        <i class="icon-inbox"></i> {l s='Check Inbox Now' mod='bm_simulant_emailrespondergpt'}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-time"></i> {l s='Recent Activity' mod='bm_simulant_emailrespondergpt'}
            </div>
            <div class="panel-body">
                {if empty($recentActivity)}
                    <p class="text-muted text-center">{l s='No recent activity' mod='bm_simulant_emailrespondergpt'}</p>
                {else}
                    <div class="activity-list">
                        {foreach $recentActivity as $activity}
                            <div class="activity-item">
                                <span class="badge badge-{$activity.type}">{$activity.type}</span>
                                <span class="activity-time">{$activity.time}</span>
                                <span class="activity-text">{$activity.message}</span>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>
        </div>
    </div>

    <!-- Setup Guide -->
    {if !$moduleStatus.allConfigured}
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-book"></i> {l s='Setup Guide' mod='bm_simulant_emailrespondergpt'}
                </div>
                <div class="panel-body">
                    <div class="setup-steps">
                        <div class="step {if $moduleStatus.emailConfigured}done{/if}">
                            <span class="step-number">1</span>
                            <span class="step-text">{l s='Configure Email Settings' mod='bm_simulant_emailrespondergpt'}</span>
                            {if !$moduleStatus.emailConfigured}
                                <a href="{$link->getAdminLink('AdminSimulantAISettings')}" class="btn btn-xs btn-primary">{l s='Configure Now' mod='bm_simulant_emailrespondergpt'}</a>
                            {/if}
                        </div>
                        <div class="step {if $moduleStatus.aiConfigured}done{/if}">
                            <span class="step-number">2</span>
                            <span class="step-text">{l s='Set Up AI Integration' mod='bm_simulant_emailrespondergpt'}</span>
                            {if !$moduleStatus.aiConfigured}
                                <a href="{$link->getAdminLink('AdminSimulantAISettings')}" class="btn btn-xs btn-primary">{l s='Configure Now' mod='bm_simulant_emailrespondergpt'}</a>
                            {/if}
                        </div>
                        <div class="step {if $moduleStatus.cronConfigured}done{/if}">
                            <span class="step-number">3</span>
                            <span class="step-text">{l s='Set Up Cron Job' mod='bm_simulant_emailrespondergpt'}</span>
                            {if !$moduleStatus.cronConfigured}
                                <button type="button" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#cronModal">
                                    {l s='View Instructions' mod='bm_simulant_emailrespondergpt'}
                                </button>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</div>

<!-- Cron Instructions Modal -->
<div class="modal fade" id="cronModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{l s='Cron Job Setup Instructions' mod='bm_simulant_emailrespondergpt'}</h4>
            </div>
            <div class="modal-body">
                <p>{l s='Add the following cron job to your server:' mod='bm_simulant_emailrespondergpt'}</p>
                <pre>*/5 * * * * php {$cronPath} token={$cronToken}</pre>
                <p class="text-muted">{l s='This will check for new emails every 5 minutes.' mod='bm_simulant_emailrespondergpt'}</p>
            </div>
        </div>
    </div>
</div>

{* Add custom CSS *}
<style>
    .stat-box {
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #25b9d7;
    }
    .stat-label {
        display: block;
        color: #666;
        font-size: 14px;
        margin-top: 5px;
    }
    .setup-steps .step {
        padding: 15px;
        border: 1px solid #ddd;
        margin-bottom: 10px;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    .step-number {
        width: 30px;
        height: 30px;
        background: #25b9d7;
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    .step.done {
        background: #f8f9fa;
        border-color: #28a745;
    }
    .step.done .step-number {
        background: #28a745;
    }
    .activity-list {
        max-height: 300px;
        overflow-y: auto;
    }
    .activity-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .activity-time {
        color: #666;
        font-size: 12px;
        margin-left: 10px;
    }
    .btn-group-vertical {
        width: 100%;
    }
    .btn-group-vertical .btn {
        text-align: left;
        margin-bottom: 10px;
    }
    .btn-group-vertical .btn i {
        margin-right: 10px;
    }
</style>
{/block}