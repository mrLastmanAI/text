/**
 * AI Content Generator — WordPress Admin Wizard
 *
 * 4-шаговый визард для генерации статей.
 * Работает через WP REST API: /wp-json/aicg/v1/...
 */

(function () {
  'use strict';

  if (typeof aicgConfig === 'undefined') return;

  const API = aicgConfig.restUrl;
  const NONCE = aicgConfig.nonce;

  // ── State ───────────────────────────────────────────────────────
  const state = {
    sourceType: 'text',
    transcript: '',
    plan: '',
    research: '',
    style: 'not-egorka',
    article: '',
    articleHtml: '',
    needsResearch: false,
    factCheckErrors: [],
  };

  // ── DOM helpers ─────────────────────────────────────────────────
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);
  const show = (sel) => { const el = typeof sel === 'string' ? $(sel) : sel; if (el) el.style.display = ''; };
  const hide = (sel) => { const el = typeof sel === 'string' ? $(sel) : sel; if (el) el.style.display = 'none'; };

  // ── API ─────────────────────────────────────────────────────────
  async function api(endpoint, data) {
    const res = await fetch(API + endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': NONCE,
      },
      body: JSON.stringify(data),
    });
    const json = await res.json();
    if (!res.ok) {
      throw new Error(json.message || json.data?.message || 'Ошибка сервера');
    }
    return json;
  }

  // ── Toast ───────────────────────────────────────────────────────
  function toast(msg, type = 'info') {
    const el = $('#aicg-toast');
    if (!el) return;
    el.textContent = msg;
    el.className = 'aicg-toast aicg-toast-' + type;
    show(el);
    setTimeout(() => hide(el), 4000);
  }

  // ── Steps ───────────────────────────────────────────────────────
  function goToStep(n) {
    $$('.aicg-step').forEach((s) => (s.style.display = 'none'));
    const step = $(`#aicg-step-${n}`);
    if (step) step.style.display = '';

    $$('.aicg-step-indicator').forEach((ind) => {
      const s = parseInt(ind.dataset.step);
      ind.classList.toggle('active', s === n);
      ind.classList.toggle('completed', s < n);
    });
  }

  // ── Validate step 1 ────────────────────────────────────────────
  function validateStep1() {
    const btn = $('#aicg-step1-next');
    if (!btn) return;

    if (state.sourceType === 'youtube') {
      btn.disabled = !state.transcript;
      return;
    }

    const topic = $('#aicg-topic')?.value.trim();
    const audience = getSelectValue('aicg-audience');
    const goal = getSelectValue('aicg-goal');
    const type = getSelectValue('aicg-type');
    const platform = getSelectValue('aicg-platform');
    const volume = $('#aicg-volume')?.value;

    btn.disabled = !(topic && audience && goal && type && platform && volume);
  }

  function getSelectValue(id) {
    const sel = $(`#${id}`);
    if (!sel) return '';
    if (sel.value === 'other') {
      return $(`#${id}-custom`)?.value.trim() || '';
    }
    return sel.value;
  }

  // ── Setup "other" selects ──────────────────────────────────────
  function setupOtherSelects() {
    ['aicg-audience', 'aicg-goal', 'aicg-type', 'aicg-platform'].forEach((id) => {
      const sel = $(`#${id}`);
      const custom = $(`#${id}-custom`);
      if (!sel || !custom) return;

      sel.addEventListener('change', () => {
        custom.style.display = sel.value === 'other' ? '' : 'none';
        validateStep1();
      });
      custom.addEventListener('input', validateStep1);
    });
  }

  // ══════════════════════════════════════════════════════════════
  //  STEP 1: Подготовка
  // ══════════════════════════════════════════════════════════════

  function bindStep1() {
    // Source toggle
    $$('input[name="aicg-source"]').forEach((radio) => {
      radio.addEventListener('change', () => {
        state.sourceType = radio.value;
        if (radio.value === 'youtube') {
          show('#aicg-youtube-panel');
          hide('#aicg-params-panel');
        } else {
          hide('#aicg-youtube-panel');
          show('#aicg-params-panel');
        }
        validateStep1();
      });
    });

    // YouTube transcription
    $('#aicg-transcribe-btn')?.addEventListener('click', async () => {
      const url = $('#aicg-youtube-url')?.value.trim();
      if (!url) { toast('Вставьте ссылку на YouTube', 'error'); return; }

      const btn = $('#aicg-transcribe-btn');
      btn.disabled = true;
      btn.textContent = 'Транскрибирую...';

      try {
        const data = await api('transcribe', { url });
        state.transcript = data.text;
        $('#aicg-transcript-text').value = data.text;
        show('#aicg-transcript-result');
        // Show params panel for YouTube too
        show('#aicg-params-panel');
        toast('Транскрипция получена!', 'success');
        validateStep1();
      } catch (e) {
        toast(e.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Транскрибировать';
      }
    });

    // Form inputs for validation
    ['#aicg-topic', '#aicg-volume'].forEach((sel) => {
      $(sel)?.addEventListener('input', validateStep1);
    });
    $('#aicg-volume')?.addEventListener('change', validateStep1);
    $('#aicg-needs-research')?.addEventListener('change', () => {
      state.needsResearch = $('#aicg-needs-research').checked;
    });

    setupOtherSelects();

    // Next button
    $('#aicg-step1-next')?.addEventListener('click', () => {
      goToStep(2);
      generatePlan();
    });
  }

  // ══════════════════════════════════════════════════════════════
  //  STEP 2: Генерация
  // ══════════════════════════════════════════════════════════════

  async function generatePlan() {
    show('#aicg-plan-loading');
    hide('#aicg-plan-result');

    const params = {
      action: 'create',
      topic: $('#aicg-topic')?.value.trim() || '',
      audience: getSelectValue('aicg-audience'),
      goal: getSelectValue('aicg-goal'),
      type: getSelectValue('aicg-type'),
      platform: getSelectValue('aicg-platform'),
      volume: $('#aicg-volume')?.value || '',
      needsResearch: state.needsResearch,
    };

    if (state.transcript) {
      params.sourceText = state.transcript;
    }

    try {
      const data = await api('plan', params);
      state.plan = data.plan;
      $('#aicg-plan-text').value = data.plan;
      show('#aicg-plan-result');
      toast('План создан!', 'success');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-plan-loading');
    }
  }

  function bindStep2() {
    // Plan actions
    $('#aicg-plan-approve')?.addEventListener('click', () => {
      if (state.needsResearch) {
        show('#aicg-substep-research');
        collectResearch();
      } else {
        show('#aicg-substep-style');
      }
    });

    $('#aicg-plan-edit-toggle')?.addEventListener('click', () => {
      const panel = $('#aicg-plan-edit-panel');
      panel.style.display = panel.style.display === 'none' ? '' : 'none';
    });

    $('#aicg-plan-edit-submit')?.addEventListener('click', async () => {
      const corrections = $('#aicg-plan-corrections')?.value.trim();
      if (!corrections) return;

      show('#aicg-plan-loading');
      hide('#aicg-plan-result');

      try {
        const data = await api('plan', { action: 'edit', plan: state.plan, corrections });
        state.plan = data.plan;
        $('#aicg-plan-text').value = data.plan;
        $('#aicg-plan-corrections').value = '';
        hide('#aicg-plan-edit-panel');
        show('#aicg-plan-result');
        toast('План обновлён!', 'success');
      } catch (e) {
        toast(e.message, 'error');
        show('#aicg-plan-result');
      } finally {
        hide('#aicg-plan-loading');
      }
    });

    $('#aicg-plan-regenerate')?.addEventListener('click', generatePlan);

    // Research actions
    $('#aicg-research-approve')?.addEventListener('click', () => {
      show('#aicg-substep-style');
    });

    $('#aicg-research-edit-toggle')?.addEventListener('click', () => {
      const panel = $('#aicg-research-edit-panel');
      panel.style.display = panel.style.display === 'none' ? '' : 'none';
    });

    $('#aicg-research-edit-submit')?.addEventListener('click', async () => {
      const corrections = $('#aicg-research-corrections')?.value.trim();
      if (!corrections) return;

      show('#aicg-research-loading');
      hide('#aicg-research-result');

      try {
        const data = await api('research', { action: 'edit', research: state.research, corrections });
        state.research = data.research;
        $('#aicg-research-text').value = data.research;
        $('#aicg-research-corrections').value = '';
        hide('#aicg-research-edit-panel');
        show('#aicg-research-result');
        toast('Ресёрч обновлён!', 'success');
      } catch (e) {
        toast(e.message, 'error');
        show('#aicg-research-result');
      } finally {
        hide('#aicg-research-loading');
      }
    });

    $('#aicg-research-regenerate')?.addEventListener('click', collectResearch);

    // Style selection
    $$('.aicg-style-card').forEach((card) => {
      card.addEventListener('click', () => {
        $$('.aicg-style-card').forEach((c) => c.classList.remove('selected'));
        card.classList.add('selected');
        state.style = card.dataset.style;
      });
    });

    $('#aicg-style-confirm')?.addEventListener('click', () => {
      show('#aicg-substep-article');
      generateArticle();
    });

    // HTML
    $('#aicg-create-html')?.addEventListener('click', createHtml);
    $('#aicg-go-factcheck')?.addEventListener('click', () => {
      goToStep(3);
      runFactCheck();
    });

    // Tabs
    $$('.aicg-tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        $$('.aicg-tab').forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');
        if (tab.dataset.tab === 'preview') {
          show('#aicg-html-preview');
          hide('#aicg-html-code');
        } else {
          hide('#aicg-html-preview');
          show('#aicg-html-code');
        }
      });
    });
  }

  async function collectResearch() {
    show('#aicg-research-loading');
    hide('#aicg-research-result');

    try {
      const data = await api('research', { action: 'collect', plan: state.plan });
      state.research = data.research;
      $('#aicg-research-text').value = data.research;
      show('#aicg-research-result');
      toast('Информация собрана!', 'success');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-research-loading');
    }
  }

  async function generateArticle() {
    show('#aicg-article-loading');
    hide('#aicg-article-result');

    try {
      const data = await api('generate', {
        plan: state.plan,
        research: state.research,
        style: state.style,
      });
      state.article = data.article;
      $('#aicg-article-text').value = data.article;
      show('#aicg-article-result');
      toast('Статья сгенерирована!', 'success');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-article-loading');
    }
  }

  async function createHtml() {
    show('#aicg-substep-html');
    show('#aicg-html-loading');
    hide('#aicg-html-result');

    try {
      const data = await api('html-convert', { text: state.article });
      state.articleHtml = data.html;
      $('#aicg-html-preview').innerHTML = data.html;
      $('#aicg-html-code').value = data.html;
      show('#aicg-html-result');
      toast('HTML создан!', 'success');
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-html-loading');
    }
  }

  // ══════════════════════════════════════════════════════════════
  //  STEP 3: Факт-чек
  // ══════════════════════════════════════════════════════════════

  async function runFactCheck() {
    show('#aicg-factcheck-loading');
    hide('#aicg-factcheck-result');

    try {
      const data = await api('factcheck', { text: state.article });

      show('#aicg-factcheck-result');

      if (data.isValid) {
        show('#aicg-factcheck-success');
        hide('#aicg-factcheck-errors');
        toast('Факт-чек пройден!', 'success');
        // Автопереход к хуманизации через 1.5 сек
        setTimeout(() => {
          goToStep(4);
          runHumanize();
        }, 1500);
      } else {
        hide('#aicg-factcheck-success');
        show('#aicg-factcheck-errors');
        state.factCheckErrors = data.errors || [];
        const list = $('#aicg-errors-list');
        list.innerHTML = '';
        data.errors.forEach((err) => {
          const li = document.createElement('li');
          li.textContent = err;
          list.appendChild(li);
        });
        $('#aicg-factcheck-summary').textContent = data.summary || '';
        toast('Обнаружены ошибки!', 'error');
      }
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-factcheck-loading');
    }
  }

  function bindStep3() {
    $('#aicg-fix-errors')?.addEventListener('click', async () => {
      show('#aicg-fix-loading');
      hide('#aicg-factcheck-result');

      try {
        const data = await api('fix-errors', {
          text: state.article,
          errors: state.factCheckErrors,
        });
        state.article = data.article;
        $('#aicg-article-text').value = data.article;
        toast('Ошибки исправлены! Перепроверяю...', 'success');
        // Перезапускаем факт-чек
        runFactCheck();
      } catch (e) {
        toast(e.message, 'error');
        show('#aicg-factcheck-result');
      } finally {
        hide('#aicg-fix-loading');
      }
    });

    $('#aicg-skip-factcheck')?.addEventListener('click', () => {
      goToStep(4);
      runHumanize();
    });
  }

  // ══════════════════════════════════════════════════════════════
  //  STEP 4: Хуманизация & Экспорт
  // ══════════════════════════════════════════════════════════════

  async function runHumanize() {
    show('#aicg-humanize-loading');
    hide('#aicg-final-result');

    try {
      const data = await api('humanize', { text: state.article });
      $('#aicg-final-text').value = data.text;
      show('#aicg-final-result');
      toast('Текст хуманизирован!', 'success');
      loadWpCategories();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      hide('#aicg-humanize-loading');
    }
  }

  async function loadWpCategories() {
    try {
      const res = await fetch(aicgConfig.adminUrl + 'admin-ajax.php', { credentials: 'same-origin' });
      // Use WP REST for categories
      const catsRes = await fetch('/wp-json/wp/v2/categories?per_page=100', {
        headers: { 'X-WP-Nonce': NONCE },
      });
      if (catsRes.ok) {
        const cats = await catsRes.json();
        const sel = $('#aicg-wp-category');
        cats.forEach((cat) => {
          const opt = document.createElement('option');
          opt.value = cat.id;
          opt.textContent = cat.name;
          sel.appendChild(opt);
        });
      }
    } catch (e) {
      // Категории необязательны
    }
  }

  function bindStep4() {
    // Копировать
    $('#aicg-copy-text')?.addEventListener('click', () => {
      const text = $('#aicg-final-text')?.value;
      if (!text) return;
      navigator.clipboard.writeText(text).then(
        () => toast('Скопировано!', 'success'),
        () => toast('Не удалось скопировать', 'error')
      );
    });

    // Скачать TXT
    $('#aicg-download-txt')?.addEventListener('click', () => {
      download($('#aicg-final-text')?.value || '', 'article.txt', 'text/plain');
    });

    // Скачать HTML
    $('#aicg-download-html')?.addEventListener('click', () => {
      download(state.articleHtml || $('#aicg-final-text')?.value || '', 'article.html', 'text/html');
    });

    // Публикация в WP
    $('#aicg-wp-publish')?.addEventListener('click', async () => {
      const title   = $('#aicg-wp-title')?.value.trim();
      const content = state.articleHtml || $('#aicg-final-text')?.value || '';
      const status  = $('#aicg-wp-status')?.value || 'draft';
      const catId   = $('#aicg-wp-category')?.value;

      if (!title) { toast('Укажите заголовок', 'error'); return; }

      const btn = $('#aicg-wp-publish');
      btn.disabled = true;
      btn.textContent = 'Публикую...';

      try {
        const postData = { title, content, status };
        if (catId) postData.categories = [parseInt(catId)];

        const res = await fetch('/wp-json/wp/v2/posts', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': NONCE,
          },
          body: JSON.stringify(postData),
        });

        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Ошибка публикации');

        $('#aicg-wp-link').href = data.link;
        show('#aicg-wp-result');
        toast('Запись опубликована!', 'success');
      } catch (e) {
        toast(e.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Опубликовать';
      }
    });

    // Начать заново
    $('#aicg-start-over')?.addEventListener('click', () => {
      if (!confirm('Начать заново? Текущий прогресс будет потерян.')) return;
      location.reload();
    });
  }

  // ── Download helper ─────────────────────────────────────────────
  function download(content, filename, mime) {
    const blob = new Blob([content], { type: mime + ';charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  // ── Init ────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    if (!$('#aicg-app')) return;
    bindStep1();
    bindStep2();
    bindStep3();
    bindStep4();
    goToStep(1);
  });
})();
