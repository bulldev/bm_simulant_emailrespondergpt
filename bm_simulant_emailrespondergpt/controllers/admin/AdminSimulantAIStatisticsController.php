<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminSimulantAIStatisticsController extends ModuleAdminController
{
    protected $dateFrom;
    protected $dateTo;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'email_ai_conversations';
        $this->className = 'EmailAIConversation';
        $this->lang = false;
        $this->addRowAction('view');
        
        parent::__construct();

        // Initialize date range (last 30 days by default)
        $this->dateFrom = Tools::getValue('date_from', date('Y-m-d', strtotime('-30 days')));
        $this->dateTo = Tools::getValue('date_to', date('Y-m-d'));
    }

    public function initContent()
    {
        parent::initContent();
        $this->content = $this->renderStatistics();
        $this->context->smarty->assign([
            'content' => $this->content
        ]);
    }

    public function renderStatistics()
    {
        try {
            // Validate date inputs
            if (!Validate::isDate($this->dateFrom) || !Validate::isDate($this->dateTo)) {
                throw new Exception($this->l('Invalid date range'));
            }

            $stats = array_merge(
                $this->getBasicStats(),
                $this->getPerformanceStats(),
                $this->getTimeStats(),
                $this->getLanguageStats()
            );

            // Add filter form
            $stats['dateFrom'] = $this->dateFrom;
            $stats['dateTo'] = $this->dateTo;
            $stats['current_url'] = $this->context->link->getAdminLink('AdminSimulantAIStatistics');

            $this->context->smarty->assign($stats);
            
            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/templates/admin/statistics.tpl'
            );

        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'Statistics Error: ' . $e->getMessage(),
                3,
                null,
                'EmailResponderGPT'
            );
            return $this->displayError($this->l('Error loading statistics: ') . $e->getMessage());
        }
    }

    protected function getBasicStats()
    {
        $db = Db::getInstance();
        
        $dateFilter = "WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'";
        
        return [
            'totalEmailsAnswered' => (int)$db->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
                {$dateFilter}"
            ),
            'totalAIResponses' => (int)$db->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "email_ai_scheduled_responses` 
                {$dateFilter} AND status = 'sent'"
            ),
            'pendingResponses' => (int)$db->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "email_ai_scheduled_responses` 
                WHERE status = 'pending'"
            ),
            'failedResponses' => (int)$db->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "email_ai_scheduled_responses` 
                {$dateFilter} AND status = 'failed'"
            ),
            'openAICalls' => (int)$db->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
                {$dateFilter}"
            )
        ];
    }

    protected function getPerformanceStats()
    {
        $db = Db::getInstance();
        
        return [
            'averageResponseTime' => (float)$db->getValue(
                "SELECT AVG(TIMESTAMPDIFF(MINUTE, date_add, date_upd)) 
                FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
                WHERE status = 'processed' 
                AND date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'"
            ),
            'responseSuccessRate' => $this->calculateSuccessRate(),
            'dailyStats' => $this->getDailyStats(),
            'monthlyStats' => $this->getMonthlyStats()
        ];
    }

    protected function getTimeStats()
    {
        $db = Db::getInstance();
        
        return [
            'peakHours' => $db->executeS(
                "SELECT HOUR(date_add) as hour, COUNT(*) as count 
                FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
                WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'
                GROUP BY HOUR(date_add) 
                ORDER BY count DESC 
                LIMIT 5"
            ),
            'busyDays' => $db->executeS(
                "SELECT DAYNAME(date_add) as day, COUNT(*) as count 
                FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
                WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'
                GROUP BY DAYNAME(date_add) 
                ORDER BY count DESC"
            )
        ];
    }

    protected function getLanguageStats()
    {
        $db = Db::getInstance();
        
        return [
            'languageDistribution' => $db->executeS(
                "SELECT detected_language, COUNT(*) as count 
                FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
                WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'
                GROUP BY detected_language 
                ORDER BY count DESC"
            )
        ];
    }

    protected function calculateSuccessRate()
    {
        $db = Db::getInstance();
        
        $total = (int)$db->getValue(
            "SELECT COUNT(*) 
            FROM `" . _DB_PREFIX_ . "email_ai_scheduled_responses` 
            WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'"
        );
        
        $successful = (int)$db->getValue(
            "SELECT COUNT(*) 
            FROM `" . _DB_PREFIX_ . "email_ai_scheduled_responses` 
            WHERE status = 'sent' 
            AND date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'"
        );
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    protected function getDailyStats()
    {
        $db = Db::getInstance();
        return $db->executeS(
            "SELECT 
                DATE(date_add) as date, 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
            WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'
            GROUP BY DATE(date_add) 
            ORDER BY date DESC"
        );
    }

    protected function getMonthlyStats()
    {
        $db = Db::getInstance();
        return $db->executeS(
            "SELECT 
                DATE_FORMAT(date_add, '%Y-%m') as month, 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM `" . _DB_PREFIX_ . "email_ai_conversations` 
            WHERE date_add BETWEEN '{$this->dateFrom} 00:00:00' AND '{$this->dateTo} 23:59:59'
            GROUP BY DATE_FORMAT(date_add, '%Y-%m') 
            ORDER BY month DESC"
        );
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        
        // Add required JS/CSS for charts
        $this->addJS([
            _PS_JS_DIR_ . 'jquery/plugins/chart.js/chart.min.js',
            _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/js/statistics.js'
        ]);
        
        $this->addCSS([
            _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/views/css/statistics.css'
        ]);
    }
}