<?php
/**
 * REST API Controller
 *
 * Регистрирует все эндпоинты: /wp-json/aicg/v1/...
 *
 * Endpoints:
 *   POST /transcribe      — YouTube → текст
 *   POST /plan            — создать/редактировать план
 *   POST /research        — собрать/редактировать ресёрч
 *   POST /generate        — сгенерировать статью
 *   POST /factcheck       — проверить факты
 *   POST /fix-errors      — исправить ошибки
 *   POST /humanize        — хуманизация
 *   POST /html-convert    — конвертация в HTML
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_REST_API {

    private string $namespace = 'aicg/v1';

    public function register_routes(): void {

        // Транскрипция YouTube
        register_rest_route($this->namespace, '/transcribe', [
            'methods'             => 'POST',
            'callback'            => [$this, 'transcribe'],
            'permission_callback' => [$this, 'can_edit'],
            'args' => [
                'url'  => ['required' => true,  'type' => 'string', 'sanitize_callback' => 'esc_url_raw'],
                'lang' => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        // План
        register_rest_route($this->namespace, '/plan', [
            'methods'             => 'POST',
            'callback'            => [$this, 'plan'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // Ресёрч
        register_rest_route($this->namespace, '/research', [
            'methods'             => 'POST',
            'callback'            => [$this, 'research'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // Генерация
        register_rest_route($this->namespace, '/generate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'generate'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // Факт-чек
        register_rest_route($this->namespace, '/factcheck', [
            'methods'             => 'POST',
            'callback'            => [$this, 'factcheck'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // Исправление ошибок
        register_rest_route($this->namespace, '/fix-errors', [
            'methods'             => 'POST',
            'callback'            => [$this, 'fix_errors'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // Хуманизация
        register_rest_route($this->namespace, '/humanize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'humanize'],
            'permission_callback' => [$this, 'can_edit'],
        ]);

        // HTML-конвертация
        register_rest_route($this->namespace, '/html-convert', [
            'methods'             => 'POST',
            'callback'            => [$this, 'html_convert'],
            'permission_callback' => [$this, 'can_edit'],
        ]);
    }

    public function can_edit(): bool|WP_Error {
        if (!current_user_can('edit_posts')) {
            return new WP_Error('rest_forbidden', 'Нет прав.', ['status' => 403]);
        }
        return true;
    }

    // ── Хелпер: получить промпт из настроек ──────────────────────

    private function prompt(string $key): string {
        return get_option(AICG_OPTION_PREFIX . 'prompt_' . $key, '');
    }

    private function or_error(string|WP_Error $result): WP_REST_Response|WP_Error {
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response($result, 200);
    }

    // ══════════════════════════════════════════════════════════════
    //  Endpoints
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /transcribe — YouTube видео → текст
     */
    public function transcribe(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $url  = $req->get_param('url');
        $lang = $req->get_param('lang') ?: '';

        $api    = new AICG_Supadata();
        $result = $api->transcribe($url, $lang);

        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(['text' => $result['text']], 200);
    }

    /**
     * POST /plan — создать или отредактировать план
     */
    public function plan(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $action = $req->get_param('action') ?: 'create';
        $llm    = new AICG_OpenRouter();

        if ($action === 'create') {
            $topic      = sanitize_text_field($req->get_param('topic') ?? '');
            $audience   = sanitize_text_field($req->get_param('audience') ?? '');
            $goal       = sanitize_text_field($req->get_param('goal') ?? '');
            $type       = sanitize_text_field($req->get_param('type') ?? '');
            $platform   = sanitize_text_field($req->get_param('platform') ?? '');
            $volume     = sanitize_text_field($req->get_param('volume') ?? '');
            $needsRes   = !empty($req->get_param('needsResearch'));
            $sourceText = $req->get_param('sourceText') ?? '';

            $system = $this->prompt('plan_create_system');
            if ($needsRes) {
                $system .= ' ' . $this->prompt('plan_create_research_note');
            }

            $user = "Создай структуру (план) статьи:\n\n";
            if ($sourceText) $user .= "Исходный текст (транскрипция видео):\n{$sourceText}\n\n";
            if ($topic)      $user .= "Тема статьи: {$topic}\n";
            if ($audience)   $user .= "Целевая аудитория: {$audience}\n";
            if ($goal)       $user .= "Основная цель: {$goal}\n";
            if ($type)       $user .= "Тип статьи: {$type}\n";
            if ($platform)   $user .= "Площадка: {$platform}\n";
            if ($volume)     $user .= "Желаемый объём: {$volume}\n";

            $result = $llm->call_claude($system, $user);
            if (is_wp_error($result)) return $result;
            return new WP_REST_Response(['plan' => $result], 200);

        } elseif ($action === 'edit') {
            $plan        = $req->get_param('plan') ?? '';
            $corrections = sanitize_text_field($req->get_param('corrections') ?? '');

            if (!$plan || !$corrections) {
                return new WP_Error('missing_fields', 'Укажите plan и corrections.', ['status' => 400]);
            }

            $system = $this->prompt('plan_edit_system');
            $user   = "Текущий план:\n{$plan}\n\nПравки, которые необходимо внести:\n{$corrections}";

            $result = $llm->call_claude($system, $user);
            if (is_wp_error($result)) return $result;
            return new WP_REST_Response(['plan' => $result], 200);
        }

        return new WP_Error('invalid_action', 'Неизвестное действие.', ['status' => 400]);
    }

    /**
     * POST /research — собрать или отредактировать информацию
     */
    public function research(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $action = $req->get_param('action') ?: 'collect';
        $llm    = new AICG_OpenRouter();

        if ($action === 'collect') {
            $plan = $req->get_param('plan') ?? '';
            if (!$plan) return new WP_Error('missing_plan', 'Укажите план.', ['status' => 400]);

            $system = $this->prompt('research_collect_system');
            $user   = "План статьи, для которого нужно собрать информацию:\n\n{$plan}";

            $result = $llm->call_gpt($system, $user);
            if (is_wp_error($result)) return $result;
            return new WP_REST_Response(['research' => $result], 200);

        } elseif ($action === 'edit') {
            $research    = $req->get_param('research') ?? '';
            $corrections = sanitize_text_field($req->get_param('corrections') ?? '');

            if (!$research || !$corrections) {
                return new WP_Error('missing_fields', 'Укажите research и corrections.', ['status' => 400]);
            }

            $system = $this->prompt('research_edit_system');
            $user   = "Текущая собранная информация:\n{$research}\n\nПравки:\n{$corrections}";

            $result = $llm->call_gpt($system, $user);
            if (is_wp_error($result)) return $result;
            return new WP_REST_Response(['research' => $result], 200);
        }

        return new WP_Error('invalid_action', 'Неизвестное действие.', ['status' => 400]);
    }

    /**
     * POST /generate — сгенерировать статью
     */
    public function generate(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $plan     = $req->get_param('plan') ?? '';
        $research = $req->get_param('research') ?? '';
        $style    = $req->get_param('style') ?? 'not-egorka';

        if (!$plan) return new WP_Error('missing_plan', 'Укажите план.', ['status' => 400]);

        $style_prompt = ($style === 'egorka')
            ? $this->prompt('generate_style_egorka')
            : $this->prompt('generate_style_standard');

        $system = $this->prompt('generate_system') . "\n\nТребования к стилю:\n{$style_prompt}";

        $user = "План статьи:\n{$plan}\n\n";
        if ($research) {
            $user .= "Собранная информация:\n{$research}\n\n";
        }
        $user .= "Напиши полную статью.";

        $llm    = new AICG_OpenRouter();
        $result = $llm->call_claude($system, $user);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(['article' => $result], 200);
    }

    /**
     * POST /factcheck — факт-чек
     */
    public function factcheck(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $text = $req->get_param('text') ?? '';
        if (!$text) return new WP_Error('missing_text', 'Укажите текст.', ['status' => 400]);

        $prompt = $this->prompt('factcheck_prompt') . "\n\nТекст для проверки:\n\n{$text}";

        $llm      = new AICG_OpenRouter();
        $response = $llm->call_gemini($prompt);
        if (is_wp_error($response)) return $response;

        // Парсим JSON
        $json_str = $response;
        if (preg_match('/```(?:json)?\s*(\{.+?\})\s*```/s', $json_str, $m)) {
            $json_str = $m[1];
        }

        $parsed = json_decode($json_str, true);

        if ($parsed && isset($parsed['isValid'])) {
            return new WP_REST_Response([
                'isValid' => (bool) $parsed['isValid'],
                'errors'  => $parsed['errors'] ?? [],
                'summary' => $parsed['summary'] ?? '',
            ], 200);
        }

        return new WP_REST_Response([
            'isValid' => true,
            'errors'  => [],
            'summary' => $response,
        ], 200);
    }

    /**
     * POST /fix-errors — исправить ошибки
     */
    public function fix_errors(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $text   = $req->get_param('text') ?? '';
        $errors = $req->get_param('errors') ?? [];

        if (!$text || !is_array($errors) || empty($errors)) {
            return new WP_Error('missing_fields', 'Укажите text и errors.', ['status' => 400]);
        }

        $errors_list = implode("\n", array_map(
            fn($e, $i) => ($i + 1) . '. ' . $e,
            $errors,
            array_keys($errors)
        ));

        $system = $this->prompt('fix_errors_system');
        $user   = "Текст статьи:\n{$text}\n\nОбнаруженные ошибки, которые нужно исправить:\n{$errors_list}";

        $llm    = new AICG_OpenRouter();
        $result = $llm->call_claude($system, $user);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(['article' => $result], 200);
    }

    /**
     * POST /humanize — хуманизация
     */
    public function humanize(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $text = $req->get_param('text') ?? '';
        if (!$text) return new WP_Error('missing_text', 'Укажите текст.', ['status' => 400]);

        $system = $this->prompt('humanize_system');
        $user   = "Хуманизируй следующий текст:\n\n{$text}";

        $llm    = new AICG_OpenRouter();
        $result = $llm->call_claude($system, $user);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(['text' => $result], 200);
    }

    /**
     * POST /html-convert — конвертация в HTML
     */
    public function html_convert(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $text = $req->get_param('text') ?? '';
        if (!$text) return new WP_Error('missing_text', 'Укажите текст.', ['status' => 400]);

        $system = $this->prompt('html_convert_system');
        $user   = "Преобразуй в HTML:\n\n{$text}";

        $llm    = new AICG_OpenRouter();
        $result = $llm->call_gpt($system, $user);
        if (is_wp_error($result)) return $result;
        return new WP_REST_Response(['html' => $result], 200);
    }
}
