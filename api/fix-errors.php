<?php
/**
 * POST /api/fix-errors.php
 *
 * Исправление фактических ошибок в статье через Claude.
 *
 * Input:  { "text": "...", "errors": ["ошибка 1", "ошибка 2"] }
 * Output: { "article": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
requireFields($input, ['text', 'errors']);

$text   = $input['text'];
$errors = $input['errors'];

if (!is_array($errors) || empty($errors)) {
    sendError('Список ошибок пуст', 400);
}

$errorsList = implode("\n", array_map(fn($e, $i) => ($i + 1) . '. ' . $e, $errors, array_keys($errors)));

$systemPrompt = 'Ты — профессиональный редактор. '
    . 'Исправь фактические ошибки в тексте статьи. '
    . 'Сохрани стиль и структуру текста, исправив только указанные ошибки. '
    . 'Верни полный исправленный текст статьи.';

$userMessage = "Текст статьи:\n{$text}\n\n"
    . "Обнаруженные ошибки, которые нужно исправить:\n{$errorsList}";

$article = callClaude($systemPrompt, $userMessage);
sendJson(['article' => $article]);
