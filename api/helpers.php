<?php
/**
 * Shared helpers for all API endpoints.
 *
 * Provides:
 *  - CORS headers
 *  - JSON request parsing
 *  - cURL wrapper for external API calls
 *  - JSON response helpers
 *  - Input sanitization
 */

// ── CORS & Headers ───────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           // Ограничить в production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// ── Config ───────────────────────────────────────────────────────

function getConfig(): array
{
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/config.php';
        if (!file_exists($path)) {
            sendError('Конфигурация не найдена. Скопируйте config.example.php → config.php', 500);
        }
        $config = require $path;
    }
    return $config;
}

// ── Request Parsing ──────────────────────────────────────────────

/**
 * Parse JSON body from the incoming request.
 */
function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        sendError('Невалидный JSON в теле запроса', 400);
    }
    return $data;
}

/**
 * Require specific fields in the input.
 */
function requireFields(array $input, array $fields): void
{
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($input[$f]) || (is_string($input[$f]) && trim($input[$f]) === '')) {
            $missing[] = $f;
        }
    }
    if ($missing) {
        sendError('Отсутствуют обязательные поля: ' . implode(', ', $missing), 400);
    }
}

// ── Sanitization ─────────────────────────────────────────────────

function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// ── cURL Wrapper ─────────────────────────────────────────────────

/**
 * Make an HTTP request via cURL.
 *
 * @param string $url     Full URL
 * @param array  $options [
 *     'method'  => 'POST',
 *     'headers' => ['Header: Value', ...],
 *     'body'    => string|null,
 *     'timeout' => int (seconds, default 120),
 * ]
 * @return array ['status' => int, 'body' => string, 'json' => mixed]
 */
function httpRequest(string $url, array $options = []): array
{
    $method  = $options['method']  ?? 'POST';
    $headers = $options['headers'] ?? [];
    $body    = $options['body']    ?? null;
    $timeout = $options['timeout'] ?? 120;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        sendError('Ошибка соединения: ' . $curlErr, 502);
    }

    return [
        'status' => $httpCode,
        'body'   => $response,
        'json'   => json_decode($response, true),
    ];
}

// ── Claude API Helper ────────────────────────────────────────────

function callClaude(string $systemPrompt, string $userMessage): string
{
    $cfg = getConfig()['claude'];

    if (empty($cfg['api_key'])) {
        sendError('Claude API key не настроен в config.php', 500);
    }

    $res = httpRequest($cfg['base_url'] . '/messages', [
        'method'  => 'POST',
        'headers' => [
            'Content-Type: application/json',
            'x-api-key: ' . $cfg['api_key'],
            'anthropic-version: 2023-06-01',
        ],
        'body' => json_encode([
            'model'      => $cfg['model'],
            'max_tokens' => 8192,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]),
    ]);

    if ($res['status'] !== 200) {
        $errMsg = $res['json']['error']['message'] ?? $res['body'];
        sendError('Claude API ошибка: ' . $errMsg, 502);
    }

    return $res['json']['content'][0]['text'] ?? '';
}

// ── OpenAI API Helper ────────────────────────────────────────────

function callOpenAI(string $systemPrompt, string $userMessage, ?string $model = null): string
{
    $cfg = getConfig()['openai'];

    if (empty($cfg['api_key'])) {
        sendError('OpenAI API key не настроен в config.php', 500);
    }

    $res = httpRequest($cfg['base_url'] . '/chat/completions', [
        'method'  => 'POST',
        'headers' => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ],
        'body' => json_encode([
            'model'    => $model ?? $cfg['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'max_tokens' => 8192,
        ]),
    ]);

    if ($res['status'] !== 200) {
        $errMsg = $res['json']['error']['message'] ?? $res['body'];
        sendError('OpenAI API ошибка: ' . $errMsg, 502);
    }

    return $res['json']['choices'][0]['message']['content'] ?? '';
}

// ── Gemini API Helper ────────────────────────────────────────────

function callGemini(string $prompt): string
{
    $cfg = getConfig()['gemini'];

    if (empty($cfg['api_key'])) {
        sendError('Gemini API key не настроен в config.php', 500);
    }

    $url = $cfg['base_url'] . '/models/' . $cfg['model'] . ':generateContent?key=' . $cfg['api_key'];

    $res = httpRequest($url, [
        'method'  => 'POST',
        'headers' => ['Content-Type: application/json'],
        'body'    => json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ]),
    ]);

    if ($res['status'] !== 200) {
        $errMsg = $res['body'];
        sendError('Gemini API ошибка: ' . $errMsg, 502);
    }

    return $res['json']['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

// ── Response Helpers ─────────────────────────────────────────────

function sendJson(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
