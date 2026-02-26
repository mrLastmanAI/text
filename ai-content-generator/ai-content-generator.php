<?php
/**
 * Plugin Name: AI Content Generator
 * Description: Генерация статей с помощью ИИ — транскрипция YouTube, планирование, ресёрч, генерация, факт-чек, хуманизация, публикация. Все промпты редактируются из админки.
 * Version:     1.0.0
 * Author:      AI Content Generator
 * Text Domain: ai-content-generator
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AICG_VERSION', '1.0.0');
define('AICG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICG_OPTION_PREFIX', 'aicg_');

// ── Автозагрузка ────────────────────────────────────────────────
require_once AICG_PLUGIN_DIR . 'includes/class-activator.php';
require_once AICG_PLUGIN_DIR . 'includes/class-openrouter.php';
require_once AICG_PLUGIN_DIR . 'includes/class-supadata.php';
require_once AICG_PLUGIN_DIR . 'includes/class-settings.php';
require_once AICG_PLUGIN_DIR . 'includes/class-prompts.php';
require_once AICG_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once AICG_PLUGIN_DIR . 'includes/class-generator-page.php';

// ── Активация ───────────────────────────────────────────────────
register_activation_hook(__FILE__, ['AICG_Activator', 'activate']);

// ── Инициализация ───────────────────────────────────────────────
add_action('plugins_loaded', function () {
    AICG_Settings::init();
    AICG_Prompts::init();
    AICG_Generator_Page::init();
});

add_action('rest_api_init', function () {
    $controller = new AICG_REST_API();
    $controller->register_routes();
});
