<?php

class EmailAILogger 
{
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_SUCCESS = 'success';

    /**
     * Log a message to the database and optional file
     * 
     * @param string $message Log message
     * @param string $type Log type (info, warning, error, success)
     * @param string|null $email Related email address
     * @param bool $logToFile Whether to log to file as well
     */
    public static function log(
        string $message, 
        string $type = self::TYPE_INFO, 
        ?string $email = null, 
        bool $logToFile = false
    ): bool {
        try {
            // Validate input
            if (!in_array($type, [self::TYPE_INFO, self::TYPE_WARNING, self::TYPE_ERROR, self::TYPE_SUCCESS])) {
                $type = self::TYPE_INFO;
            }

            // Database logging
            $result = Db::getInstance()->insert('email_ai_logs', [
                'log_type' => pSQL($type),
                'message' => pSQL($message),
                'email' => $email ? pSQL($email) : null,
                'date_add' => date('Y-m-d H:i:s')
            ]);

            // Optional file logging
            if ($logToFile) {
                self::logToFile($message, $type, $email);
            }

            return $result;

        } catch (Exception $e) {
            // Fallback error logging
            error_log('EmailAILogger Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log to file with more detailed information
     * 
     * @param string $message Log message
     * @param string $type Log type
     * @param string|null $email Related email
     */
    private static function logToFile(string $message, string $type, ?string $email = null): void
    {
        $logDir = _PS_MODULE_DIR_ . 'bm_simulant_emailrespondergpt/logs/';
        $logFile = $logDir . $type . '_' . date('Y-m-d') . '.log';

        // Ensure log directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}][{$type}]" . 
                    ($email ? "[{$email}]" : "") . 
                    " {$message}\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Create a detailed error log for complex scenarios
     * 
     * @param Exception $exception The exception to log
     * @param array $context Additional context information
     */
    public static function logException(Exception $exception, array $context = []): void
    {
        $errorMessage = sprintf(
            "Exception: %s\nCode: %d\nFile: %s\nLine: %d\nTrace: %s", 
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        // Add context information if provided
        if (!empty($context)) {
            $errorMessage .= "\nContext: " . json_encode($context);
        }

        self::log($errorMessage, self::TYPE_ERROR);
    }
}