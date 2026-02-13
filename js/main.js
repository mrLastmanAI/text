/**
 * Main Application Module
 *
 * Orchestrates the entire wizard workflow:
 * Step 1 → Step 2 (plan → research → style → article → HTML) → Step 3 (fact-check) → Step 4 (humanize → export)
 */

(function () {
  'use strict';

  const { $, $$, goToStep, show, hide, toggle, showLoading, hideLoading,
    setResultText, setResultHtml, enableBtn, disableBtn, setBtnLoading,
    toast, initTabs, setupOtherSelects, copyToClipboard, downloadFile,
    exportToGoogleDocs, validateStep1 } = UI;

  // ════════════════════════════════════════════════════════════════
  //  INITIALIZATION
  // ════════════════════════════════════════════════════════════════

  document.addEventListener('DOMContentLoaded', () => {
    setupOtherSelects();
    initTabs();
    bindStep1Events();
    bindStep2Events();
    bindStep3Events();
    bindStep4Events();
  });

  // ════════════════════════════════════════════════════════════════
  //  STEP 1: Подготовка
  // ════════════════════════════════════════════════════════════════

  function bindStep1Events() {
    // Source type selection
    const sourceSelect = $('#source-type');
    sourceSelect.addEventListener('change', () => {
      const val = sourceSelect.value;
      AppState.setSourceType(val);

      hide('#source-youtube');
      hide('#source-text');
      hide('#youtube-result');

      if (val === 'youtube') show('#source-youtube');
      if (val === 'text') show('#source-text');

      updateStep1Button();
    });

    // YouTube URL input
    $('#youtube-url').addEventListener('input', updateStep1Button);

    // Text form inputs — listen for changes to validate
    const textInputs = [
      '#article-topic', '#target-audience', '#article-goal',
      '#article-type', '#platform', '#volume',
      '#article-goal-custom', '#article-type-custom', '#platform-custom',
    ];
    textInputs.forEach(sel => {
      const el = $(sel);
      if (el) el.addEventListener('input', updateStep1Button);
      if (el) el.addEventListener('change', updateStep1Button);
    });

    // Submit button
    $('#btn-step1-submit').addEventListener('click', handleStep1Submit);

    // YouTube continue button
    $('#btn-youtube-continue').addEventListener('click', handleYouTubeContinue);
  }

  function updateStep1Button() {
    const valid = validateStep1();
    toggle('#btn-step1-submit', AppState.get().sourceType !== null);
    if (AppState.get().sourceType) {
      if (valid) enableBtn('#btn-step1-submit');
      else disableBtn('#btn-step1-submit');
    }
  }

  async function handleStep1Submit() {
    const state = AppState.get();

    if (state.sourceType === 'youtube') {
      // Transcribe YouTube video
      const url = $('#youtube-url').value.trim();
      AppState.setYouTubeUrl(url);
      setBtnLoading('#btn-step1-submit', true);

      try {
        const result = await ApiService.transcribeYouTube(url);
        AppState.setTranscript(result.text);
        setResultText('youtube-transcript', result.text);
        show('#youtube-result');
        hide('#btn-step1-submit');
        toast('Транскрипция получена', 'success');
      } catch (err) {
        toast('Ошибка транскрипции: ' + err.message, 'error');
        setBtnLoading('#btn-step1-submit', false, 'Отправить');
      }
    } else if (state.sourceType === 'text') {
      // Collect form data and go to Step 2
      collectTextFormData();
      startStep2();
    }
  }

  function collectTextFormData() {
    AppState.setArticleParams({
      topic: $('#article-topic').value.trim(),
      audience: $('#target-audience').value.trim(),
      goal: $('#article-goal').value,
      goalCustom: $('#article-goal-custom')?.value?.trim() || '',
      type: $('#article-type').value,
      typeCustom: $('#article-type-custom')?.value?.trim() || '',
      platform: $('#platform').value,
      platformCustom: $('#platform-custom')?.value?.trim() || '',
      volume: $('#volume').value.trim(),
      needsResearch: $('#collect-info').checked,
    });
  }

  function handleYouTubeContinue() {
    // Collect optional YouTube article parameters
    AppState.setArticleParams({
      topic: $('#yt-article-topic')?.value?.trim() || '',
      audience: $('#yt-target-audience')?.value?.trim() || '',
      goal: $('#yt-article-goal')?.value || '',
      goalCustom: '',
      type: $('#yt-article-type')?.value || '',
      typeCustom: '',
      platform: '',
      platformCustom: '',
      volume: $('#yt-volume')?.value?.trim() || '',
      needsResearch: $('#yt-collect-info')?.checked || false,
    });
    startStep2();
  }

  // ════════════════════════════════════════════════════════════════
  //  STEP 2: Генерация
  // ════════════════════════════════════════════════════════════════

  function startStep2() {
    AppState.setCurrentStep(2);
    goToStep(2);
    generatePlan();
  }

  function bindStep2Events() {
    // Plan
    $('#btn-plan-approve').addEventListener('click', handlePlanApprove);
    $('#btn-plan-edit').addEventListener('click', () => {
      show('#plan-edit-block');
    });
    $('#btn-plan-send-edit').addEventListener('click', handlePlanSendEdit);

    // Research
    $('#btn-research-approve').addEventListener('click', handleResearchApprove);
    $('#btn-research-edit').addEventListener('click', () => {
      show('#research-edit-block');
    });
    $('#btn-research-send-edit').addEventListener('click', handleResearchSendEdit);

    // Style
    $('#writing-style').addEventListener('change', () => {
      const val = $('#writing-style').value;
      if (val) enableBtn('#btn-style-submit');
      else disableBtn('#btn-style-submit');
    });
    $('#btn-style-submit').addEventListener('click', handleStyleSubmit);

    // Article
    $('#btn-create-html').addEventListener('click', handleCreateHtml);
    $('#btn-to-factcheck').addEventListener('click', () => startStep3());

    // HTML
    $('#btn-copy-html').addEventListener('click', () => {
      copyToClipboard(AppState.get().articleHtml);
    });
    $('#btn-to-factcheck-html').addEventListener('click', () => startStep3());
  }

  // ── 2.1 Plan ───────────────────────────────────────────────────

  async function generatePlan() {
    show('#substep-plan');
    showLoading('plan-loading');
    hide('#plan-result');
    hide('#plan-edit-block');

    try {
      const params = AppState.getEffectiveParams();
      const result = await ApiService.createPlan(params);
      AppState.setPlan(result.plan);
      setResultText('plan-text', result.plan);
      show('#plan-result');
      toast('План статьи создан', 'success');
    } catch (err) {
      toast('Ошибка создания плана: ' + err.message, 'error');
    } finally {
      hideLoading('plan-loading');
    }
  }

  function handlePlanApprove() {
    AppState.setPlanApproved(true);
    hide('#plan-edit-block');
    disableBtn('#btn-plan-approve');
    disableBtn('#btn-plan-edit');

    const state = AppState.get();
    if (state.articleParams.needsResearch) {
      // Go to research
      startResearch();
    } else {
      // Skip research, go to style selection
      showStyleSelection();
    }
  }

  async function handlePlanSendEdit() {
    const corrections = $('#plan-corrections').value.trim();
    if (!corrections) {
      toast('Введите правки', 'error');
      return;
    }

    setBtnLoading('#btn-plan-send-edit', true);
    hide('#plan-result');
    showLoading('plan-loading');
    hide('#plan-edit-block');

    try {
      const result = await ApiService.editPlan(AppState.get().plan, corrections);
      AppState.setPlan(result.plan);
      setResultText('plan-text', result.plan);
      show('#plan-result');
      enableBtn('#btn-plan-approve');
      enableBtn('#btn-plan-edit');
      $('#plan-corrections').value = '';
      toast('План обновлён', 'success');
    } catch (err) {
      toast('Ошибка редактирования плана: ' + err.message, 'error');
    } finally {
      hideLoading('plan-loading');
      setBtnLoading('#btn-plan-send-edit', false, 'Отправить правки');
    }
  }

  // ── 2.2 Research ───────────────────────────────────────────────

  async function startResearch() {
    show('#substep-research');
    showLoading('research-loading');
    hide('#research-result');
    hide('#research-edit-block');

    try {
      const result = await ApiService.collectResearch(AppState.get().plan);
      AppState.setResearch(result.research);
      setResultText('research-text', result.research);
      show('#research-result');
      toast('Информация собрана', 'success');
    } catch (err) {
      toast('Ошибка сбора информации: ' + err.message, 'error');
    } finally {
      hideLoading('research-loading');
    }
  }

  function handleResearchApprove() {
    AppState.setResearchApproved(true);
    hide('#research-edit-block');
    disableBtn('#btn-research-approve');
    disableBtn('#btn-research-edit');
    showStyleSelection();
  }

  async function handleResearchSendEdit() {
    const corrections = $('#research-corrections').value.trim();
    if (!corrections) {
      toast('Введите правки', 'error');
      return;
    }

    setBtnLoading('#btn-research-send-edit', true);
    hide('#research-result');
    showLoading('research-loading');
    hide('#research-edit-block');

    try {
      const result = await ApiService.editResearch(AppState.get().research, corrections);
      AppState.setResearch(result.research);
      setResultText('research-text', result.research);
      show('#research-result');
      enableBtn('#btn-research-approve');
      enableBtn('#btn-research-edit');
      $('#research-corrections').value = '';
      toast('Информация обновлена', 'success');
    } catch (err) {
      toast('Ошибка редактирования: ' + err.message, 'error');
    } finally {
      hideLoading('research-loading');
      setBtnLoading('#btn-research-send-edit', false, 'Отправить правки');
    }
  }

  // ── 2.3 Style Selection ────────────────────────────────────────

  function showStyleSelection() {
    show('#substep-style');
    $('#writing-style').value = '';
    disableBtn('#btn-style-submit');

    // Scroll to style section
    $('#substep-style').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  async function handleStyleSubmit() {
    const style = $('#writing-style').value;
    if (!style) return;

    AppState.setWritingStyle(style);
    disableBtn('#btn-style-submit');

    // Generate article
    await generateArticle();
  }

  // ── 2.4 Article Generation ─────────────────────────────────────

  async function generateArticle() {
    show('#substep-article');
    showLoading('article-loading');
    hide('#article-result');

    try {
      const state = AppState.get();
      const result = await ApiService.generateArticle(
        state.plan,
        state.research || null,
        state.writingStyle,
      );
      AppState.setArticle(result.article);
      setResultText('article-text', result.article);
      show('#article-result');
      toast('Статья сгенерирована', 'success');

      // Scroll to article
      $('#substep-article').scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (err) {
      toast('Ошибка генерации статьи: ' + err.message, 'error');
    } finally {
      hideLoading('article-loading');
    }
  }

  // ── 2.5 HTML Conversion ────────────────────────────────────────

  async function handleCreateHtml() {
    show('#substep-html');
    showLoading('html-loading');
    hide('#html-result');
    setBtnLoading('#btn-create-html', true);

    try {
      const result = await ApiService.convertToHtml(AppState.get().article);
      AppState.setArticleHtml(result.html);

      // Preview
      setResultHtml('html-preview', result.html);
      // Source code
      setResultText('html-source', result.html);

      show('#html-result');
      toast('HTML-версия создана', 'success');
    } catch (err) {
      toast('Ошибка создания HTML: ' + err.message, 'error');
    } finally {
      hideLoading('html-loading');
      setBtnLoading('#btn-create-html', false, 'Создать HTML');
    }
  }

  // ════════════════════════════════════════════════════════════════
  //  STEP 3: Факт-чек
  // ════════════════════════════════════════════════════════════════

  function startStep3() {
    AppState.setCurrentStep(3);
    goToStep(3);
    runFactCheck();
  }

  function bindStep3Events() {
    $('#btn-to-humanize').addEventListener('click', () => startStep4());
    $('#btn-factcheck-fix').addEventListener('click', handleFactCheckFix);
    $('#btn-factcheck-recheck').addEventListener('click', () => runFactCheck());
  }

  async function runFactCheck() {
    showLoading('factcheck-loading');
    hide('#factcheck-ok');
    hide('#factcheck-errors');

    try {
      const result = await ApiService.factCheck(AppState.get().article);
      AppState.setFactCheckResult(result.isValid, result.errors, result.summary);

      if (result.isValid) {
        show('#factcheck-ok');
        toast('Факт-чек пройден', 'success');
      } else {
        show('#factcheck-errors');
        const errorsList = result.errors.map((e, i) => `${i + 1}. ${e}`).join('\n');
        setResultText('factcheck-errors-list', errorsList);
        $('#factcheck-manual-edit').value = errorsList;
        toast('Обнаружены несоответствия', 'error');
      }
    } catch (err) {
      toast('Ошибка факт-чека: ' + err.message, 'error');
    } finally {
      hideLoading('factcheck-loading');
    }
  }

  async function handleFactCheckFix() {
    const editedErrors = $('#factcheck-manual-edit').value.trim();
    if (!editedErrors) {
      toast('Список ошибок пуст', 'error');
      return;
    }

    setBtnLoading('#btn-factcheck-fix', true);
    showLoading('factcheck-loading');
    hide('#factcheck-errors');

    try {
      // Parse errors from text
      const errors = editedErrors.split('\n').filter(line => line.trim());

      // Fix via Claude
      const fixResult = await ApiService.fixErrors(AppState.get().article, errors);
      AppState.setArticle(fixResult.article);

      // Re-run fact-check
      hideLoading('factcheck-loading');
      toast('Текст исправлен, перепроверяем...', 'info');
      await runFactCheck();
    } catch (err) {
      toast('Ошибка исправления: ' + err.message, 'error');
      hideLoading('factcheck-loading');
    } finally {
      setBtnLoading('#btn-factcheck-fix', false, 'Отправить в Claude на исправление');
    }
  }

  // ════════════════════════════════════════════════════════════════
  //  STEP 4: Хуманизация
  // ════════════════════════════════════════════════════════════════

  function startStep4() {
    AppState.setCurrentStep(4);
    goToStep(4);
    runHumanize();
  }

  function bindStep4Events() {
    $('#btn-copy-text').addEventListener('click', () => {
      copyToClipboard(AppState.get().finalText);
    });

    $('#btn-download-txt').addEventListener('click', () => {
      downloadFile(AppState.get().finalText, 'article.txt', 'text/plain;charset=utf-8');
    });

    $('#btn-download-html').addEventListener('click', () => {
      const html = AppState.get().articleHtml || AppState.get().finalText;
      downloadFile(html, 'article.html', 'text/html;charset=utf-8');
    });

    $('#btn-export-gdocs').addEventListener('click', () => {
      exportToGoogleDocs(AppState.get().finalText);
    });

    $('#btn-start-over').addEventListener('click', () => {
      AppState.reset();
      goToStep(1);

      // Reset all form fields
      $('#source-type').value = '';
      $('#youtube-url').value = '';
      hide('#source-youtube');
      hide('#source-text');
      hide('#youtube-result');
      disableBtn('#btn-step1-submit');
      hide('#btn-step1-submit');

      // Reset Step 2
      hide('#substep-research');
      hide('#substep-style');
      hide('#substep-article');
      hide('#substep-html');
      enableBtn('#btn-plan-approve');
      enableBtn('#btn-plan-edit');
      enableBtn('#btn-research-approve');
      enableBtn('#btn-research-edit');

      toast('Начинаем заново', 'info');
    });
  }

  async function runHumanize() {
    showLoading('humanize-loading');
    hide('#humanize-result');

    try {
      const result = await ApiService.humanize(AppState.get().article);
      AppState.setFinalText(result.text);
      setResultText('humanize-text', result.text);
      show('#humanize-result');
      toast('Хуманизация завершена! Текст готов.', 'success');
    } catch (err) {
      toast('Ошибка хуманизации: ' + err.message, 'error');
    } finally {
      hideLoading('humanize-loading');
    }
  }

})();
