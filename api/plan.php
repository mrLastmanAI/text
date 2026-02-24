<?php
/**
 * POST /api/plan.php
 *
 * Создание / редактирование плана статьи через Claude.
 *
 * Создание:
 *   Input:  { "action": "create", "topic": "...", "audience": "...", "goal": "...",
 *             "type": "...", "platform": "...", "volume": "...", "needsResearch": true,
 *             "sourceText": "..." }
 *   Output: { "plan": "..." }
 *
 * Редактирование:
 *   Input:  { "action": "edit", "plan": "...", "corrections": "..." }
 *   Output: { "plan": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
$action = $input['action'] ?? 'create';

if ($action === 'create') {
    // ── Создание плана ───────────────────────────────────────────

    $topic      = sanitize($input['topic']      ?? '');
    $audience   = sanitize($input['audience']    ?? '');
    $goal       = sanitize($input['goal']        ?? '');
    $type       = sanitize($input['type']        ?? '');
    $platform   = sanitize($input['platform']    ?? '');
    $volume     = sanitize($input['volume']      ?? '');
    $needsRes   = !empty($input['needsResearch']);
    $sourceText = $input['sourceText'] ?? '';

    $systemPrompt = 'Ты — профессиональный контент-стратег и редактор. '
        . 'Создай детальный план (структуру) статьи на основе предоставленных параметров. '
        . 'План должен содержать: заголовки разделов, краткое описание содержания каждого раздела, '
        . 'примерный объём каждого раздела. '
        . ($needsRes ? 'Статья ТРЕБУЕТ сбора дополнительной информации перед написанием — учти это в плане.' : '');

    $userMessage = "Создай структуру (план) статьи:\n\n";

    if ($sourceText) {
        $userMessage .= "Исходный текст (транскрипция видео):\n{$sourceText}\n\n";
    }
    if ($topic)    $userMessage .= "Тема статьи: {$topic}\n";
    if ($audience) $userMessage .= "Целевая аудитория: {$audience}\n";
    if ($goal)     $userMessage .= "Основная цель: {$goal}\n";
    if ($type)     $userMessage .= "Тип статьи: {$type}\n";
    if ($platform) $userMessage .= "Площадка: {$platform}\n";
    if ($volume)   $userMessage .= "Желаемый объём: {$volume}\n";

    $plan = callClaude($systemPrompt, $userMessage);
    sendJson(['plan' => $plan]);

} elseif ($action === 'edit') {
    // ── Редактирование плана ─────────────────────────────────────

    requireFields($input, ['plan', 'corrections']);

    $currentPlan = $input['plan'];
    $corrections = sanitize($input['corrections']);

    $systemPrompt = 'Ты — профессиональный контент-стратег. '
        . 'Отредактируй план статьи с учётом указанных замечаний. '
        . 'Верни обновлённый план целиком.';

    $userMessage = "Текущий план:\n{$currentPlan}\n\n"
        . "Правки, которые необходимо внести:\n{$corrections}";

    $plan = callClaude($systemPrompt, $userMessage);
    sendJson(['plan' => $plan]);

} else {
    sendError('Неизвестное действие: ' . $action, 400);
}
