<?php

use PrestaShopBundle\Translation\TranslatorComponent;
use Symfony\Component\Translation\TranslatorInterface;

if (!defined('_PS_VERSION_') || !Module::getInstanceByName('bm_simulant_emailrespondergpt')) {
    exit;
}

class AdminSimulantAISettingsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        // Manually set up the translator with a fallback
        $this->translator = $this->context->getTranslator() ?? 
            new TranslatorComponent(Context::getContext()->getTranslator());
        
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = $this->trans('Simulant AI Settings', 'Modules.Simulantaiemailrespondergpt.Admin');
        $this->className = 'Configuration';
        $this->table = 'configuration';
        
        // Initialize module
        $this->module = Module::getInstanceByName('bm_simulant_emailrespondergpt');

        $this->available_tones = [
            'professional' => $this->trans('Professional', 'Modules.Simulantaiemailrespondergpt.Admin'),
            'casual' => $this->trans('Casual', 'Modules.Simulantaiemailrespondergpt.Admin'),
            'friendly' => $this->trans('Friendly', 'Modules.Simulantaiemailrespondergpt.Admin'),
            'formal' => $this->trans('Formal', 'Modules.Simulantaiemailrespondergpt.Admin'),
            'humorous' => $this->trans('Humorous', 'Modules.Simulantaiemailrespondergpt.Admin')
        ];

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function initContent()
    {
        parent::initContent();
    
        // Directly show settings form, remove dashboard and navigation
        $this->content = $this->renderForm();
        
        $this->context->smarty->assign([
            'content' => $this->content,
            'show_page_header_toolbar' => true,
            'page_header_toolbar_title' => $this->meta_title,
            'help_link' => false
        ]);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // Add CSS files
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/settings.css'
        ]);

        // Add JavaScript files
        $this->addJS([
            $this->module->getPathUri() . 'views/js/settings.js'
        ]);

        // Add translation JS vars
        Media::addJsDef([
            'simulantAjaxUrl' => $this->context->link->getAdminLink('AdminSimulantAISettings'),
            'simulantErrorText' => $this->trans('An error occurred', 'Modules.Simulantaiemailrespondergpt.Admin'),
            'simulantSuccessText' => $this->trans('Operation completed successfully', 'Modules.Simulantaiemailrespondergpt.Admin')
        ]);
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Simulant AI Settings', 'Modules.Simulantaiemailrespondergpt.Admin'),
                    'icon' => 'icon-cogs'
                ],
                'input' => $this->getFormInputs(),
                'submit' => [
                    'title' => $this->trans('Save', 'Modules.Simulantaiemailrespondergpt.Admin'),
                    'class' => 'btn btn-primary pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $this->initFormHelper($helper);
        $helper->fields_value = $this->getConfigurationValues();

        return $helper->generateForm([$fields_form]);
    }

    protected function getFormInputs()
    {
        return [
            // Email Server Configuration
            [
                'type' => 'text',
                'label' => $this->trans('IMAP Host', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_IMAP_HOST',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->trans('IMAP server hostname', 'Modules.Simulantaiemailrespondergpt.Admin')
            ],
            [
                'type' => 'text',
                'label' => $this->trans('IMAP Username', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_IMAP_USER',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'password',
                'label' => $this->trans('IMAP Password', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_IMAP_PASS',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'text',
                'label' => $this->trans('SMTP Host', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_SMTP_HOST',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->trans('SMTP server hostname', 'Modules.Simulantaiemailrespondergpt.Admin')
            ],
            [
                'type' => 'text',
                'label' => $this->trans('SMTP Username', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_SMTP_USER',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            [
                'type' => 'password',
                'label' => $this->trans('SMTP Password', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_SMTP_PASS',
                'required' => true,
                'class' => 'fixed-width-xxl'
            ],
            // OpenAI Configuration
            [
                'type' => 'text',
                'label' => $this->trans('OpenAI API Key', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_OPENAI_KEY',
                'required' => true,
                'class' => 'fixed-width-xxl',
                'desc' => $this->trans('Your OpenAI API key', 'Modules.Simulantaiemailrespondergpt.Admin')
            ],
            // Response Settings
            [
                'type' => 'text',
                'label' => $this->trans('Response Delay (minutes)', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_EMAIL_DELAY',
                'class' => 'fixed-width-sm',
                'required' => true,
                'desc' => $this->trans('Set the delay before AI responses are sent', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'validation' => 'isUnsignedInt'
            ],
            [
                'type' => 'select',
                'label' => $this->trans('AI Response Tone', 'Modules.Simulantaiemailrespondergpt.Admin'),
                'name' => 'SIMULANT_AI_TONE',
                'options' => [
                    'query' => $this->getToneOptions(),
                    'id' => 'id',
                    'name' => 'name'
                ],
                'desc' => $this->trans('Choose the tone for AI-generated responses', 'Modules.Simulantaiemailrespondergpt.Admin')
            ]
        ];
    }

    protected function getToneOptions()
    {
        $options = [];
        foreach ($this->available_tones as $id => $name) {
            $options[] = ['id' => $id, 'name' => $name];
        }
        return $options;
    }

    protected function initFormHelper($helper)
    {
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSimulantAISettings';
        $helper->currentIndex = self::$currentIndex;
        $helper->token = Tools::getAdminTokenLite('AdminSimulantAISettings');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigurationValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];
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

    public function postProcess()
    {
        if (Tools::isSubmit('submitSimulantAISettings')) {
            $this->processConfigurationUpdate();
        }
    }

    public function ajaxProcessTestConnection()
    {
        try {
            $this->module->testConnections();
            
            die(json_encode([
                'success' => true,
                'message' => $this->trans('All connections tested successfully!', 'Modules.Simulantaiemailrespondergpt.Admin')
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }

    public function ajaxProcessCheckInbox()
    {
        try {
            $emailResponder = new EmailAIResponder($this->module);
            $emailResponder->checkInbox();

            die(json_encode([
                'success' => true,
                'message' => $this->trans('Inbox check completed successfully!', 'Modules.Simulantaiemailrespondergpt.Admin')
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }

    protected function processConfigurationUpdate()
    {
        try {
            $fields = [
                'SIMULANT_AI_IMAP_HOST',
                'SIMULANT_AI_IMAP_USER',
                'SIMULANT_AI_IMAP_PASS',
                'SIMULANT_AI_SMTP_HOST',
                'SIMULANT_AI_SMTP_USER',
                'SIMULANT_AI_SMTP_PASS',
                'SIMULANT_AI_OPENAI_KEY',
                'SIMULANT_AI_EMAIL_DELAY',
                'SIMULANT_AI_TONE'
            ];

            // Validate values before saving
            $this->validateSettings($fields);

            foreach ($fields as $field) {
                $value = Tools::getValue($field);
                if ($value !== false) {
                    Configuration::updateValue($field, $value);
                }
            }

            $this->confirmations[] = $this->trans('Settings updated successfully', 'Modules.Simulantaiemailrespondergpt.Admin');
            
            // Clear module cache after update
            if (method_exists($this->module, 'clearModuleCache')) {
                $this->module->clearModuleCache();
            }

        } catch (Exception $e) {
            $this->errors[] = $this->trans('Error updating settings: ', 'Modules.Simulantaiemailrespondergpt.Admin') . $e->getMessage();
        }
    }

    protected function validateSettings($fields)
    {
        foreach ($fields as $field) {
            $value = Tools::getValue($field);
            
            switch ($field) {
                case 'SIMULANT_AI_EMAIL_DELAY':
                    if (!Validate::isUnsignedInt($value) || $value < 0 || $value > 1440) {
                        throw new Exception($this->trans('Email delay must be between 0 and 1440 minutes', 'Modules.Simulantaiemailrespondergpt.Admin'));
                    }
                    break;

                case 'SIMULANT_AI_IMAP_HOST':
                case 'SIMULANT_AI_SMTP_HOST':
                    if (empty($value)) {
                        throw new Exception(sprintf(
                            $this->trans('%s cannot be empty', 'Modules.Simulantaiemailrespondergpt.Admin'),
                            strpos($field, 'IMAP') !== false ? 'IMAP host' : 'SMTP host'
                        ));
                    }
                    break;

                case 'SIMULANT_AI_OPENAI_KEY':
                    if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $value)) {
                        throw new Exception($this->trans('Invalid OpenAI API key format', 'Modules.Simulantaiemailrespondergpt.Admin'));
                    }
                    break;

                case 'SIMULANT_AI_TONE':
                    if (!array_key_exists($value, $this->available_tones)) {
                        throw new Exception($this->trans('Invalid tone selected', 'Modules.Simulantaiemailrespondergpt.Admin'));
                    }
                    break;
            }
        }

        return true;
    }
}