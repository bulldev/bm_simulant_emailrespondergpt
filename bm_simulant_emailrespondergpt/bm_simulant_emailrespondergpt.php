<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class bm_simulant_emailrespondergpt extends Module
{
    protected $_errors = array();
    private $availableTones;
    
    public function __construct()
    {
        $this->name = 'bm_simulant_emailrespondergpt';
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'Bullmade';
        $this->need_instance = 0;
        $this->bootstrap = true;
        
        // Register all controllers
        $this->controllers = array(
            'AdminAIEmailConversations',
            'AdminSimulantAIStatistics',
            'AdminSimulantAILogs'
        );

        parent::__construct();

        $this->displayName = $this->l('Simulant AI - Email Responder GPT');
        $this->description = $this->l('Automatically responds to emails using AI.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->availableTones = [
            'professional' => $this->l('Professional'),
            'casual' => $this->l('Casual'),
            'friendly' => $this->l('Friendly'),
            'formal' => $this->l('Formal'),
            'humorous' => $this->l('Humorous')
        ];

        $this->initializeDefaultConfig();
    }

    public function install()
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $this->_errors[] = $this->l('This module requires PrestaShop 1.7 or later.');
            return false;
        }

        if (!$this->checkRequirements()) {
            return false;
        }

        if (!parent::install() ||
            !$this->registerHook('actionEmailSend') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->installDatabase()) {
            return false;
        }

        return true;
    }

    private function installDatabase()
    {
        $sql = [];
        
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'email_ai_conversations` (
            `id_conversation` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `subject` varchar(255) NOT NULL,
            `received_message` text NOT NULL,
            `ai_response` text NOT NULL,
            `status` enum("pending","processed","failed") NOT NULL DEFAULT "pending",
            `detected_language` varchar(5) DEFAULT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_conversation`),
            KEY `email` (`email`),
            KEY `status` (`status`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'email_ai_logs` (
            `id_log` int(11) NOT NULL AUTO_INCREMENT,
            `log_type` enum("info","warning","error","success") NOT NULL DEFAULT "info",
            `message` text NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `log_type` (`log_type`),
            KEY `email` (`email`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'email_ai_api_logs` (
            `id_api_log` int(11) NOT NULL AUTO_INCREMENT,
            `request` text NOT NULL,
            `response` text NOT NULL,
            `status_code` int(11) NOT NULL,
            `duration` float NOT NULL,
            `tokens_used` int(11) DEFAULT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_api_log`),
            KEY `status_code` (`status_code`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        foreach ($sql as $query) {
            try {
                if (!Db::getInstance()->execute($query)) {
                    $this->_errors[] = $this->l('Error creating database tables');
                    return false;
                }
            } catch (Exception $e) {
                $this->_errors[] = $this->l('Database error: ') . $e->getMessage();
                return false;
            }
        }

        return true;
    }

    private function checkRequirements()
    {
        if (!extension_loaded('curl')) {
            $this->_errors[] = $this->l('CURL extension is required for this module.');
            return false;
        }

        if (!extension_loaded('imap')) {
            $this->_errors[] = $this->l('IMAP extension is required for this module.');
            return false;
        }

        return true;
    }

    private function initializeDefaultConfig()
    {
        $defaults = [
            'SIMULANT_AI_EMAIL_TEMPLATE' => 'Dear {customer},\n\nThank you for your message.\n\n{ai_response}\n\nBest regards,\nYour Store Team',
            'SIMULANT_AI_EMAIL_DELAY' => 5,
            'SIMULANT_AI_RANDOM_DELAY' => 0,
            'SIMULANT_AI_DEBUG_LOGGING' => 0,
            'SIMULANT_AI_TONE' => 'professional',
            'SIMULANT_AI_LOGS_RETENTION' => 7,
            'SIMULANT_AI_MODEL' => 'gpt-4',
            'SIMULANT_AI_CRON_TOKEN' => Tools::passwdGen(32),
            'SIMULANT_AI_LAST_CRON_RUN' => null,
            'SIMULANT_AI_TEMPERATURE' => 0.7,
            'SIMULANT_AI_MAX_TOKENS' => 500,
            'SIMULANT_AI_EMAIL_TEMPLATE_ID' => 0,
            'SIMULANT_AI_TRANSLATE_EMAIL' => 0
        ];

        foreach ($defaults as $key => $value) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, $value);
            }
        }
    }

    public function getContent()
    {
        $output = null;
    
        if (Tools::isSubmit('submitSimulantAISettings')) {
            $settings = array(
                'SIMULANT_AI_IMAP_HOST' => Tools::getValue('SIMULANT_AI_IMAP_HOST'),
                'SIMULANT_AI_IMAP_USER' => Tools::getValue('SIMULANT_AI_IMAP_USER'),
                'SIMULANT_AI_IMAP_PASS' => Tools::getValue('SIMULANT_AI_IMAP_PASS'),
                'SIMULANT_AI_SMTP_HOST' => Tools::getValue('SIMULANT_AI_SMTP_HOST'),
                'SIMULANT_AI_SMTP_USER' => Tools::getValue('SIMULANT_AI_SMTP_USER'),
                'SIMULANT_AI_SMTP_PASS' => Tools::getValue('SIMULANT_AI_SMTP_PASS'),
                'SIMULANT_AI_OPENAI_KEY' => Tools::getValue('SIMULANT_AI_OPENAI_KEY'),
                'SIMULANT_AI_EMAIL_DELAY' => Tools::getValue('SIMULANT_AI_EMAIL_DELAY'),
                'SIMULANT_AI_TONE' => Tools::getValue('SIMULANT_AI_TONE')
            );

            try {
                if ($this->updateSettings($settings)) {
                    $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
                }
            } catch (Exception $e) {
                $output .= $this->displayError($this->l('Error updating settings: ') . $e->getMessage());
            }
        }
    
        // Create links to other controllers
        $logsLink = $this->context->link->getAdminLink('AdminSimulantAILogs');
        $statisticsLink = $this->context->link->getAdminLink('AdminSimulantAIStatistics');
        $conversationsLink = $this->context->link->getAdminLink('AdminAIEmailConversations', true);
    
        // Create helper form
        $helper = new HelperForm();
        
        $helper->show_toolbar = false;
        $helper->table = $this->name;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Simulant AI Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => $this->getFormInputs(),
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitSimulantAISettings',
                    'class' => 'btn btn-primary pull-right'
                ],
                'custom_html' => '
                    <div class="panel">
                        <div class="panel-heading">
                            <i class="icon-link"></i> ' . $this->l('Quick Links') . '
                        </div>
                        <div class="form-wrapper">
                            <div class="row">
                                <div class="col-lg-3">
                                    <a href="' . $logsLink . '" class="btn btn-default btn-block">
                                        <i class="icon-list"></i> ' . $this->l('View Logs') . '
                                    </a>
                                </div>
                                <div class="col-lg-3">
                                    <a href="' . $statisticsLink . '" class="btn btn-default btn-block">
                                        <i class="icon-bar-chart"></i> ' . $this->l('View Statistics') . '
                                    </a>
                                </div>
                                <div class="col-lg-3">
                                    <a href="' . $conversationsLink . '" class="btn btn-default btn-block">
                                        <i class="icon-envelope"></i> ' . $this->l('Email Conversations') . '
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                '
            ]
        ];
        
        $helper->fields_value = $this->getConfigurationValues();
        
        $output .= $this->generateFormWithCustomHTML($helper, $fields_form);
        
        return $output;
    }

    protected function generateFormWithCustomHTML($helper, $fields_form)
    {
        ob_start();
        echo $helper->generateForm([$fields_form]);
        $form = ob_get_clean();
        $customHtml = $fields_form['form']['custom_html'];
        $form = str_replace('</form>', $customHtml . '</form>', $form);
        return $form;
    }

    protected function getFormInputs()
    {
        return [
            [
                'type' => 'text',
                'label' => $this->l('IMAP Host'),
                'name' => 'SIMULANT_AI_IMAP_HOST',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->l('IMAP server hostname')
            ],
            [
                'type' => 'text',
                'label' => $this->l('IMAP Username'),
                'name' => 'SIMULANT_AI_IMAP_USER',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'password',
                'label' => $this->l('IMAP Password'),
                'name' => 'SIMULANT_AI_IMAP_PASS',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'text',
                'label' => $this->l('SMTP Host'),
                'name' => 'SIMULANT_AI_SMTP_HOST',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->l('SMTP server hostname')
            ],
            [
                'type' => 'text',
                'label' => $this->l('SMTP Username'),
                'name' => 'SIMULANT_AI_SMTP_USER',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'password',
                'label' => $this->l('SMTP Password'),
                'name' => 'SIMULANT_AI_SMTP_PASS',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'text',
                'label' => $this->l('OpenAI API Key'),
                'name' => 'SIMULANT_AI_OPENAI_KEY',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->l('Your OpenAI API key')
            ],
            [
                'type' => 'text',
                'label' => $this->l('Response Delay (minutes)'),
                'name' => 'SIMULANT_AI_EMAIL_DELAY',
                'class' => 'fixed-width-sm',
                'required' => true,
                'desc' => $this->l('Set the delay before AI responses are sent'),
                'validation' => 'isUnsignedInt'
            ],
            [
                'type' => 'select',
                'label' => $this->l('AI Response Tone'),
                'name' => 'SIMULANT_AI_TONE',
                'options' => [
                    'query' => $this->getToneOptions(),
                    'id' => 'id',
                    'name' => 'name'
                ],
                'desc' => $this->l('Choose the tone for AI-generated responses')
            ]
        ];
    }

    protected function getToneOptions()
    {
        $options = [];
        foreach ($this->availableTones as $id => $name) {
            $options[] = ['id' => $id, 'name' => $name];
        }
        return $options;
    }

    protected function getConfigurationValues()
    {
        return [
            'SIMULANT_AI_IMAP_HOST' => Configuration::get('SIMULANT_AI_IMAP_HOST'),
            'SIMULANT_AI_IMAP_USER' => Configuration::get('SIMULANT_AI_IMAP_USER'),
            'SIMULANT_AI_SMTP_HOST' => Configuration::get('SIMULANT_AI_SMTP_HOST'),
            'SIMULANT_AI_SMTP_USER' => Configuration::get('SIMULANT_AI_SMTP_USER'),
            'SIMULANT_AI_OPENAI_KEY' => Configuration::get('SIMULANT_AI_OPENAI_KEY'),
            'SIMULANT_AI_EMAIL_DELAY' => Configuration::get('SIMULANT_AI_EMAIL_DELAY', 5),
            'SIMULANT_AI_TONE' => Configuration::get('SIMULANT_AI_TONE', 'professional')
        ];
    }

    public function hookDisplayBackOfficeHeader()
    {
        $controller = $this->context->controller->controller_name;
        
        if ($controller === 'AdminModules' && Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS([
                $this->_path . 'views/js/settings.js'
            ]);
            
            $this->context->controller->addCSS([
                $this->_path . 'views/css/settings.css'
            ]);
        }
    }

    public function hookActionEmailSend($params)
    {
        try {
            if (!isset($params['template']) || !isset($params['email'])) {
                throw new Exception('Invalid email parameters');
            }

            if (!$this->isModuleConfigured()) {
                throw new Exception('Module not fully configured');
            }

            $emailResponder = new EmailAIResponder($this);
            return $emailResponder->processEmail($params);

        } catch (Exception $e) {
            $this->logError($e->getMessage(), 'EmailSend');
            return false;
        }
    }

    public function uninstall()
    {
        return parent::uninstall() && 
               $this->uninstallDatabase() && 
               $this->deleteConfiguration() &&
               $this->clearModuleCache();
    }

    private function uninstallDatabase()
    {
        $tables = [
            'email_ai_conversations',
            'email_ai_logs',
            'email_ai_api_logs'
        ];

        foreach ($tables as $table) {
            try {
                Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
            } catch (Exception $e) {
                $this->_errors[] = $this->l('Error dropping table: ') . $table;
                return false;
            }
        }

        return true;
    }

    private function deleteConfiguration()
    {
        $configs = [
            'SIMULANT_AI_EMAIL_TEMPLATE',
            'SIMULANT_AI_EMAIL_DELAY',
            'SIMULANT_AI_RANDOM_DELAY',
            'SIMULANT_AI_DEBUG_LOGGING',
            'SIMULANT_AI_TONE',
            'SIMULANT_AI_LOGS_RETENTION',
            'SIMULANT_AI_MODEL',
            'SIMULANT_AI_CRON_TOKEN',
            'SIMULANT_AI_OPENAI_KEY',
            'SIMULANT_AI_IMAP_HOST',
            'SIMULANT_AI_IMAP_USER',
            'SIMULANT_AI_IMAP_PASS',
            'SIMULANT_AI_SMTP_HOST',
            'SIMULANT_AI_SMTP_USER',
            'SIMULANT_AI_SMTP_PASS',
            'SIMULANT_AI_LAST_CRON_RUN',
            'SIMULANT_AI_TEMPERATURE',
            'SIMULANT_AI_MAX_TOKENS',
            'SIMULANT_AI_EMAIL_TEMPLATE_ID',
            'SIMULANT_AI_TRANSLATE_EMAIL'
        ];

        foreach ($configs as $config) {
            Configuration::deleteByName($config);
        }

        return true;
    }

    protected function clearModuleCache()
    {
        $cacheKeys = [
            'bm_simulant_emailrespondergpt_data',
            'bm_simulant_emailrespondergpt_stats',
            'bm_simulant_emailrespondergpt_logs'
        ];

        foreach ($cacheKeys as $key) {
            if (Cache::isStored($key)) {
                Cache::clean($key);
            }
        }

        return true;
    }

    public function isModuleConfigured()
    {
        return !empty(Configuration::get('SIMULANT_AI_IMAP_HOST'))
            && !empty(Configuration::get('SIMULANT_AI_IMAP_USER'))
            && !empty(Configuration::get('SIMULANT_AI_IMAP_PASS'))
            && !empty(Configuration::get('SIMULANT_AI_SMTP_HOST'))
            && !empty(Configuration::get('SIMULANT_AI_SMTP_USER'))
            && !empty(Configuration::get('SIMULANT_AI_SMTP_PASS'))
            && !empty(Configuration::get('SIMULANT_AI_OPENAI_KEY'));
    }

    public function getConfigValue($key, $default = '')
    {
        return Configuration::get($key) ?: $default;
    }

    public function logError($message, $context = '')
    {
        PrestaShopLogger::addLog(
            sprintf('[%s] %s', $context ?: $this->name, $message),
            3,
            null,
            $this->name
        );
    }

    public function getAvailableTones()
    {
        return $this->availableTones;
    }

    public function validateSettings($settings)
    {
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'SIMULANT_AI_EMAIL_DELAY':
                    if (!Validate::isUnsignedInt($value) || $value < 0 || $value > 1440) {
                        throw new Exception($this->l('Email delay must be between 0 and 1440 minutes'));
                    }
                    break;

                case 'SIMULANT_AI_IMAP_HOST':
                case 'SIMULANT_AI_SMTP_HOST':
                    if (!Validate::isUrl($value)) {
                        throw new Exception(sprintf($this->l('Invalid %s hostname'), strpos($key, 'IMAP') !== false ? 'IMAP' : 'SMTP'));
                    }
                    break;

                case 'SIMULANT_AI_OPENAI_KEY':
                    if (!preg_match('/^sk-proj-[a-zA-Z0-9]+$/', $value)) {
                        throw new Exception($this->l('Invalid OpenAI API key format'));
                    }
                    break;

                case 'SIMULANT_AI_TEMPERATURE':
                    if (!is_numeric($value) || $value < 0 || $value > 1) {
                        throw new Exception($this->l('Temperature must be between 0 and 1'));
                    }
                    break;

                case 'SIMULANT_AI_MAX_TOKENS':
                    if (!Validate::isUnsignedInt($value) || $value < 1 || $value > 4000) {
                        throw new Exception($this->l('Max tokens must be between 1 and 4000'));
                    }
                    break;
            }
        }

        return true;
    }

    public function updateSettings($settings)
    {
        try {
            $this->validateSettings($settings);
            
            foreach ($settings as $key => $value) {
                Configuration::updateValue($key, $value);
            }
            
            $this->clearModuleCache();
            return true;
            
        } catch (Exception $e) {
            $this->logError($e->getMessage(), 'Settings');
            throw $e;
        }
    }

    public function testConnections()
    {
        try {
            // Test IMAP
            $imap_host = Configuration::get('SIMULANT_AI_IMAP_HOST');
            $imap_user = Configuration::get('SIMULANT_AI_IMAP_USER');
            $imap_pass = Configuration::get('SIMULANT_AI_IMAP_PASS');

            $imap = @imap_open(
                '{' . $imap_host . ':143/imap}INBOX',
                $imap_user,
                $imap_pass,
                OP_HALFOPEN | OP_SILENT
            );

            if (!$imap) {
                throw new Exception($this->l('IMAP Connection failed: ') . imap_last_error());
            }
            imap_close($imap);

            // Test SMTP
            $smtp_host = Configuration::get('SIMULANT_AI_SMTP_HOST');
            $smtp_user = Configuration::get('SIMULANT_AI_SMTP_USER');
            $smtp_pass = Configuration::get('SIMULANT_AI_SMTP_PASS');

            $smtp = new PHPMailer(true);
            $smtp->isSMTP();
            $smtp->Host = $smtp_host;
            $smtp->SMTPAuth = true;
            $smtp->Username = $smtp_user;
            $smtp->Password = $smtp_pass;
            $smtp->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $smtp->Port = 587;

            if (!$smtp->smtpConnect()) {
                throw new Exception($this->l('SMTP Connection failed'));
            }
            $smtp->smtpClose();

            // Test OpenAI
            $openai_key = Configuration::get('SIMULANT_AI_OPENAI_KEY');
            $this->testOpenAIConnection($openai_key);

            return true;

        } catch (Exception $e) {
            $this->logError($e->getMessage(), 'ConnectionTest');
            throw $e;
        }
    }

    private function testOpenAIConnection($api_key)
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test connection']
                ],
                'max_tokens' => 5
            ]),
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception($this->l('OpenAI API connection failed. HTTP code: ') . $http_code);
        }

        $response_data = json_decode($response, true);
        if (!isset($response_data['choices'][0]['message'])) {
            throw new Exception($this->l('Invalid response from OpenAI API'));
        }
    }
}