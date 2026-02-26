<?php
/**
 * Supadata API Client
 *
 * Транскрипция YouTube видео через Supadata.ai.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Supadata {

    private string $api_key;
    private string $base_url;
    private string $default_lang;

    public function __construct() {
        $this->api_key      = get_option(AICG_OPTION_PREFIX . 'supadata_api_key', '');
        $this->base_url     = rtrim(get_option(AICG_OPTION_PREFIX . 'supadata_base_url', 'https://api.supadata.ai/v1'), '/');
        $this->default_lang = get_option(AICG_OPTION_PREFIX . 'supadata_default_lang', 'ru');
    }

    public function is_configured(): bool {
        return !empty($this->api_key);
    }

    /**
     * Транскрибировать YouTube видео.
     *
     * @return array|WP_Error ['text' => '...']
     */
    public function transcribe(string $youtube_url, string $lang = ''): array|WP_Error {
        if (!$this->is_configured()) {
            return new WP_Error('supadata_not_configured', 'Supadata API key не настроен.');
        }

        $url = filter_var($youtube_url, FILTER_VALIDATE_URL);
        if (!$url || (!str_contains($url, 'youtube.com') && !str_contains($url, 'youtu.be'))) {
            return new WP_Error('invalid_youtube_url', 'Невалидная ссылка на YouTube.');
        }

        $lang = $lang ?: $this->default_lang;

        $response = wp_remote_post($this->base_url . '/youtube/transcript', [
            'timeout' => 180,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key'    => $this->api_key,
            ],
            'body' => wp_json_encode([
                'url'  => $url,
                'lang' => $lang,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('supadata_request_failed', 'Ошибка соединения: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $err = $data['message'] ?? $body;
            return new WP_Error('supadata_api_error', 'Supadata: ' . $err);
        }

        return ['text' => $this->parse_transcript($data)];
    }

    private function parse_transcript($data): string {
        if (!is_array($data)) {
            return (string) $data;
        }
        if (isset($data['content'])) return $data['content'];
        if (isset($data['text']))    return $data['text'];
        if (isset($data[0]['text'])) return implode(' ', array_column($data, 'text'));
        return wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
