<?php
/**
 * OpenRouter API Client
 *
 * Единый клиент для вызова Claude, GPT, Gemini через OpenRouter.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_OpenRouter {

    private string $api_key;
    private string $base_url;
    private array  $models;

    public function __construct() {
        $this->api_key  = get_option(AICG_OPTION_PREFIX . 'openrouter_api_key', '');
        $this->base_url = rtrim(get_option(AICG_OPTION_PREFIX . 'openrouter_base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->models   = [
            'claude' => get_option(AICG_OPTION_PREFIX . 'model_claude', 'anthropic/claude-sonnet-4-5-20250929'),
            'gpt'    => get_option(AICG_OPTION_PREFIX . 'model_gpt', 'openai/gpt-4o'),
            'gemini' => get_option(AICG_OPTION_PREFIX . 'model_gemini', 'google/gemini-2.0-flash-001'),
        ];
    }

    public function is_configured(): bool {
        return !empty($this->api_key);
    }

    /**
     * Вызов LLM через OpenRouter.
     *
     * @param string $model   Ключ: 'claude', 'gpt', 'gemini'
     * @param string $system  System prompt
     * @param string $user    User message
     * @param int    $max_tokens
     * @return string|WP_Error
     */
    public function call(string $model, string $system, string $user, int $max_tokens = 8192): string|WP_Error {
        if (!$this->is_configured()) {
            return new WP_Error('openrouter_not_configured', 'OpenRouter API key не настроен.');
        }

        $model_id = $this->models[$model] ?? null;
        if (!$model_id) {
            return new WP_Error('invalid_model', "Модель '{$model}' не найдена в настройках.");
        }

        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'timeout' => 120,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => wp_json_encode([
                'model'      => $model_id,
                'messages'   => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'max_tokens' => $max_tokens,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('openrouter_request_failed', 'Ошибка соединения: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $err = $data['error']['message'] ?? $body;
            return new WP_Error('openrouter_api_error', "OpenRouter ({$model_id}): {$err}");
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Удобные алиасы.
     */
    public function call_claude(string $system, string $user): string|WP_Error {
        return $this->call('claude', $system, $user);
    }

    public function call_gpt(string $system, string $user): string|WP_Error {
        return $this->call('gpt', $system, $user);
    }

    public function call_gemini(string $prompt): string|WP_Error {
        return $this->call('gemini', 'Ты — полезный AI-ассистент.', $prompt);
    }
}
