/**
 * UI Helper Module
 *
 * Handles DOM manipulation, step transitions, toast notifications,
 * and common UI patterns (show/hide, loading states, etc.)
 */

const UI = (() => {
  // ── DOM References (cached) ────────────────────────────────────
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);

  // ── Step Navigation ────────────────────────────────────────────

  /**
   * Show a specific wizard step, hide others.
   * Updates the stepper progress bar.
   */
  function goToStep(stepNumber) {
    // Hide all steps
    $$('.step').forEach(el => {
      el.style.display = 'none';
      el.classList.remove('step--active');
    });

    // Show target step
    const target = $(`#step-${stepNumber}`);
    if (target) {
      target.style.display = 'block';
      target.classList.add('step--active');
    }

    // Update stepper
    updateStepper(stepNumber);

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /**
   * Update stepper progress indicator.
   */
  function updateStepper(activeStep) {
    const items = $$('.stepper__item');
    const lines = $$('.stepper__line');

    items.forEach((item, idx) => {
      const step = idx + 1;
      item.classList.remove('stepper__item--active', 'stepper__item--completed');

      if (step === activeStep) {
        item.classList.add('stepper__item--active');
      } else if (step < activeStep) {
        item.classList.add('stepper__item--completed');
      }
    });

    lines.forEach((line, idx) => {
      line.classList.remove('stepper__line--completed');
      if (idx + 1 < activeStep) {
        line.classList.add('stepper__line--completed');
      }
    });
  }

  // ── Show / Hide Helpers ────────────────────────────────────────

  function show(selector) {
    const el = typeof selector === 'string' ? $(selector) : selector;
    if (el) el.style.display = 'block';
  }

  function hide(selector) {
    const el = typeof selector === 'string' ? $(selector) : selector;
    if (el) el.style.display = 'none';
  }

  function toggle(selector, visible) {
    if (visible) show(selector);
    else hide(selector);
  }

  // ── Loading States ─────────────────────────────────────────────

  function showLoading(id) {
    show(`#${id}`);
  }

  function hideLoading(id) {
    hide(`#${id}`);
  }

  // ── Content Rendering ──────────────────────────────────────────

  /**
   * Set text content for a result block, preserving whitespace.
   */
  function setResultText(elementId, text) {
    const el = $(`#${elementId}`);
    if (el) {
      el.textContent = text;
    }
  }

  /**
   * Set innerHTML for a result block (for HTML preview).
   */
  function setResultHtml(elementId, html) {
    const el = $(`#${elementId}`);
    if (el) {
      el.innerHTML = html;
    }
  }

  // ── Button State ───────────────────────────────────────────────

  function enableBtn(selector) {
    const el = typeof selector === 'string' ? $(selector) : selector;
    if (el) el.disabled = false;
  }

  function disableBtn(selector) {
    const el = typeof selector === 'string' ? $(selector) : selector;
    if (el) el.disabled = true;
  }

  function setBtnLoading(selector, isLoading, originalText) {
    const el = typeof selector === 'string' ? $(selector) : selector;
    if (!el) return;
    if (isLoading) {
      el.dataset.originalText = el.textContent;
      el.textContent = 'Загрузка...';
      el.disabled = true;
    } else {
      el.textContent = originalText || el.dataset.originalText || 'Отправить';
      el.disabled = false;
    }
  }

  // ── Toast Notifications ────────────────────────────────────────

  /**
   * Show a toast notification.
   * @param {string} message
   * @param {'info'|'success'|'error'} type
   * @param {number} duration - ms before auto-dismiss
   */
  function toast(message, type = 'info', duration = 3000) {
    const container = $('#toast-container');
    const el = document.createElement('div');
    el.className = `toast toast--${type}`;
    el.textContent = message;
    container.appendChild(el);

    setTimeout(() => {
      el.style.animation = 'toastOut 0.3s ease forwards';
      setTimeout(() => el.remove(), 300);
    }, duration);
  }

  // ── Tabs ───────────────────────────────────────────────────────

  function initTabs() {
    $$('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const tabGroup = tab.closest('.substep') || tab.closest('.step');
        tabGroup.querySelectorAll('.tab').forEach(t => t.classList.remove('tab--active'));
        tabGroup.querySelectorAll('.tab-content').forEach(c => c.classList.remove('tab-content--active'));

        tab.classList.add('tab--active');
        const targetId = tab.dataset.tab;
        const content = tabGroup.querySelector(`#html-${targetId}`);
        if (content) content.classList.add('tab-content--active');
      });
    });
  }

  // ── "Other" Select Handling ────────────────────────────────────

  /**
   * When a <select> with "other" option is selected,
   * show/hide the custom input field.
   */
  function setupOtherSelects() {
    const pairs = [
      { select: '#article-goal', input: '#article-goal-custom' },
      { select: '#article-type', input: '#article-type-custom' },
      { select: '#platform', input: '#platform-custom' },
    ];

    pairs.forEach(({ select: selSel, input: inpSel }) => {
      const sel = $(selSel);
      const inp = $(inpSel);
      if (!sel || !inp) return;

      sel.addEventListener('change', () => {
        toggle(inpSel, sel.value === 'other');
      });
    });
  }

  // ── Copy to Clipboard ──────────────────────────────────────────

  async function copyToClipboard(text) {
    try {
      await navigator.clipboard.writeText(text);
      toast('Скопировано в буфер обмена', 'success');
    } catch (err) {
      // Fallback
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      toast('Скопировано в буфер обмена', 'success');
    }
  }

  // ── File Download ──────────────────────────────────────────────

  function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toast(`Файл "${filename}" скачан`, 'success');
  }

  // ── Google Docs Export ─────────────────────────────────────────

  function exportToGoogleDocs(text) {
    // Open Google Docs with pre-filled content via URL
    // This creates a new document with the content
    const encodedText = encodeURIComponent(text);
    const url = `https://docs.google.com/document/create?body=${encodedText}`;

    // Alternative: open blank doc (Google doesn't support body param reliably)
    // So we copy to clipboard first, then open a new doc
    copyToClipboard(text);
    window.open('https://docs.google.com/document/create', '_blank');
    toast('Текст скопирован. Вставьте его в открывшийся Google Doc (Ctrl+V)', 'info', 5000);
  }

  // ── Validate Step 1 ───────────────────────────────────────────

  /**
   * Check if Step 1 form is valid based on source type.
   * @returns {boolean}
   */
  function validateStep1() {
    const state = AppState.get();
    if (state.sourceType === 'youtube') {
      const url = $('#youtube-url')?.value?.trim();
      return url && (url.includes('youtube.com') || url.includes('youtu.be'));
    }
    if (state.sourceType === 'text') {
      const topic = $('#article-topic')?.value?.trim();
      const audience = $('#target-audience')?.value?.trim();
      const goal = $('#article-goal')?.value;
      const type = $('#article-type')?.value;
      const platform = $('#platform')?.value;
      const volume = $('#volume')?.value?.trim();

      if (goal === 'other' && !$('#article-goal-custom')?.value?.trim()) return false;
      if (type === 'other' && !$('#article-type-custom')?.value?.trim()) return false;
      if (platform === 'other' && !$('#platform-custom')?.value?.trim()) return false;

      return !!(topic && audience && goal && type && platform && volume);
    }
    return false;
  }

  // ── Public API ─────────────────────────────────────────────────
  return {
    $,
    $$,
    goToStep,
    updateStepper,
    show,
    hide,
    toggle,
    showLoading,
    hideLoading,
    setResultText,
    setResultHtml,
    enableBtn,
    disableBtn,
    setBtnLoading,
    toast,
    initTabs,
    setupOtherSelects,
    copyToClipboard,
    downloadFile,
    exportToGoogleDocs,
    validateStep1,
  };
})();
