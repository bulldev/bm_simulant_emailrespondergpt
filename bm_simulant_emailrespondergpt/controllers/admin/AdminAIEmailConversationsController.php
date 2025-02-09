<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAIEmailConversationsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'email_ai_conversations';
        $this->className = 'EmailAIConversation';
        $this->identifier = 'id_conversation';
        $this->list_no_link = true;
        
        parent::__construct();

        $this->fields_list = [
            'id_conversation' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'email' => [
                'title' => $this->l('Email'),
                'filter_key' => 'a!email',
                'havingFilter' => true
            ],
            'subject' => [
                'title' => $this->l('Subject'),
                'filter_key' => 'a!subject',
                'havingFilter' => true
            ],
            'status' => [
                'title' => $this->l('Status'),
                'type' => 'select',
                'list' => [
                    'pending' => $this->l('Pending'),
                    'processed' => $this->l('Processed'),
                    'failed' => $this->l('Failed')
                ],
                'filter_key' => 'a!status',
                'callback' => 'getStatusBadge'
            ],
            'detected_language' => [
                'title' => $this->l('Language'),
                'filter_key' => 'a!detected_language'
            ],
            'received_message' => [
                'title' => $this->l('Received Message'),
                'callback' => 'truncateMessage',
                'search' => false
            ],
            'ai_response' => [
                'title' => $this->l('AI Response'),
                'callback' => 'truncateMessage',
                'search' => false
            ],
            'date_add' => [
                'title' => $this->l('Received'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ],
            'date_upd' => [
                'title' => $this->l('Responded'),
                'type' => 'datetime',
                'filter_key' => 'a!date_upd'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ],
            'export' => [
                'text' => $this->l('Export selected'),
                'icon' => 'icon-cloud-download'
            ]
        ];

        $this->_select = 'a.*, TIMESTAMPDIFF(MINUTE, a.date_add, a.date_upd) as response_time';
        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';
        $this->_pagination = [20, 50, 100];

        // Add row actions
        $this->addRowAction('view');
        $this->addRowAction('retry');
        $this->addRowAction('delete');
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        // Add export button
        $this->page_header_toolbar_btn['export'] = [
            'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Export All'),
            'icon' => 'process-icon-download'
        ];

        // Add statistics button
        $this->page_header_toolbar_btn['statistics'] = [
            'href' => self::$currentIndex . '&statistics' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('View Statistics'),
            'icon' => 'process-icon-stats'
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // Add module specific CSS and JS
        $this->addJS([
            $this->module->getPathUri() . 'views/js/conversations.js'
        ]);
        
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/conversations.css'
        ]);
    }

    public function renderList()
    {
        return parent::renderList();
    }

    public function truncateMessage($value, $row)
    {
        if (empty($value)) {
            return '-';
        }

        $maxLength = 100;
        $formattedMessage = htmlspecialchars($value);
        
        if (strlen($value) > $maxLength) {
            $shortMessage = substr($formattedMessage, 0, $maxLength) . '...';
            return sprintf(
                '<span class="message-preview" data-full-message="%s">%s</span>',
                $formattedMessage,
                $shortMessage
            );
        }
        
        return $formattedMessage;
    }

    public function getStatusBadge($status)
    {
        $badges = [
            'pending' => 'badge-warning',
            'processed' => 'badge-success',
            'failed' => 'badge-danger'
        ];

        $class = isset($badges[$status]) ? $badges[$status] : 'badge-default';
        return '<span class="badge ' . $class . '">' . $this->l($status) . '</span>';
    }

    public function renderView()
    {
        try {
            $id_conversation = (int)Tools::getValue('id_conversation');
            
            $conversation = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'email_ai_conversations` 
                 WHERE `id_conversation` = ' . (int)$id_conversation
            );

            if (!$conversation) {
                throw new Exception($this->l('Conversation not found.'));
            }

            // Get related logs
            $logs = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'email_ai_logs` 
                 WHERE `email` = "' . pSQL($conversation['email']) . '"
                 AND date_add BETWEEN "' . pSQL($conversation['date_add']) . '" 
                 AND "' . pSQL($conversation['date_upd']) . '"
                 ORDER BY date_add DESC'
            );

            $this->context->smarty->assign([
                'conversation' => $conversation,
                'logs' => $logs,
                'response_time' => $this->getResponseTime($conversation),
                'detected_language' => $this->getLanguageName($conversation['detected_language']),
                'can_retry' => $conversation['status'] === 'failed',
                'retry_url' => $this->context->link->getAdminLink('AdminAIEmailConversations') . 
                              '&retry&id_conversation=' . $id_conversation
            ]);

            return $this->context->smarty->fetch(
                $this->module->getLocalPath() . 'views/templates/admin/conversation_detail.tpl'
            );

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return parent::renderView();
        }
    }

    protected function getResponseTime($conversation)
    {
        if ($conversation['status'] !== 'processed') {
            return '-';
        }

        $start = strtotime($conversation['date_add']);
        $end = strtotime($conversation['date_upd']);
        $minutes = round(($end - $start) / 60);

        if ($minutes < 60) {
            return $minutes . ' ' . $this->l('minutes');
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return sprintf('%d %s %d %s', 
            $hours, 
            $this->l('hours'),
            $remainingMinutes,
            $this->l('minutes')
        );
    }

    protected function getLanguageName($code)
    {
        $languages = [
            'en' => $this->l('English'),
            'fr' => $this->l('French'),
            'es' => $this->l('Spanish'),
            'de' => $this->l('German'),
        ];

        return isset($languages[$code]) ? $languages[$code] : $code;
    }

    public function processRetry()
    {
        try {
            $id_conversation = (int)Tools::getValue('id_conversation');
            $conversation = new EmailAIConversation($id_conversation);

            if (!Validate::isLoadedObject($conversation)) {
                throw new Exception($this->l('Conversation not found.'));
            }

            if ($conversation->status !== 'failed') {
                throw new Exception($this->l('Only failed conversations can be retried.'));
            }

            // Update status to pending
            $conversation->status = 'pending';
            $conversation->date_upd = date('Y-m-d H:i:s');

            if (!$conversation->update()) {
                throw new Exception($this->l('Failed to update conversation status.'));
            }

            // Log the retry attempt
            EmailAILogger::log(
                sprintf('Retrying conversation #%d', $id_conversation),
                'info',
                $conversation->email
            );

            $this->confirmations[] = $this->l('Conversation queued for retry.');

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return $this->redirectWithNotifications(
            $this->context->link->getAdminLink('AdminAIEmailConversations')
        );
    }

    public function processExport($text_delimiter = '"')
    {
        try {
            $query = new DbQuery();
            $query->select('*')
                  ->from($this->table)
                  ->orderBy('date_add DESC');

            $conversations = Db::getInstance()->executeS($query);

            if (!$conversations) {
                throw new Exception($this->l('No conversations to export.'));
            }

            $filename = 'conversations_export_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Write headers with text delimiter
            fputcsv($output, array_keys($conversations[0]), ',', $text_delimiter);
            
            // Write data with text delimiter
            foreach ($conversations as $row) {
                fputcsv($output, $row, ',', $text_delimiter);
            }
            
            fclose($output);
            exit();

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return $this->redirectWithNotifications(
                $this->context->link->getAdminLink('AdminAIEmailConversations')
            );
        }
    }

    public function ajaxProcessGetMessagePreview()
    {
        $message = Tools::getValue('message');
        die(json_encode(['content' => nl2br(htmlspecialchars($message))]));
    }
}