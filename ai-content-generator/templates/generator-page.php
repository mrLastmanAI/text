<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap" id="aicg-app">
    <h1>AI Content Generator</h1>

    <!-- ── Stepper ─────────────────────────────────────────────── -->
    <div class="aicg-stepper">
        <div class="aicg-step-indicator active" data-step="1"><span>1</span> Подготовка</div>
        <div class="aicg-step-indicator" data-step="2"><span>2</span> Генерация</div>
        <div class="aicg-step-indicator" data-step="3"><span>3</span> Факт-чек</div>
        <div class="aicg-step-indicator" data-step="4"><span>4</span> Хуманизация</div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  STEP 1: Подготовка                                       -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="aicg-step" id="aicg-step-1">
        <h2>Шаг 1: Подготовка</h2>

        <div class="aicg-source-toggle">
            <label><input type="radio" name="aicg-source" value="text" checked /> Текст / тема</label>
            <label><input type="radio" name="aicg-source" value="youtube" /> Ссылка на YouTube</label>
        </div>

        <!-- YouTube mode -->
        <div id="aicg-youtube-panel" style="display:none;">
            <div class="aicg-field">
                <label for="aicg-youtube-url">YouTube URL:</label>
                <input type="url" id="aicg-youtube-url" class="regular-text" placeholder="https://youtube.com/watch?v=..." />
                <button type="button" class="button button-primary" id="aicg-transcribe-btn">Транскрибировать</button>
            </div>
            <div id="aicg-transcript-result" style="display:none;">
                <label>Транскрипция:</label>
                <textarea id="aicg-transcript-text" rows="8" class="large-text" readonly></textarea>
            </div>
        </div>

        <!-- Параметры статьи -->
        <div id="aicg-params-panel">
            <div class="aicg-fields-grid">
                <div class="aicg-field">
                    <label for="aicg-topic">Тема статьи *</label>
                    <input type="text" id="aicg-topic" class="regular-text" placeholder="О чём статья?" />
                </div>
                <div class="aicg-field">
                    <label for="aicg-audience">Целевая аудитория *</label>
                    <select id="aicg-audience">
                        <option value="">Выберите...</option>
                        <option value="Широкая аудитория">Широкая аудитория</option>
                        <option value="Бизнесмены и предприниматели">Бизнесмены и предприниматели</option>
                        <option value="Разработчики">Разработчики</option>
                        <option value="Маркетологи">Маркетологи</option>
                        <option value="Студенты">Студенты</option>
                        <option value="other">Другое...</option>
                    </select>
                    <input type="text" id="aicg-audience-custom" class="regular-text aicg-custom-input" style="display:none;" placeholder="Укажите аудиторию" />
                </div>
                <div class="aicg-field">
                    <label for="aicg-goal">Цель статьи *</label>
                    <select id="aicg-goal">
                        <option value="">Выберите...</option>
                        <option value="Информирование">Информирование</option>
                        <option value="Продажа">Продажа</option>
                        <option value="Обучение">Обучение</option>
                        <option value="Развлечение">Развлечение</option>
                        <option value="SEO-трафик">SEO-трафик</option>
                        <option value="other">Другое...</option>
                    </select>
                    <input type="text" id="aicg-goal-custom" class="regular-text aicg-custom-input" style="display:none;" placeholder="Укажите цель" />
                </div>
                <div class="aicg-field">
                    <label for="aicg-type">Тип статьи *</label>
                    <select id="aicg-type">
                        <option value="">Выберите...</option>
                        <option value="Обзорная статья">Обзорная статья</option>
                        <option value="Руководство / How-to">Руководство / How-to</option>
                        <option value="Лисикл (список)">Лисикл (список)</option>
                        <option value="Аналитика">Аналитика</option>
                        <option value="Интервью">Интервью</option>
                        <option value="other">Другое...</option>
                    </select>
                    <input type="text" id="aicg-type-custom" class="regular-text aicg-custom-input" style="display:none;" placeholder="Укажите тип" />
                </div>
                <div class="aicg-field">
                    <label for="aicg-platform">Площадка *</label>
                    <select id="aicg-platform">
                        <option value="">Выберите...</option>
                        <option value="Блог / сайт">Блог / сайт</option>
                        <option value="Хабр">Хабр</option>
                        <option value="VC.ru">VC.ru</option>
                        <option value="Telegram">Telegram</option>
                        <option value="Дзен">Дзен</option>
                        <option value="other">Другое...</option>
                    </select>
                    <input type="text" id="aicg-platform-custom" class="regular-text aicg-custom-input" style="display:none;" placeholder="Укажите площадку" />
                </div>
                <div class="aicg-field">
                    <label for="aicg-volume">Объём *</label>
                    <select id="aicg-volume">
                        <option value="">Выберите...</option>
                        <option value="Короткая (до 1000 слов)">Короткая (до 1000 слов)</option>
                        <option value="Средняя (1000-2500 слов)">Средняя (1000-2500 слов)</option>
                        <option value="Длинная (2500-5000 слов)">Длинная (2500-5000 слов)</option>
                        <option value="Лонгрид (5000+ слов)">Лонгрид (5000+ слов)</option>
                    </select>
                </div>
            </div>
            <div class="aicg-field">
                <label>
                    <input type="checkbox" id="aicg-needs-research" />
                    Нужен сбор информации (ресёрч) перед написанием
                </label>
            </div>
        </div>

        <div class="aicg-actions">
            <button type="button" class="button button-primary button-hero" id="aicg-step1-next" disabled>
                Продолжить к генерации
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  STEP 2: Генерация                                        -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="aicg-step" id="aicg-step-2" style="display:none;">
        <h2>Шаг 2: Генерация статьи</h2>

        <!-- 2.1 План -->
        <div class="aicg-substep" id="aicg-substep-plan">
            <h3>2.1 — План статьи</h3>
            <div class="aicg-loading" id="aicg-plan-loading" style="display:none;">
                <span class="spinner is-active"></span> Генерирую план...
            </div>
            <div id="aicg-plan-result" style="display:none;">
                <textarea id="aicg-plan-text" rows="12" class="large-text" readonly></textarea>
                <div class="aicg-plan-actions">
                    <button type="button" class="button button-primary" id="aicg-plan-approve">Одобрить план</button>
                    <button type="button" class="button" id="aicg-plan-edit-toggle">Внести правки</button>
                    <button type="button" class="button" id="aicg-plan-regenerate">Перегенерировать</button>
                </div>
                <div id="aicg-plan-edit-panel" style="display:none;">
                    <textarea id="aicg-plan-corrections" rows="3" class="large-text" placeholder="Опишите, что нужно изменить в плане..."></textarea>
                    <button type="button" class="button button-primary" id="aicg-plan-edit-submit">Применить правки</button>
                </div>
            </div>
        </div>

        <!-- 2.2 Ресёрч -->
        <div class="aicg-substep" id="aicg-substep-research" style="display:none;">
            <h3>2.2 — Сбор информации</h3>
            <div class="aicg-loading" id="aicg-research-loading" style="display:none;">
                <span class="spinner is-active"></span> Собираю информацию...
            </div>
            <div id="aicg-research-result" style="display:none;">
                <textarea id="aicg-research-text" rows="10" class="large-text" readonly></textarea>
                <div class="aicg-plan-actions">
                    <button type="button" class="button button-primary" id="aicg-research-approve">Одобрить</button>
                    <button type="button" class="button" id="aicg-research-edit-toggle">Внести правки</button>
                    <button type="button" class="button" id="aicg-research-regenerate">Перегенерировать</button>
                </div>
                <div id="aicg-research-edit-panel" style="display:none;">
                    <textarea id="aicg-research-corrections" rows="3" class="large-text" placeholder="Опишите, что нужно изменить..."></textarea>
                    <button type="button" class="button button-primary" id="aicg-research-edit-submit">Применить правки</button>
                </div>
            </div>
        </div>

        <!-- 2.3 Стиль -->
        <div class="aicg-substep" id="aicg-substep-style" style="display:none;">
            <h3>2.3 — Стиль написания</h3>
            <div class="aicg-style-cards">
                <div class="aicg-style-card" data-style="egorka">
                    <strong>Егорка</strong>
                    <p>Живо, с юмором, разговорный язык, короткие абзацы, метафоры.</p>
                </div>
                <div class="aicg-style-card selected" data-style="not-egorka">
                    <strong>Стандартный</strong>
                    <p>Профессионально, экспертно, структурированно, деловой тон.</p>
                </div>
            </div>
            <button type="button" class="button button-primary" id="aicg-style-confirm">Генерировать статью</button>
        </div>

        <!-- 2.4 Статья -->
        <div class="aicg-substep" id="aicg-substep-article" style="display:none;">
            <h3>2.4 — Сгенерированная статья</h3>
            <div class="aicg-loading" id="aicg-article-loading" style="display:none;">
                <span class="spinner is-active"></span> Генерирую статью...
            </div>
            <div id="aicg-article-result" style="display:none;">
                <textarea id="aicg-article-text" rows="15" class="large-text" readonly></textarea>
                <button type="button" class="button button-primary" id="aicg-create-html">Создать HTML</button>
            </div>
        </div>

        <!-- 2.5 HTML -->
        <div class="aicg-substep" id="aicg-substep-html" style="display:none;">
            <h3>2.5 — HTML-версия</h3>
            <div class="aicg-loading" id="aicg-html-loading" style="display:none;">
                <span class="spinner is-active"></span> Конвертирую в HTML...
            </div>
            <div id="aicg-html-result" style="display:none;">
                <div class="aicg-tabs">
                    <button type="button" class="aicg-tab active" data-tab="preview">Превью</button>
                    <button type="button" class="aicg-tab" data-tab="code">Код</button>
                </div>
                <div class="aicg-tab-content active" id="aicg-html-preview"></div>
                <textarea class="aicg-tab-content large-text" id="aicg-html-code" rows="12" style="display:none;" readonly></textarea>
                <button type="button" class="button button-primary" id="aicg-go-factcheck">Перейти к факт-чеку</button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  STEP 3: Факт-чек                                         -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="aicg-step" id="aicg-step-3" style="display:none;">
        <h2>Шаг 3: Факт-чек</h2>

        <div class="aicg-loading" id="aicg-factcheck-loading" style="display:none;">
            <span class="spinner is-active"></span> Проверяю факты...
        </div>

        <div id="aicg-factcheck-result" style="display:none;">
            <div id="aicg-factcheck-success" class="aicg-notice aicg-notice-success" style="display:none;">
                Факт-чек пройден! Ошибок не обнаружено.
            </div>
            <div id="aicg-factcheck-errors" style="display:none;">
                <div class="aicg-notice aicg-notice-error">Обнаружены ошибки:</div>
                <ul id="aicg-errors-list"></ul>
                <p id="aicg-factcheck-summary"></p>
                <div class="aicg-plan-actions">
                    <button type="button" class="button button-primary" id="aicg-fix-errors">Исправить ошибки (Claude)</button>
                    <button type="button" class="button" id="aicg-skip-factcheck">Пропустить и продолжить</button>
                </div>
            </div>
        </div>

        <div id="aicg-fix-loading" class="aicg-loading" style="display:none;">
            <span class="spinner is-active"></span> Исправляю ошибки...
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  STEP 4: Хуманизация & Экспорт                            -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="aicg-step" id="aicg-step-4" style="display:none;">
        <h2>Шаг 4: Хуманизация и экспорт</h2>

        <div class="aicg-loading" id="aicg-humanize-loading" style="display:none;">
            <span class="spinner is-active"></span> Хуманизирую текст...
        </div>

        <div id="aicg-final-result" style="display:none;">
            <textarea id="aicg-final-text" rows="15" class="large-text" readonly></textarea>

            <!-- Экспорт -->
            <div class="aicg-export-panel">
                <h3>Экспорт</h3>
                <div class="aicg-export-actions">
                    <button type="button" class="button" id="aicg-copy-text">Копировать текст</button>
                    <button type="button" class="button" id="aicg-download-txt">Скачать .txt</button>
                    <button type="button" class="button" id="aicg-download-html">Скачать .html</button>
                </div>
            </div>

            <!-- Публикация в WordPress -->
            <div class="aicg-export-panel">
                <h3>Опубликовать как запись WordPress</h3>
                <div class="aicg-fields-grid">
                    <div class="aicg-field">
                        <label for="aicg-wp-title">Заголовок:</label>
                        <input type="text" id="aicg-wp-title" class="regular-text" />
                    </div>
                    <div class="aicg-field">
                        <label for="aicg-wp-category">Категория:</label>
                        <select id="aicg-wp-category">
                            <option value="">Без категории</option>
                        </select>
                    </div>
                    <div class="aicg-field">
                        <label for="aicg-wp-status">Статус:</label>
                        <select id="aicg-wp-status">
                            <option value="draft">Черновик</option>
                            <option value="pending">На модерации</option>
                            <option value="publish">Опубликовать</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="button button-primary" id="aicg-wp-publish">Опубликовать</button>
                <div id="aicg-wp-result" style="display:none;">
                    <div class="aicg-notice aicg-notice-success">
                        Запись создана! <a id="aicg-wp-link" href="#" target="_blank">Открыть</a>
                    </div>
                </div>
            </div>

            <!-- Начать заново -->
            <div class="aicg-actions" style="margin-top:20px;">
                <button type="button" class="button" id="aicg-start-over">Начать заново</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="aicg-toast" class="aicg-toast" style="display:none;"></div>
</div>
