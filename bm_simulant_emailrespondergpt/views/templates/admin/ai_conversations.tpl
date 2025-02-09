<div class="panel">
    <div class="panel-heading">
        <i class="icon-envelope"></i> {l s='AI Email Conversations' mod='bm_simulant_emailrespondergpt'}
        <span class="badge">{$total_conversations|intval}</span>
        
        <div class="panel-heading-action">
            <a href="{$link->getAdminLink('AdminAIEmailConversations')|escape:'html':'UTF-8'}&export=1" class="btn btn-default">
                <i class="icon-cloud-download"></i> {l s='Export All' mod='bm_simulant_emailrespondergpt'}
            </a>
        </div>
    </div>

    {if empty($conversations)}
        <div class="alert alert-info">
            {l s='No conversations found.' mod='bm_simulant_emailrespondergpt'}
        </div>
    {else}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><span class="title_box">{l s='ID' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Email' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Subject' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Status' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Language' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Received Message' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='AI Response' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Response Time' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Date' mod='bm_simulant_emailrespondergpt'}</span></th>
                        <th><span class="title_box">{l s='Actions' mod='bm_simulant_emailrespondergpt'}</span></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$conversations item=conversation}
                        <tr>
                            <td>{$conversation.id_conversation|intval}</td>
                            <td>{$conversation.email|escape:'html'}</td>
                            <td>{$conversation.subject|escape:'html'}</td>
                            <td>
                                <span class="badge badge-{if $conversation.status == 'processed'}success{elseif $conversation.status == 'failed'}danger{else}warning{/if}">
                                    {$conversation.status|escape:'html'}
                                </span>
                            </td>
                            <td>{$conversation.detected_language|escape:'html'|default:'-'}</td>
                            <td>
                                <span class="message-preview" data-full-message="{$conversation.received_message|escape:'html'}">
                                    {$conversation.received_message|truncate:50|escape:'html'}
                                </span>
                            </td>
                            <td>
                                <span class="message-preview" data-full-message="{$conversation.ai_response|escape:'html'}">
                                    {$conversation.ai_response|truncate:50|escape:'html'}
                                </span>
                            </td>
                            <td>
                                {if $conversation.status == 'processed'}
                                    {$conversation.response_time|escape:'html'} {l s='min' mod='bm_simulant_emailrespondergpt'}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>{$conversation.date_add|escape:'html'}</td>
                            <td class="text-right">
                                <div class="btn-group">
                                    <a href="{$link->getAdminLink('AdminAIEmailConversations')|escape:'html':'UTF-8'}&id_conversation={$conversation.id_conversation|intval}&viewconversation=1" 
                                       class="btn btn-default" 
                                       title="{l s='View' mod='bm_simulant_emailrespondergpt'}">
                                        <i class="icon-eye"></i>
                                    </a>
                                    {if $conversation.status == 'failed'}
                                        <a href="{$link->getAdminLink('AdminAIEmailConversations')|escape:'html':'UTF-8'}&id_conversation={$conversation.id_conversation|intval}&retry=1" 
                                           class="btn btn-warning" 
                                           title="{l s='Retry' mod='bm_simulant_emailrespondergpt'}"
                                           onclick="return confirm('{l s='Are you sure you want to retry this conversation?' mod='bm_simulant_emailrespondergpt'}');">
                                            <i class="icon-refresh"></i>
                                        </a>
                                    {/if}
                                    <a href="{$link->getAdminLink('AdminAIEmailConversations')|escape:'html':'UTF-8'}&id_conversation={$conversation.id_conversation|intval}&delete=1" 
                                       class="btn btn-danger" 
                                       title="{l s='Delete' mod='bm_simulant_emailrespondergpt'}"
                                       onclick="return confirm('{l s='Are you sure you want to delete this conversation?' mod='bm_simulant_emailrespondergpt'}');">
                                        <i class="icon-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        {* Pagination *}
        {if isset($pagination) && $pagination.total_pages > 1}
            <div class="pagination">
                <ul class="pagination">
                    {if $pagination.current_page > 1}
                        <li>
                            <a href="{$pagination.first_url|escape:'html':'UTF-8'}" title="{l s='First page' mod='bm_simulant_emailrespondergpt'}">
                                <i class="icon-double-angle-left"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{$pagination.prev_url|escape:'html':'UTF-8'}" title="{l s='Previous page' mod='bm_simulant_emailrespondergpt'}">
                                <i class="icon-angle-left"></i>
                            </a>
                        </li>
                    {/if}

                    {foreach from=$pagination.pages item=page}
                        <li {if $page.current}class="active"{/if}>
                            <a href="{$page.url|escape:'html':'UTF-8'}">{$page.number|intval}</a>
                        </li>
                    {/foreach}

                    {if $pagination.current_page < $pagination.total_pages}
                        <li>
                            <a href="{$pagination.next_url|escape:'html':'UTF-8'}" title="{l s='Next page' mod='bm_simulant_emailrespondergpt'}">
                                <i class="icon-angle-right"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{$pagination.last_url|escape:'html':'UTF-8'}" title="{l s='Last page' mod='bm_simulant_emailrespondergpt'}">
                                <i class="icon-double-angle-right"></i>
                            </a>
                        </li>
                    {/if}
                </ul>
            </div>
        {/if}
    {/if}
</div>

{* Message Preview Modal *}
<div id="message-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{l s='Message Content' mod='bm_simulant_emailrespondergpt'}</h4>
            </div>
            <div class="modal-body">
                <pre class="message-content"></pre>
            </div>
        </div>
    </div>
</div>