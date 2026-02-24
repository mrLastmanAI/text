<?php
/**
 * POST /api/generate.php
 *
 * Генерация статьи через Claude.
 *
 * Input:  { "plan": "...", "research": "..." | null, "style": "egorka" | "not-egorka" }
 * Output: { "article": "..." }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
requireFields($input, ['plan', 'style']);

$plan     = $input['plan'];
$research = $input['research'] ?? '';
$style    = $input['style'];

// ── Определяем стиль ─────────────────────────────────────────────
$styleInstruction = '';
if ($style === 'egorka') {
    $styleInstruction = 'Стиль «Егорка»: пиши живо, с юмором, разговорным языком, '
        . 'используй короткие абзацы, личные обращения к читателю, '
        . 'метафоры и сравнения из повседневной жизни. Текст должен быть легко читаемым и вовлекающим.';
} else {
    $styleInstruction = 'Стиль «Стандартный»: пиши профессионально, экспертно, '
        . 'структурированно, с чёткими формулировками. Соблюдай деловой тон, '
        . 'избегая излишней разговорности. Текст должен выглядеть авторитетно и информативно.';
}

$systemPrompt = "Ты — профессиональный копирайтер. Напиши полную статью по предоставленному плану.\n\n"
    . "Требования к стилю:\n{$styleInstruction}\n\n"
    . "Требования к тексту:\n"
    . "- Следуй структуре плана\n"
    . "- Используй все собранные факты и данные, если они предоставлены\n"
    . "- Пиши связный, логичный текст\n"
    . "- Добавляй подзаголовки для структурирования";

$userMessage = "План статьи:\n{$plan}\n\n";
if ($research) {
    $userMessage .= "Собранная информация:\n{$research}\n\n";
}
$userMessage .= "Напиши полную статью.";

$article = callClaude($systemPrompt, $userMessage);
sendJson(['article' => $article]);
