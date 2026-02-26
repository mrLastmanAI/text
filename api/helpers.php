<?php
/**
 * Shared helpers for all API endpoints.
 *
 * Provides:
 *  - CORS headers
 *  - JSON request parsing
 *  - cURL wrapper for external API calls
 *  - Unified LLM call via OpenRouter (Claude, GPT, Gemini — один ключ)
 *  - WordPress REST API helper
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

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        sendError('Невалидный JSON в теле запроса', 400);
    }
    return $data;
}

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
 * @param string $url
 * @param array  $options ['method', 'headers', 'body', 'timeout']
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

// ══════════════════════════════════════════════════════════════════
//  LLM через OpenRouter (единый ключ, OpenAI-совместимый формат)
// ══════════════════════════════════════════════════════════════════

/**
 * Вызов любой LLM через OpenRouter.
 *
 * @param string $model   Ключ из config['models']: 'claude', 'gpt', 'gemini'
 * @param string $system  System prompt
 * @param string $user    User message
 * @param int    $maxTokens
 * @return string  Текст ответа
 */
function callLLM(string $model, string $system, string $user, int $maxTokens = 8192): string
{
    $cfg      = getConfig();
    $orCfg    = $cfg['openrouter'];
    $modelId  = $cfg['models'][$model] ?? null;

    if (empty($orCfg['api_key'])) {
        sendError('OpenRouter API key не настроен в config.php', 500);
    }
    if (!$modelId) {
        sendError("Модель '{$model}' не найдена в config.php['models']", 500);
    }

    $res = httpRequest($orCfg['base_url'] . '/chat/completions', [
        'method'  => 'POST',
        'headers' => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $orCfg['api_key'],
        ],
        'body' => json_encode([
            'model'      => $modelId,
            'messages'   => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            'max_tokens' => $maxTokens,
        ]),
    ]);

    if ($res['status'] !== 200) {
        $errMsg = $res['json']['error']['message'] ?? $res['body'];
        sendError("OpenRouter ({$modelId}) ошибка: " . $errMsg, 502);
    }

    return $res['json']['choices'][0]['message']['content'] ?? '';
}

/**
 * Короткие алиасы для обратной совместимости с эндпоинтами.
 */
function callClaude(string $system, string $user): string
{
    return callLLM('claude', $system, $user);
}

function callOpenAI(string $system, string $user): string
{
    return callLLM('gpt', $system, $user);
}

function callGemini(string $prompt): string
{
    return callLLM('gemini', 'Ты — полезный AI-ассистент.', $prompt);
}

// ══════════════════════════════════════════════════════════════════
//  WordPress REST API
// ══════════════════════════════════════════════════════════════════

/**
 * Публикация/обновление записи в WordPress через REST API.
 *
 * @param array $postData [
 *     'title'   => string,
 *     'content' => string (HTML),
 *     'status'  => 'draft' | 'publish' | 'pending',
 *     'categories' => [int, ...],
 *     'tags'       => [int, ...],
 * ]
 * @param int|null $postId  Если указан — обновление существующего поста
 * @return array  WP REST API response
 */
function wpPublish(array $postData, ?int $postId = null): array
{
    $cfg = getConfig()['wordpress'];

    if (empty($cfg['site_url']) || empty($cfg['username']) || empty($cfg['app_password'])) {
        sendError('WordPress не настроен в config.php (site_url, username, app_password)', 500);
    }

    $siteUrl = rtrim($cfg['site_url'], '/');
    $url = $postId
        ? "{$siteUrl}/wp-json/wp/v2/posts/{$postId}"
        : "{$siteUrl}/wp-json/wp/v2/posts";

    $auth = base64_encode($cfg['username'] . ':' . $cfg['app_password']);

    $res = httpRequest($url, [
        'method'  => $postId ? 'PUT' : 'POST',
        'headers' => [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth,
        ],
        'body' => json_encode($postData),
    ]);

    if ($res['status'] !== 200 && $res['status'] !== 201) {
        $errMsg = $res['json']['message'] ?? $res['body'];
        sendError('WordPress API ошибка: ' . $errMsg, 502);
    }

    return $res['json'];
}

/**
 * Получить список категорий WordPress.
 */
function wpGetCategories(): array
{
    $cfg = getConfig()['wordpress'];
    $siteUrl = rtrim($cfg['site_url'], '/');
    $auth = base64_encode($cfg['username'] . ':' . $cfg['app_password']);

    $res = httpRequest("{$siteUrl}/wp-json/wp/v2/categories?per_page=100", [
        'method'  => 'GET',
        'headers' => [
            'Authorization: Basic ' . $auth,
        ],
    ]);

    if ($res['status'] !== 200) {
        return [];
    }

    return $res['json'] ?? [];
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
