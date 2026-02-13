/**
 * API Service Layer
 *
 * All API calls are abstracted here. Replace BASE_URL and endpoint
 * implementations when the backend is ready.
 *
 * Each method returns a Promise that resolves with the expected data shape.
 * Currently uses mock/placeholder implementations for development.
 */

const ApiService = (() => {
  // ── Configuration ──────────────────────────────────────────────
  const CONFIG = {
    SUPADATA_API_KEY: '',       // Set your Supadata API key
    SUPADATA_BASE_URL: 'https://api.supadata.ai/v1',
    CLAUDE_API_KEY: '',         // Set your Claude API key
    CLAUDE_BASE_URL: 'https://api.anthropic.com/v1',
    OPENAI_API_KEY: '',         // Set your OpenAI API key
    OPENAI_BASE_URL: 'https://api.openai.com/v1',
    GEMINI_API_KEY: '',         // Set your Gemini API key
    GEMINI_BASE_URL: 'https://generativelanguage.googleapis.com/v1beta',
    // Backend proxy URL (recommended for production to hide API keys)
    BACKEND_URL: '',            // e.g. 'https://your-backend.com/api'
  };

  // ── Helpers ────────────────────────────────────────────────────

  /** Simple delay helper for mock responses */
  function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /** Generic fetch wrapper with error handling */
  async function request(url, options = {}) {
    try {
      const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...options.headers },
        ...options,
      });
      if (!response.ok) {
        const errorBody = await response.text();
        throw new Error(`API Error ${response.status}: ${errorBody}`);
      }
      return await response.json();
    } catch (err) {
      console.error('[API]', err);
      throw err;
    }
  }

  // ── 1. YouTube Transcription (Supadata) ────────────────────────

  /**
   * Transcribe a YouTube video to text.
   * @param {string} youtubeUrl - Full YouTube video URL
   * @returns {Promise<{text: string}>}
   */
  async function transcribeYouTube(youtubeUrl) {
    // TODO: Replace with real Supadata API call
    // Real implementation:
    // return request(`${CONFIG.SUPADATA_BASE_URL}/youtube/transcript`, {
    //   method: 'POST',
    //   headers: { 'x-api-key': CONFIG.SUPADATA_API_KEY },
    //   body: JSON.stringify({ url: youtubeUrl, lang: 'ru' }),
    // });

    await delay(2000);
    return {
      text: `[MOCK] Транскрипция видео: ${youtubeUrl}\n\nЗдесь будет реальный текст из видео после подключения Supadata API.\n\nВидео обсуждает ключевые аспекты разработки контент-стратегии для digital-продуктов. Спикер рассматривает важность структурированного подхода к созданию текстов, где каждый этап — от выбора темы до финальной редактуры — играет критическую роль в качестве итогового материала.`,
    };
  }

  // ── 2. Create Article Plan (Claude) ────────────────────────────

  /**
   * Generate an article plan/structure via Claude.
   * @param {object} params - Article parameters from Step 1
   * @param {string} params.topic - Article topic
   * @param {string} params.audience - Target audience
   * @param {string} params.goal - Article goal
   * @param {string} params.type - Article type
   * @param {string} params.platform - Publication platform
   * @param {string} params.volume - Desired volume
   * @param {boolean} params.needsResearch - Whether research is needed
   * @param {string} [params.sourceText] - Source text from YouTube transcript
   * @returns {Promise<{plan: string}>}
   */
  async function createPlan(params) {
    // TODO: Replace with real Claude API call using the "Создание структуры статьи" template
    // Real implementation would send the template prompt with filled-in variables

    await delay(2500);
    const topic = params.topic || params.sourceText?.substring(0, 50) || 'Тема';
    return {
      plan: `План статьи: "${topic}"\n\n` +
        `1. Введение\n   - Зацепка для читателя\n   - Обозначение проблемы\n   - Обещание решения\n\n` +
        `2. Основная часть\n   2.1. Контекст и предыстория\n   2.2. Ключевые факты и данные\n   2.3. Практические рекомендации\n   2.4. Примеры и кейсы\n\n` +
        `3. Заключение\n   - Краткие выводы\n   - Призыв к действию\n\n` +
        `Целевая аудитория: ${params.audience || 'Не указана'}\n` +
        `Тип: ${params.type || 'Не указан'}\n` +
        `Объём: ${params.volume || 'Не указан'}`,
    };
  }

  // ── 2b. Edit Plan (Claude) ─────────────────────────────────────

  /**
   * Send corrections to Claude to edit the plan.
   * @param {string} currentPlan - Current plan text
   * @param {string} corrections - User's corrections
   * @returns {Promise<{plan: string}>}
   */
  async function editPlan(currentPlan, corrections) {
    // TODO: Replace with real Claude API call

    await delay(2000);
    return {
      plan: currentPlan + `\n\n--- Обновлено по правкам ---\n${corrections}\n(План скорректирован с учётом указанных замечаний)`,
    };
  }

  // ── 3. Collect Research (ChatGPT Research) ─────────────────────

  /**
   * Collect research information via ChatGPT Research.
   * @param {string} plan - Approved article plan
   * @returns {Promise<{research: string}>}
   */
  async function collectResearch(plan) {
    // TODO: Replace with real OpenAI API call using "Сбор информации для статьи" template

    await delay(3000);
    return {
      research: `Собранная информация по плану:\n\n` +
        `1. Статистика и данные:\n   - Рынок контент-маркетинга вырос на 15% в 2025 году\n   - 73% компаний используют AI для генерации контента\n\n` +
        `2. Экспертные мнения:\n   - Цитата из исследования McKinsey\n   - Комментарий отраслевого эксперта\n\n` +
        `3. Практические примеры:\n   - Кейс компании A: рост трафика на 40%\n   - Кейс компании B: увеличение конверсии на 25%\n\n` +
        `4. Источники:\n   - research-paper-1.pdf\n   - industry-report-2025.pdf`,
    };
  }

  // ── 3b. Edit Research (ChatGPT) ────────────────────────────────

  /**
   * Send corrections to ChatGPT to edit the research.
   * @param {string} currentResearch - Current research text
   * @param {string} corrections - User's corrections
   * @returns {Promise<{research: string}>}
   */
  async function editResearch(currentResearch, corrections) {
    // TODO: Replace with real OpenAI API call

    await delay(2000);
    return {
      research: currentResearch + `\n\n--- Обновлено по правкам ---\n${corrections}\n(Информация дополнена с учётом замечаний)`,
    };
  }

  // ── 4. Generate Article (Claude) ───────────────────────────────

  /**
   * Generate the full article via Claude.
   * @param {string} plan - Approved plan
   * @param {string|null} research - Collected research (if any)
   * @param {string} style - Writing style ('egorka' or 'not-egorka')
   * @returns {Promise<{article: string}>}
   */
  async function generateArticle(plan, research, style) {
    // TODO: Replace with real Claude API call using "Генерация статьи" template + style

    await delay(3500);
    const styleName = style === 'egorka' ? 'Егорка' : 'Стандартный';
    return {
      article: `# Как AI меняет контент-маркетинг в 2025 году\n\n` +
        `*Стиль: ${styleName}*\n\n` +
        `Контент-маркетинг переживает фундаментальную трансформацию. С появлением продвинутых языковых моделей процесс создания текстов стал не просто быстрее — он стал качественно другим.\n\n` +
        `## Почему это важно\n\n` +
        `По данным последних исследований, 73% компаний уже внедрили AI-инструменты в свои контент-процессы. Рынок вырос на 15% только за последний год.\n\n` +
        `## Практические рекомендации\n\n` +
        `1. **Начните с плана** — структура определяет качество\n` +
        `2. **Используйте факт-чек** — AI может ошибаться\n` +
        `3. **Хуманизируйте текст** — читатели ценят живой стиль\n\n` +
        `## Кейсы\n\n` +
        `Компания A внедрила AI-генерацию и увеличила трафик на 40%. Компания B сфокусировалась на качестве и подняла конверсию на 25%.\n\n` +
        `## Заключение\n\n` +
        `AI — не замена автору, а мощный инструмент. Ключ к успеху — в правильном сочетании технологий и человеческой экспертизы.`,
    };
  }

  // ── 5. Convert to HTML (ChatGPT) ──────────────────────────────

  /**
   * Convert article text to formatted HTML via ChatGPT.
   * @param {string} articleText - Plain text article
   * @returns {Promise<{html: string}>}
   */
  async function convertToHtml(articleText) {
    // TODO: Replace with real OpenAI API call with HTML formatting instructions

    await delay(2000);
    // Simple markdown-like to HTML conversion for mock
    let html = articleText
      .replace(/^# (.+)$/gm, '<h1>$1</h1>')
      .replace(/^## (.+)$/gm, '<h2>$1</h2>')
      .replace(/^### (.+)$/gm, '<h3>$1</h3>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
      .replace(/\n\n/g, '</p><p>')
      .replace(/\n/g, '<br>');
    html = `<article>\n<p>${html}</p>\n</article>`;
    return { html };
  }

  // ── 6. Fact-check (Gemini / GPT) ──────────────────────────────

  /**
   * Run fact-check on the generated article.
   * @param {string} articleText - Article text to check
   * @returns {Promise<{isValid: boolean, errors: string[]}>}
   */
  async function factCheck(articleText) {
    // TODO: Replace with real Gemini API call

    await delay(2500);
    // Mock: randomly decide if there are errors (for demo purposes, return clean)
    return {
      isValid: true,
      errors: [],
      summary: 'Факт-чек пройден. Существенных фактических ошибок не обнаружено.',
    };
  }

  // ── 6b. Fix Errors in Article (Claude) ─────────────────────────

  /**
   * Fix factual errors in the article via Claude.
   * @param {string} articleText - Current article text
   * @param {string[]} errors - List of errors to fix
   * @returns {Promise<{article: string}>}
   */
  async function fixErrors(articleText, errors) {
    // TODO: Replace with real Claude API call

    await delay(2500);
    return {
      article: articleText + '\n\n[Текст исправлен с учётом замечаний факт-чека]',
    };
  }

  // ── 7. Humanize (Claude) ──────────────────────────────────────

  /**
   * Humanize the article text via Claude.
   * @param {string} articleText - Article to humanize
   * @returns {Promise<{text: string}>}
   */
  async function humanize(articleText) {
    // TODO: Replace with real Claude API call using humanization template

    await delay(2500);
    return {
      text: articleText + '\n\n---\n[Текст прошёл хуманизацию — стиль приближен к живому авторскому письму]',
    };
  }

  // ── Public API ─────────────────────────────────────────────────
  return {
    CONFIG,
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
  };
})();
