<?php
/**
 * POST /api/factcheck.php
 *
 * Факт-чек статьи через Gemini.
 *
 * Input:  { "text": "..." }
 * Output: { "isValid": true|false, "errors": [...], "summary": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
requireFields($input, ['text']);

$text = $input['text'];

$prompt = "Ты — профессиональный ревьюер и факт-чекер текстов. "
    . "Проверь следующий текст на фактические ошибки, неточности и несоответствия.\n\n"
    . "Требования к проверке:\n"
    . "- Проверь все упомянутые факты, цифры, даты, имена\n"
    . "- Найди логические противоречия\n"
    . "- Отметь спорные или непроверяемые утверждения\n"
    . "- Если всё корректно, так и напиши\n\n"
    . "ФОРМАТ ОТВЕТА (строго JSON):\n"
    . "{\n"
    . "  \"isValid\": true или false,\n"
    . "  \"errors\": [\"описание ошибки 1\", \"описание ошибки 2\"],\n"
    . "  \"summary\": \"краткий итог проверки\"\n"
    . "}\n\n"
    . "Текст для проверки:\n\n{$text}";

$response = callGemini($prompt);

// Пытаемся распарсить JSON из ответа Gemini
$jsonStr = $response;

// Gemini может обернуть в ```json ... ```
if (preg_match('/```(?:json)?\s*(\{.+?\})\s*```/s', $jsonStr, $m)) {
    $jsonStr = $m[1];
}

$parsed = json_decode($jsonStr, true);

if ($parsed && isset($parsed['isValid'])) {
    sendJson([
        'isValid' => (bool) $parsed['isValid'],
        'errors'  => $parsed['errors'] ?? [],
        'summary' => $parsed['summary'] ?? '',
    ]);
} else {
    // Если Gemini не вернул валидный JSON — считаем что ошибок нет,
    // но показываем raw-ответ как summary
    sendJson([
        'isValid' => true,
        'errors'  => [],
        'summary' => $response,
    ]);
}
