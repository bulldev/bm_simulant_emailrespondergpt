<?php

// Prevent timeouts for long-running processes
set_time_limit(0);
ini_set('memory_limit', '256M');

// Load PrestaShop configuration
$dir = dirname(__FILE__);
require_once($dir . '/../../config/config.inc.php');
require_once($dir . '/../../init.php');

// Security checks
if (!defined('_PS_VERSION_')) {
    exit('Direct access denied');
}

// Check if module is installed and active
if (!Module::isInstalled('bm_simulant_emailrespondergpt') || !Module::isEnabled('bm_simulant_emailrespondergpt')) {
    error_log("[CRON ERROR] Module is not installed or not active");
    die('Module is not installed or not active');
}

// Load required classes
require_once($dir . '/bm_simulant_emailrespondergpt.php');
require_once($dir . '/classes/EmailAIResponder.php');

// Initialize logging
$logFile = _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/logs/cron_' . date('Y-m-d') . '.log';
$debugMode = (bool)Configuration::get('SIMULANT_AI_DEBUG_LOGGING', false);

function logMessage($message, $level = 'INFO') {
    global $logFile, $debugMode;
    
    if (!$debugMode && $level === 'DEBUG') {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$level] $message" . PHP_EOL;
    
    error_log($logMessage);
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    logMessage("Starting Simulant AI Email Responder cron job");

    // Create lock file to prevent concurrent execution
    $lockFile = _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/cron.lock';
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        if (time() - $lockTime < 3600) { // Lock expires after 1 hour
            logMessage("Another cron job is still running", "WARNING");
            exit;
        }
    }
    touch($lockFile);

    // Initialize module and responder
    $module = Module::getInstanceByName('bm_simulant_emailrespondergpt');
    if (!$module) {
        throw new Exception("Could not initialize module");
    }

    $emailResponder = new EmailAIResponder($module);

    // Process scheduled responses
    logMessage("Processing scheduled responses", "DEBUG");
    $emailResponder->processScheduledResponses();

    // Check inbox for new emails
    logMessage("Checking inbox for new emails", "DEBUG");
    $emailResponder->checkInbox();

    // Clean up old logs if debug mode is disabled
    if (!$debugMode) {
        $oldLogs = glob(_PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/logs/cron_*.log');
        foreach ($oldLogs as $log) {
            if (filemtime($log) < strtotime('-7 days')) {
                unlink($log);
            }
        }
    }

    logMessage("Cron job completed successfully");

} catch (Exception $e) {
    logMessage("Error during cron execution: " . $e->getMessage(), "ERROR");
    
    // Send alert email to admin if configured
    if (Configuration::get('SIMULANT_AI_ALERT_EMAIL')) {
        Mail::Send(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            'alert',
            'AI Email Responder Cron Error',
            ['message' => $e->getMessage()],
            Configuration::get('SIMULANT_AI_ALERT_EMAIL')
        );
    }
    
} finally {
    // Always remove lock file
    if (isset($lockFile) && file_exists($lockFile)) {
        unlink($lockFile);
    }
}