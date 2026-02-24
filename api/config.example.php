<?php
/**
 * API Configuration — EXAMPLE
 *
 * Скопируйте этот файл в config.php и заполните реальными ключами:
 *   cp config.example.php config.php
 */

return [
    'supadata' => [
        'api_key'  => 'YOUR_SUPADATA_API_KEY',
        'base_url' => 'https://api.supadata.ai/v1',
    ],
    'claude' => [
        'api_key'  => 'YOUR_CLAUDE_API_KEY',
        'base_url' => 'https://api.anthropic.com/v1',
        'model'    => 'claude-sonnet-4-5-20250929',
    ],
    'openai' => [
        'api_key'  => 'YOUR_OPENAI_API_KEY',
        'base_url' => 'https://api.openai.com/v1',
        'model'    => 'gpt-4o',
    ],
    'gemini' => [
        'api_key'  => 'YOUR_GEMINI_API_KEY',
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'model'    => 'gemini-2.0-flash',
    ],
];
