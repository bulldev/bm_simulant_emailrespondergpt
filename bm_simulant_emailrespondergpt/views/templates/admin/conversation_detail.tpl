<div class="panel">
    <div class="panel-heading">
        <i class="icon-envelope"></i> {l s='Conversation Details' mod='bm_simulant_emailrespondergpt'}
        {if $can_retry}
            <a href="{$retry_url|escape:'html':'UTF-8'}" class="btn btn-warning pull-right">
                <i class="icon-refresh"></i> {l s='Retry' mod='bm_simulant_emailrespondergpt'}
            </a>
        {/if}
    </div>

    <div class="panel-body">
        <!-- Meta Information -->
        <div class="row conversation-meta">
            <div class="col-md-3">
                <div class="meta-item">
                    <label>{l s='Status' mod='bm_simulant_emailrespondergpt'}</label>
                    <div>
                        <span class="badge badge-{if $conversation.status == 'processed'}success{elseif $conversation.status == 'failed'}danger{else}warning{/if}">
                            {$conversation.status|escape:'html'}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="meta-item">
                    <label>{l s='Response Time' mod='bm_simulant_emailrespondergpt'}</label>
                    <div>{$response_time|escape:'html'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="meta-item">
                    <label>{l s='Language' mod='bm_simulant_emailrespondergpt'}</label>
                    <div>{$detected_language|escape:'html'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="meta-item">
                    <label>{l s='Date' mod='bm_simulant_emailrespondergpt'}</label>
                    <div>{$conversation.date_add|escape:'html'}</div>
                </div>
            </div>
        </div>

        <!-- Conversation Details -->
        <div class="conversation-details">
            <table class="table">
                <tr>
                    <th>{l s='ID' mod='bm_simulant_emailrespondergpt'}:</th>
                    <td>{$conversation.id_conversation|intval}</td>
                </tr>
                <tr>
                    <th>{l s='Email' mod='bm_simulant_emailrespondergpt'}:</th>
                    <td>{$conversation.email|escape:'html'}</td>
                </tr>
                <tr>
                    <th>{l s='Subject' mod='bm_simulant_emailrespondergpt'}:</th>
                    <td>{$conversation.subject|escape:'html'}</td>
                </tr>
            </table>
        </div>

        <!-- Message Content -->
        <div class="conversation-messages">
            <div class="message-bubble received-message">
                <h4>{l s='Received Message' mod='bm_simulant_emailrespondergpt'}</h4>
                <div class="message-content">
                    {$conversation.received_message|escape:'html'|nl2br}
                </div>
            </div>

            <div class="message-bubble ai-response">
                <h4>{l s='AI Response' mod='bm_simulant_emailrespondergpt'}</h4>
                <div class="message-content">
                    {$conversation.ai_response|escape:'html'|nl2br}
                </div>
            </div>
        </div>

        <!-- Related Logs -->
        {if !empty($logs)}
            <div class="logs-section">
                <h4>{l s='Related Logs' mod='bm_simulant_emailrespondergpt'}</h4>
                {foreach from=$logs item=log}
                    <div class="log-entry">
                        <span class="badge badge-{if $log.log_type == 'error'}danger{elseif $log.log_type == 'warning'}warning{elseif $log.log_type == 'success'}success{else}info{/if}">
                            {$log.log_type|escape:'html'}
                        </span>
                        <span class="log-date">{$log.date_add|escape:'html'}</span>
                        <div class="log-message">{$log.message|escape:'html'|nl2br}</div>
                    </div>
                {/foreach}
            </div>
        {/if}
    </div>

    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminAIEmailConversations')|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon-arrow-left"></i> {l s='Back to Conversations' mod='bm_simulant_emailrespondergpt'}
        </a>
    </div>
</div>