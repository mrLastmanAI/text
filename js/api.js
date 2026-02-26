/**
 * API Service Layer
 *
 * Все запросы идут к PHP-бэкенду (api/*.php).
 * API-ключи хранятся ТОЛЬКО на сервере в api/config.php.
 * Фронтенд не имеет доступа к секретам.
 */

const ApiService = (() => {
  // ── Configuration ──────────────────────────────────────────────
  const API_BASE = 'api';  // Путь к PHP-эндпоинтам относительно index.html

  // ── Helpers ────────────────────────────────────────────────────

  /**
   * POST JSON к PHP-бэкенду.
   * @param {string} endpoint - Имя PHP-файла (без расширения)
   * @param {object} data - Тело запроса
   * @returns {Promise<object>}
   */
  async function post(endpoint, data) {
    const url = `${API_BASE}/${endpoint}.php`;

    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });

    const result = await response.json().catch(() => null);

    if (!response.ok) {
      const errMsg = result?.error || `Ошибка сервера: ${response.status}`;
      throw new Error(errMsg);
    }

    return result;
  }

  // ── 1. YouTube Transcription ───────────────────────────────────

  /**
   * Транскрипция YouTube видео (Supadata через PHP).
   * @param {string} youtubeUrl
   * @returns {Promise<{text: string}>}
   */
  async function transcribeYouTube(youtubeUrl) {
    return post('transcribe', { url: youtubeUrl });
  }

  // ── 2. Create Article Plan (Claude) ────────────────────────────

  /**
   * Создание плана статьи через Claude.
   * @param {object} params
   * @returns {Promise<{plan: string}>}
   */
  async function createPlan(params) {
    return post('plan', {
      action: 'create',
      topic: params.topic || '',
      audience: params.audience || '',
      goal: params.goal || '',
      type: params.type || '',
      platform: params.platform || '',
      volume: params.volume || '',
      needsResearch: params.needsResearch || false,
      sourceText: params.sourceText || '',
    });
  }

  // ── 2b. Edit Plan (Claude) ─────────────────────────────────────

  /**
   * Редактирование плана через Claude.
   * @param {string} currentPlan
   * @param {string} corrections
   * @returns {Promise<{plan: string}>}
   */
  async function editPlan(currentPlan, corrections) {
    return post('plan', {
      action: 'edit',
      plan: currentPlan,
      corrections: corrections,
    });
  }

  // ── 3. Collect Research (ChatGPT) ──────────────────────────────

  /**
   * Сбор информации через ChatGPT Research.
   * @param {string} plan
   * @returns {Promise<{research: string}>}
   */
  async function collectResearch(plan) {
    return post('research', {
      action: 'collect',
      plan: plan,
    });
  }

  // ── 3b. Edit Research (ChatGPT) ────────────────────────────────

  /**
   * Редактирование собранной информации.
   * @param {string} currentResearch
   * @param {string} corrections
   * @returns {Promise<{research: string}>}
   */
  async function editResearch(currentResearch, corrections) {
    return post('research', {
      action: 'edit',
      research: currentResearch,
      corrections: corrections,
    });
  }

  // ── 4. Generate Article (Claude) ───────────────────────────────

  /**
   * Генерация статьи через Claude.
   * @param {string} plan
   * @param {string|null} research
   * @param {string} style - 'egorka' | 'not-egorka'
   * @returns {Promise<{article: string}>}
   */
  async function generateArticle(plan, research, style) {
    return post('generate', {
      plan: plan,
      research: research || '',
      style: style,
    });
  }

  // ── 5. Convert to HTML (ChatGPT) ──────────────────────────────

  /**
   * Конвертация текста в HTML.
   * @param {string} articleText
   * @returns {Promise<{html: string}>}
   */
  async function convertToHtml(articleText) {
    return post('html-convert', { text: articleText });
  }

  // ── 6. Fact-check (Gemini) ─────────────────────────────────────

  /**
   * Факт-чек через Gemini.
   * @param {string} articleText
   * @returns {Promise<{isValid: boolean, errors: string[], summary: string}>}
   */
  async function factCheck(articleText) {
    return post('factcheck', { text: articleText });
  }

  // ── 6b. Fix Errors (Claude) ────────────────────────────────────

  /**
   * Исправление ошибок через Claude.
   * @param {string} articleText
   * @param {string[]} errors
   * @returns {Promise<{article: string}>}
   */
  async function fixErrors(articleText, errors) {
    return post('fix-errors', { text: articleText, errors: errors });
  }

  // ── 7. Humanize (Claude) ──────────────────────────────────────

  /**
   * Хуманизация текста через Claude.
   * @param {string} articleText
   * @returns {Promise<{text: string}>}
   */
  async function humanize(articleText) {
    return post('humanize', { text: articleText });
  }

  // ── 8. WordPress ─────────────────────────────────────────────────

  /**
   * Получить список категорий WordPress.
   * @returns {Promise<{categories: Array<{id: number, name: string, slug: string}>}>}
   */
  async function wpGetCategories() {
    return post('wp-publish', { action: 'categories' });
  }

  /**
   * Опубликовать статью в WordPress.
   * @param {object} data
   * @param {string} data.title
   * @param {string} data.content - HTML-контент
   * @param {string} data.status - 'draft' | 'publish' | 'pending'
   * @param {number[]} [data.categories]
   * @returns {Promise<{id: number, link: string, status: string, title: string}>}
   */
  async function wpPublish(data) {
    return post('wp-publish', {
      action: 'publish',
      title: data.title,
      content: data.content,
      status: data.status || 'draft',
      categories: data.categories || [],
    });
  }

  // ── Public API ─────────────────────────────────────────────────
  return {
    transcribeYouTube,
    createPlan,
    editPlan,
    collectResearch,
    editResearch,
    generateArticle,
    convertToHtml,
    factCheck,
    fixErrors,
    humanize,
    wpGetCategories,
    wpPublish,
  };
})();
