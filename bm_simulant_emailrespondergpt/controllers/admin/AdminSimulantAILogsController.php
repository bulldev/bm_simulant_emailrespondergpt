<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminSimulantAILogsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'email_ai_logs';
        $this->className = 'EmailAILog';
        $this->identifier = 'id_log';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->list_no_link = true;

        parent::__construct();

        // Define the fields for the list
        $this->fields_list = [
            'id_log' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'log_type' => [
                'title' => $this->l('Type'),
                'align' => 'center',
                'type' => 'select',
                'list' => [
                    'info' => $this->l('Info'),
                    'warning' => $this->l('Warning'),
                    'error' => $this->l('Error'),
                    'success' => $this->l('Success')
                ],
                'filter_key' => 'a!log_type',
                'callback' => 'getLogTypeBadge'
            ],
            'message' => [
                'title' => $this->l('Message'),
                'callback' => 'getFormattedMessage'
            ],
            'email' => [
                'title' => $this->l('Email'),
                'filter_key' => 'a!email'
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ]
        ];

        // Add bulk actions
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

        // Add filters
        $this->_select = 'a.*';
        $this->_join = '';
        $this->_where = '';
        $this->_orderBy = 'date_add';
        $this->_orderWay = 'DESC';
    }

    public function initContent()
    {
        parent::initContent();

        // Add navigation
        $this->context->smarty->assign([
            'current_url' => $this->context->link->getAdminLink('AdminSimulantAILogs'),
            'clear_logs_url' => $this->context->link->getAdminLink('AdminSimulantAILogs') . '&action=clearLogs',
            'export_logs_url' => $this->context->link->getAdminLink('AdminSimulantAILogs') . '&action=exportLogs',
            'download_url' => $this->context->link->getAdminLink('AdminSimulantAILogs') . '&action=downloadLog&id='
        ]);
    }

    public function initToolbar()
    {
        parent::initToolbar();

        // Add Clear Logs button
        $this->page_header_toolbar_btn['clear_logs'] = [
            'href' => $this->context->link->getAdminLink('AdminSimulantAILogs') . '&action=clearLogs',
            'desc' => $this->l('Clear Logs'),
            'icon' => 'process-icon-eraser'
        ];

        // Add Export All button
        $this->page_header_toolbar_btn['export_logs'] = [
            'href' => $this->context->link->getAdminLink('AdminSimulantAILogs') . '&action=exportLogs',
            'desc' => $this->l('Export All'),
            'icon' => 'process-icon-download'
        ];
    }

    public function renderList()
    {
        // Add CSS for log styling
        $this->addCSS(_PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/css/logs.css');

        return parent::renderList();
    }

    public function getFormattedMessage($message, $row)
    {
        $maxLength = 100;
        $formattedMessage = htmlspecialchars($message);
        
        if (strlen($message) > $maxLength) {
            $shortMessage = substr($formattedMessage, 0, $maxLength) . '...';
            return '<span class="log-message" title="' . $formattedMessage . '">' . $shortMessage . '</span>';
        }
        
        return '<span class="log-message">' . $formattedMessage . '</span>';
    }

    public function getLogTypeBadge($type)
    {
        $badges = [
            'info' => 'badge-info',
            'warning' => 'badge-warning',
            'error' => 'badge-danger',
            'success' => 'badge-success'
        ];

        $class = isset($badges[$type]) ? $badges[$type] : 'badge-default';
        return '<span class="badge ' . $class . '">' . $type . '</span>';
    }

    public function postProcess()
    {
        if (Tools::isSubmit('clearLogs')) {
            $this->clearLogs();
        } elseif (Tools::isSubmit('exportLogs')) {
            $this->exportLogs();
        } elseif (Tools::isSubmit('downloadLog')) {
            $this->downloadLog(Tools::getValue('id'));
        }
        
        return parent::postProcess();
    }

    protected function clearLogs()
    {
        try {
            // Keep last 7 days of logs by default
            $daysToKeep = Configuration::get('SIMULANT_AI_LOGS_RETENTION', 7);
            $date = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
            
            $result = Db::getInstance()->execute(
                "DELETE FROM `" . _DB_PREFIX_ . "email_ai_logs` 
                 WHERE date_add < '" . pSQL($date) . "'"
            );

            if ($result) {
                $this->confirmations[] = $this->l('Logs cleared successfully');
            } else {
                $this->errors[] = $this->l('Error clearing logs');
            }
        } catch (Exception $e) {
            $this->errors[] = $this->l('Error clearing logs: ') . $e->getMessage();
        }
    }

    protected function exportLogs()
    {
        try {
            $logs = Db::getInstance()->executeS(
                "SELECT * FROM `" . _DB_PREFIX_ . "email_ai_logs` 
                 ORDER BY date_add DESC"
            );

            if (!$logs) {
                throw new Exception($this->l('No logs to export'));
            }

            $filename = 'simulant_ai_logs_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, array_keys($logs[0]));
            
            // Add data
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
            
            fclose($output);
            exit();

        } catch (Exception $e) {
            $this->errors[] = $this->l('Error exporting logs: ') . $e->getMessage();
        }
    }

    protected function downloadLog($id)
    {
        try {
            $log = new EmailAILog($id);
            
            if (!Validate::isLoadedObject($log)) {
                throw new Exception($this->l('Log not found'));
            }

            $filename = 'log_' . $id . '_' . date('Y-m-d_H-i-s') . '.txt';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo "Log ID: " . $log->id . "\n";
            echo "Type: " . $log->log_type . "\n";
            echo "Date: " . $log->date_add . "\n";
            echo "Email: " . $log->email . "\n";
            echo "Message:\n" . $log->message;
            
            exit();

        } catch (Exception $e) {
            $this->errors[] = $this->l('Error downloading log: ') . $e->getMessage();
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // Add custom JS and CSS
        $this->addJS([
            _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/js/logs.js'
        ]);
        
        $this->addCSS([
            _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/css/logs.css'
        ]);
    }
}