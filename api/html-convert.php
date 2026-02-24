<?php
/**
 * POST /api/html-convert.php
 *
 * Конвертация текста статьи в HTML через ChatGPT.
 *
 * Input:  { "text": "..." }
 * Output: { "html": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
requireFields($input, ['text']);

$text = $input['text'];

$systemPrompt = 'Ты — верстальщик. Преобразуй текст статьи в чистый, семантический HTML.\n\n'
    . "Правила форматирования:\n"
    . "- Заголовок статьи: <h1>\n"
    . "- Подзаголовки разделов: <h2>, <h3>\n"
    . "- Абзацы: <p>\n"
    . "- Списки: <ul>/<ol> с <li>\n"
    . "- Выделение: <strong> для важного, <em> для акцентов\n"
    . "- Цитаты: <blockquote>\n"
    . "- Оберни всё в <article>\n"
    . "- НЕ добавляй <html>, <head>, <body> — только содержимое статьи\n"
    . "- НЕ добавляй CSS-стили\n"
    . "- Верни ТОЛЬКО HTML-код, без пояснений";

$userMessage = "Преобразуй в HTML:\n\n{$text}";

$html = callOpenAI($systemPrompt, $userMessage);
sendJson(['html' => $html]);
