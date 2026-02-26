<?php
/**
 * Settings Page
 *
 * Админка: AI Content Generator → Настройки
 * Управление API-ключами, моделями, URL.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Settings {

    private static string $page_slug    = 'aicg-settings';
    private static string $menu_slug    = 'ai-content-generator';
    private static string $option_group = 'aicg_settings_group';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('wp_ajax_aicg_test_openrouter', [self::class, 'ajax_test_openrouter']);
        add_action('wp_ajax_aicg_test_supadata', [self::class, 'ajax_test_supadata']);
    }

    public static function add_menu(): void {
        // Главное меню
        add_menu_page(
            'AI Content Generator',
            'AI Content',
            'manage_options',
            self::$menu_slug,
            [AICG_Generator_Page::class, 'render_page'],
            'dashicons-edit-large',
            30
        );

        // Подменю: Генератор (дубль главной)
        add_submenu_page(
            self::$menu_slug,
            'Генератор статей',
            'Генератор',
            'edit_posts',
            self::$menu_slug,
            [AICG_Generator_Page::class, 'render_page']
        );

        // Подменю: Настройки
        add_submenu_page(
            self::$menu_slug,
            'Настройки API',
            'Настройки',
            'manage_options',
            self::$page_slug,
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        $p = AICG_OPTION_PREFIX;

        // ── OpenRouter ────────────────────────────────────────────
        add_settings_section('aicg_openrouter', 'OpenRouter API', function () {
            echo '<p>Единый ключ для всех LLM-моделей через <a href="https://openrouter.ai" target="_blank">openrouter.ai</a></p>';
        }, self::$page_slug);

        self::add_field($p . 'openrouter_api_key', 'API Key', 'password', self::$page_slug, 'aicg_openrouter',
            'Получите на <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a>');
        self::add_field($p . 'openrouter_base_url', 'Base URL', 'url', self::$page_slug, 'aicg_openrouter',
            'По умолчанию: https://openrouter.ai/api/v1');

        // ── Модели ────────────────────────────────────────────────
        add_settings_section('aicg_models', 'Модели (OpenRouter IDs)', function () {
            echo '<p>Укажите ID моделей OpenRouter для каждой задачи.</p>';
        }, self::$page_slug);

        self::add_field($p . 'model_claude', 'Claude (план, генерация, хуманизация)', 'text', self::$page_slug, 'aicg_models');
        self::add_field($p . 'model_gpt', 'GPT (ресёрч, HTML)', 'text', self::$page_slug, 'aicg_models');
        self::add_field($p . 'model_gemini', 'Gemini (факт-чек)', 'text', self::$page_slug, 'aicg_models');

        // ── Supadata ──────────────────────────────────────────────
        add_settings_section('aicg_supadata', 'Supadata (YouTube транскрипция)', function () {
            echo '<p>API для транскрипции YouTube видео — <a href="https://supadata.ai" target="_blank">supadata.ai</a></p>';
        }, self::$page_slug);

        self::add_field($p . 'supadata_api_key', 'API Key', 'password', self::$page_slug, 'aicg_supadata',
            'Получите на <a href="https://supadata.ai/dashboard" target="_blank">supadata.ai/dashboard</a>');
        self::add_field($p . 'supadata_base_url', 'Base URL', 'url', self::$page_slug, 'aicg_supadata');
        self::add_select_field($p . 'supadata_default_lang', 'Язык по умолчанию', self::$page_slug, 'aicg_supadata', [
            'ru' => 'Русский (ru)',
            'en' => 'English (en)',
            'uk' => 'Українська (uk)',
            'de' => 'Deutsch (de)',
            'fr' => 'Français (fr)',
            'es' => 'Español (es)',
        ]);
    }

    // ── Хелпер: текстовое поле ──────────────────────────────────

    private static function add_field(string $option, string $label, string $type, string $page, string $section, string $desc = ''): void {
        register_setting(self::$option_group, $option, [
            'type'              => 'string',
            'sanitize_callback' => $type === 'url' ? 'esc_url_raw' : 'sanitize_text_field',
        ]);

        add_settings_field($option, $label, function () use ($option, $type, $desc) {
            $value = get_option($option, '');
            printf(
                '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" autocomplete="off" />',
                esc_attr($type), esc_attr($option), esc_attr($option), esc_attr($value)
            );
            if ($desc) {
                printf('<p class="description">%s</p>', $desc);
            }
        }, $page, $section);
    }

    // ── Хелпер: select ──────────────────────────────────────────

    private static function add_select_field(string $option, string $label, string $page, string $section, array $choices): void {
        register_setting(self::$option_group, $option, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_field($option, $label, function () use ($option, $choices) {
            $value = get_option($option, '');
            echo '<select id="' . esc_attr($option) . '" name="' . esc_attr($option) . '">';
            foreach ($choices as $k => $v) {
                printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($value, $k, false), esc_html($v));
            }
            echo '</select>';
        }, $page, $section);
    }

    // ── Рендер страницы ─────────────────────────────────────────

    public static function render_page(): void {
        if (!current_user_can('manage_options')) return;
        include AICG_PLUGIN_DIR . 'templates/settings-page.php';
    }

    // ── AJAX: тест OpenRouter ───────────────────────────────────

    public static function ajax_test_openrouter(): void {
        check_ajax_referer('aicg_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Нет прав.');

        $client = new AICG_OpenRouter();
        $result = $client->call_claude('Ответь одним словом.', 'Привет!');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success('OpenRouter работает! Ответ: ' . mb_substr($result, 0, 100));
    }

    // ── AJAX: тест Supadata ─────────────────────────────────────

    public static function ajax_test_supadata(): void {
        check_ajax_referer('aicg_test_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Нет прав.');

        $client = new AICG_Supadata();
        if (!$client->is_configured()) {
            wp_send_json_error('API key не указан.');
            return;
        }
        wp_send_json_success('Supadata API key настроен.');
    }
}
