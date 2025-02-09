<?php

class EmailAIAPIMonitor 
{
    const MAX_DAILY_TOKENS = 100000; // OpenAI token limit
    const MAX_API_CALLS_PER_HOUR = 50;

    /**
     * Log API usage
     */
    public static function logAPIUsage(string $endpoint, array $request, array $response, int $statusCode, float $executionTime): bool
    {
        try {
            // Calculate token usage if available
            $tokenUsage = isset($response['usage']['total_tokens']) ? $response['usage']['total_tokens'] : 0;

            $result = Db::getInstance()->insert('email_ai_api_logs', [
                'endpoint' => pSQL($endpoint),
                'request' => pSQL(json_encode($request)),
                'response' => pSQL(json_encode($response)),
                'status_code' => (int)$statusCode,
                'execution_time' => (float)$executionTime,
                'date_add' => date('Y-m-d H:i:s')
            ]);

            return $result;
        } catch (Exception $e) {
            error_log('API Usage Logging Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check daily token usage
     */
    public static function checkDailyTokenUsage(): bool
    {
        $today = date('Y-m-d');
        $totalTokens = (int)Db::getInstance()->getValue(
            "SELECT SUM(JSON_EXTRACT(response, '$.usage.total_tokens')) 
             FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
             WHERE DATE(date_add) = '" . pSQL($today) . "'"
        );

        return $totalTokens <= self::MAX_DAILY_TOKENS;
    }

    /**
     * Check hourly API call rate
     */
    public static function checkHourlyAPICallRate(): bool
    {
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $apiCallCount = (int)Db::getInstance()->getValue(
            "SELECT COUNT(*) 
             FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
             WHERE date_add >= '" . pSQL($oneHourAgo) . "'"
        );

        return $apiCallCount < self::MAX_API_CALLS_PER_HOUR;
    }

    /**
     * Send admin alert if usage is high
     */
    public static function checkUsageAndAlert(): void
    {
        $today = date('Y-m-d');
        $totalTokens = (int)Db::getInstance()->getValue(
            "SELECT SUM(JSON_EXTRACT(response, '$.usage.total_tokens')) 
             FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
             WHERE DATE(date_add) = '" . pSQL($today) . "'"
        );

        $apiCallCount = (int)Db::getInstance()->getValue(
            "SELECT COUNT(*) 
             FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
             WHERE DATE(date_add) = '" . pSQL($today) . "'"
        );

        $alertEmail = Configuration::get('SIMULANT_AI_ALERT_EMAIL');
        if ($alertEmail) {
            if ($totalTokens > (self::MAX_DAILY_TOKENS * 0.8)) {
                Mail::Send(
                    Configuration::get('PS_LANG_DEFAULT'),
                    'high_usage_alert',
                    'High OpenAI API Usage Alert',
                    [
                        '{tokens_used}' => $totalTokens,
                        '{max_tokens}' => self::MAX_DAILY_TOKENS,
                        '{api_calls}' => $apiCallCount
                    ],
                    $alertEmail
                );
            }
        }
    }

    /**
     * Trace expensive or slow API calls
     */
    public static function traceSlowAPICalls(): array
    {
        return Db::getInstance()->executeS(
            "SELECT * FROM `" . _DB_PREFIX_ . "email_ai_api_logs` 
             WHERE execution_time > 5 
             ORDER BY execution_time DESC 
             LIMIT 10"
        );
    }
}