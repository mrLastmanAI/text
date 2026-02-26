<?php
/**
 * Generator Admin Page
 *
 * Основная страница генератора статей в админке WordPress.
 * 4-шаговый визард: Подготовка → Генерация → Факт-чек → Хуманизация
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Generator_Page {

    public static function init(): void {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Подключение CSS и JS только на страницах плагина.
     */
    public static function enqueue_assets(string $hook): void {
        // Подключаем на всех страницах нашего плагина
        if (!str_contains($hook, 'ai-content-generator') && !str_contains($hook, 'aicg-')) {
            return;
        }

        wp_enqueue_style(
            'aicg-generator',
            AICG_PLUGIN_URL . 'css/generator.css',
            [],
            AICG_VERSION
        );

        wp_enqueue_script(
            'aicg-generator',
            AICG_PLUGIN_URL . 'js/generator.js',
            [],
            AICG_VERSION,
            true
        );

        wp_localize_script('aicg-generator', 'aicgConfig', [
            'restUrl'  => rest_url('aicg/v1/'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
        ]);
    }

    /**
     * Рендер главной страницы генератора.
     */
    public static function render_page(): void {
        if (!current_user_can('edit_posts')) {
            wp_die('Нет прав для доступа к этой странице.');
        }
        include AICG_PLUGIN_DIR . 'templates/generator-page.php';
    }
}
