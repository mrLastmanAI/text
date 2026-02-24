<?php
/**
 * POST /api/transcribe.php
 *
 * Транскрипция YouTube видео через Supadata API.
 *
 * Input:  { "url": "https://youtube.com/watch?v=..." }
 * Output: { "text": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
requireFields($input, ['url']);

$youtubeUrl = filter_var($input['url'], FILTER_VALIDATE_URL);
if (!$youtubeUrl || (!str_contains($youtubeUrl, 'youtube.com') && !str_contains($youtubeUrl, 'youtu.be'))) {
    sendError('Невалидная ссылка на YouTube', 400);
}

$cfg = getConfig()['supadata'];

if (empty($cfg['api_key'])) {
    sendError('Supadata API key не настроен в config.php', 500);
}

// Запрос к Supadata API для транскрипции
$res = httpRequest($cfg['base_url'] . '/youtube/transcript', [
    'method'  => 'POST',
    'headers' => [
        'Content-Type: application/json',
        'x-api-key: ' . $cfg['api_key'],
    ],
    'body' => json_encode([
        'url'  => $youtubeUrl,
        'lang' => 'ru',
    ]),
    'timeout' => 180, // Транскрипция может быть долгой
]);

if ($res['status'] !== 200) {
    $errMsg = $res['json']['message'] ?? $res['body'];
    sendError('Ошибка Supadata: ' . $errMsg, 502);
}

$transcript = '';
if (is_array($res['json'])) {
    // Supadata может вернуть массив сегментов или объект с полем content/text
    if (isset($res['json']['content'])) {
        $transcript = $res['json']['content'];
    } elseif (isset($res['json']['text'])) {
        $transcript = $res['json']['text'];
    } elseif (isset($res['json'][0]['text'])) {
        // Массив сегментов — склеиваем
        $transcript = implode(' ', array_column($res['json'], 'text'));
    } else {
        $transcript = json_encode($res['json'], JSON_UNESCAPED_UNICODE);
    }
}

sendJson(['text' => $transcript]);
