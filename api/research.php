<?php
/**
 * POST /api/research.php
 *
 * Сбор информации для статьи через ChatGPT Research.
 *
 * Создание:
 *   Input:  { "action": "collect", "plan": "..." }
 *   Output: { "research": "..." }
 *
 * Редактирование:
 *   Input:  { "action": "edit", "research": "...", "corrections": "..." }
 *   Output: { "research": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
$action = $input['action'] ?? 'collect';

if ($action === 'collect') {
    // ── Сбор информации ──────────────────────────────────────────

    requireFields($input, ['plan']);

    $plan = $input['plan'];

    $systemPrompt = 'Ты — исследователь и аналитик. '
        . 'На основе предоставленного плана статьи собери всю необходимую информацию: '
        . 'факты, статистику, экспертные мнения, примеры и кейсы, ссылки на источники. '
        . 'Структурируй информацию по разделам плана.';

    $userMessage = "План статьи, для которого нужно собрать информацию:\n\n{$plan}";

    $research = callOpenAI($systemPrompt, $userMessage);
    sendJson(['research' => $research]);

} elseif ($action === 'edit') {
    // ── Редактирование ───────────────────────────────────────────

    requireFields($input, ['research', 'corrections']);

    $currentResearch = $input['research'];
    $corrections     = sanitize($input['corrections']);

    $systemPrompt = 'Ты — исследователь и аналитик. '
        . 'Обнови собранную информацию с учётом указанных замечаний.';

    $userMessage = "Текущая собранная информация:\n{$currentResearch}\n\n"
        . "Правки:\n{$corrections}";

    $research = callOpenAI($systemPrompt, $userMessage);
    sendJson(['research' => $research]);

} else {
    sendError('Неизвестное действие: ' . $action, 400);
}
