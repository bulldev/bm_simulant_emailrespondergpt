<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

// Require the new API Monitor class
require_once dirname(__FILE__) . '/EmailAIAPIMonitor.php';

class EmailAIResponder
{
    // [All previous methods remain the same]

    /**
     * Generate AI response using OpenAI with enhanced monitoring
     */
    private function generateAIResponse(string $message): string
    {
        // Check rate limits before processing
        if (!EmailAIAPIMonitor::checkDailyTokenUsage() || 
            !EmailAIAPIMonitor::checkHourlyAPICallRate()) {
            throw new Exception('API Usage limits exceeded. Please try again later.');
        }

        $openai_api_key = $this->getConfig('SIMULANT_AI_OPENAI_KEY');
        if (!$openai_api_key) {
            throw new Exception('OpenAI API key not configured');
        }

        $tone = $this->getConfig('SIMULANT_AI_TONE', 'professional');
        $custom_instructions = $this->getConfig('SIMULANT_AI_CUSTOM_INSTRUCTIONS', '');
        $max_retries = 3;
        $retry_delay = 1;

        $data = [
            'model' => $this->getConfig('SIMULANT_AI_MODEL', 'gpt-4'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are responding in a $tone tone. $custom_instructions"
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ],
            ],
            'temperature' => (float) $this->getConfig('SIMULANT_AI_TEMPERATURE', '0.7'),
            'max_tokens' => (int) $this->getConfig('SIMULANT_AI_MAX_TOKENS', '500')
        ];

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $start_time = microtime(true);
            try {
                $response = $this->makeOpenAIRequest($openai_api_key, $data);
                $end_time = microtime(true);
                
                // Log API usage
                EmailAIAPIMonitor::logAPIUsage(
                    'chat/completions', 
                    $data, 
                    $response, 
                    200, 
                    $end_time - $start_time
                );

                // Check usage and send alerts if necessary
                EmailAIAPIMonitor::checkUsageAndAlert();

                return $response['choices'][0]['message']['content'] ?? 
                       throw new Exception('Invalid API response format');
            } catch (Exception $e) {
                if ($attempt === $max_retries) {
                    throw $e;
                }
                sleep($retry_delay * $attempt);
            }
        }

        throw new Exception('Failed to generate AI response after ' . $max_retries . ' attempts');
    }

    /**
     * Trace and log any slow or problematic API calls
     */
    public function traceAPIPerformance(): array
    {
        return EmailAIAPIMonitor::traceSlowAPICalls();
    }
}