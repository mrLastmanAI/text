<?php
/**
 * API Configuration — EXAMPLE
 *
 * Скопируйте этот файл в config.php и заполните реальными ключами:
 *   cp config.example.php config.php
 *
 * OpenRouter: https://openrouter.ai/keys
 * Supadata:   https://supadata.ai
 * WordPress Application Password: Пользователи → Профиль → Пароли приложений
 */

return [
    // ── OpenRouter (единый ключ для всех LLM) ───────────────────
    'openrouter' => [
        'api_key'  => 'sk-or-v1-YOUR_OPENROUTER_KEY',
        'base_url' => 'https://openrouter.ai/api/v1',
    ],

    // ── Модели (OpenRouter model IDs) ────────────────────────────
    // Полный список: https://openrouter.ai/models
    'models' => [
        'claude'  => 'anthropic/claude-sonnet-4-5-20250929',  // План, генерация, хуманизация, фикс ошибок
        'gpt'     => 'openai/gpt-4o',                         // Research, HTML-конвертация
        'gemini'  => 'google/gemini-2.0-flash-001',           // Факт-чек
    ],

    // ── Supadata (транскрипция YouTube) ──────────────────────────
    'supadata' => [
        'api_key'  => 'YOUR_SUPADATA_API_KEY',
        'base_url' => 'https://api.supadata.ai/v1',
    ],

    // ── WordPress (публикация через WP REST API) ─────────────────
    'wordpress' => [
        'site_url'     => 'https://your-site.com',
        'username'     => 'admin',
        'app_password' => 'xxxx xxxx xxxx xxxx xxxx xxxx',  // Application Password
    ],
];
