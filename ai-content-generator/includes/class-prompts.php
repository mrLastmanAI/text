<?php
/**
 * Prompts Page
 *
 * Админка: AI Content → Промпты
 * Редактирование всех AI-промптов из интерфейса WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICG_Prompts {

    private static string $page_slug    = 'aicg-prompts';
    private static string $option_group = 'aicg_prompts_group';

    /**
     * Описания промптов для отображения в UI.
     */
    private static function get_prompt_fields(): array {
        return [
            // ── План ──────────────────────────────────────────
            [
                'section' => 'plan',
                'title'   => 'Создание плана',
                'fields'  => [
                    'plan_create_system' => [
                        'label' => 'System prompt (создание плана)',
                        'desc'  => 'Инструкции для Claude при генерации структуры статьи.',
                        'rows'  => 5,
                    ],
                    'plan_create_research_note' => [
                        'label' => 'Дополнение при включённом ресёрче',
                        'desc'  => 'Добавляется к system prompt, если включён сбор информации.',
                        'rows'  => 2,
                    ],
                    'plan_edit_system' => [
                        'label' => 'System prompt (редактирование плана)',
                        'desc'  => 'Инструкции для Claude при правке плана.',
                        'rows'  => 3,
                    ],
                ],
            ],
            // ── Генерация ─────────────────────────────────────
            [
                'section' => 'generate',
                'title'   => 'Генерация статьи',
                'fields'  => [
                    'generate_system' => [
                        'label' => 'System prompt (генерация)',
                        'desc'  => 'Основные инструкции для написания статьи.',
                        'rows'  => 8,
                    ],
                    'generate_style_egorka' => [
                        'label' => 'Стиль «Егорка»',
                        'desc'  => 'Описание живого, разговорного стиля.',
                        'rows'  => 4,
                    ],
                    'generate_style_standard' => [
                        'label' => 'Стиль «Стандартный»',
                        'desc'  => 'Описание профессионального стиля.',
                        'rows'  => 4,
                    ],
                ],
            ],
            // ── Ресёрч ────────────────────────────────────────
            [
                'section' => 'research',
                'title'   => 'Сбор информации',
                'fields'  => [
                    'research_collect_system' => [
                        'label' => 'System prompt (сбор)',
                        'desc'  => 'Инструкции для ChatGPT при ресёрче.',
                        'rows'  => 5,
                    ],
                    'research_edit_system' => [
                        'label' => 'System prompt (правка ресёрча)',
                        'desc'  => 'Инструкции для ChatGPT при обновлении информации.',
                        'rows'  => 3,
                    ],
                ],
            ],
            // ── Факт-чек ──────────────────────────────────────
            [
                'section' => 'factcheck',
                'title'   => 'Факт-чек',
                'fields'  => [
                    'factcheck_prompt' => [
                        'label' => 'Промпт факт-чека',
                        'desc'  => 'Полный промпт для Gemini. Должен содержать инструкции и формат JSON-ответа.',
                        'rows'  => 12,
                    ],
                ],
            ],
            // ── Исправление ───────────────────────────────────
            [
                'section' => 'fix',
                'title'   => 'Исправление ошибок',
                'fields'  => [
                    'fix_errors_system' => [
                        'label' => 'System prompt',
                        'desc'  => 'Инструкции для Claude при исправлении фактических ошибок.',
                        'rows'  => 4,
                    ],
                ],
            ],
            // ── Хуманизация ───────────────────────────────────
            [
                'section' => 'humanize',
                'title'   => 'Хуманизация',
                'fields'  => [
                    'humanize_system' => [
                        'label' => 'System prompt',
                        'desc'  => 'Правила хуманизации текста для обхода AI-детекторов.',
                        'rows'  => 14,
                    ],
                ],
            ],
            // ── HTML ──────────────────────────────────────────
            [
                'section' => 'html',
                'title'   => 'HTML-конвертация',
                'fields'  => [
                    'html_convert_system' => [
                        'label' => 'System prompt',
                        'desc'  => 'Правила HTML-вёрстки статьи.',
                        'rows'  => 10,
                    ],
                ],
            ],
        ];
    }

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('wp_ajax_aicg_reset_prompts', [self::class, 'ajax_reset_prompts']);
    }

    public static function add_menu(): void {
        add_submenu_page(
            'ai-content-generator',
            'Промпты AI',
            'Промпты',
            'manage_options',
            self::$page_slug,
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        $sections = self::get_prompt_fields();

        foreach ($sections as $section) {
            $section_id = 'aicg_prompt_section_' . $section['section'];

            add_settings_section($section_id, $section['title'], null, self::$page_slug);

            foreach ($section['fields'] as $key => $field) {
                $option_name = AICG_OPTION_PREFIX . 'prompt_' . $key;

                register_setting(self::$option_group, $option_name, [
                    'type'              => 'string',
                    'sanitize_callback' => function ($value) {
                        return wp_kses_post(trim($value));
                    },
                ]);

                add_settings_field(
                    $option_name,
                    $field['label'],
                    function () use ($option_name, $field) {
                        $value = get_option($option_name, '');
                        printf(
                            '<textarea id="%s" name="%s" rows="%d" class="large-text code" style="font-family:monospace;">%s</textarea>',
                            esc_attr($option_name),
                            esc_attr($option_name),
                            $field['rows'] ?? 5,
                            esc_textarea($value)
                        );
                        if (!empty($field['desc'])) {
                            printf('<p class="description">%s</p>', esc_html($field['desc']));
                        }
                    },
                    self::$page_slug,
                    $section_id
                );
            }
        }
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) return;
        include AICG_PLUGIN_DIR . 'templates/prompts-page.php';
    }

    /**
     * AJAX: сброс всех промптов к дефолтным значениям.
     */
    public static function ajax_reset_prompts(): void {
        check_ajax_referer('aicg_reset_prompts_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Нет прав.');
        }

        $defaults = AICG_Activator::get_default_prompts();
        foreach ($defaults as $key => $value) {
            update_option(AICG_OPTION_PREFIX . 'prompt_' . $key, $value);
        }

        wp_send_json_success('Все промпты сброшены к значениям по умолчанию.');
    }
}
