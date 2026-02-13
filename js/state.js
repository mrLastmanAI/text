/**
 * Application State Management
 *
 * Centralized state for the article generation wizard.
 * All step data, current progress, and intermediate results are stored here.
 */

const AppState = (() => {
  // ── State Object ───────────────────────────────────────────────
  const state = {
    // Current wizard step (1-4)
    currentStep: 1,

    // Step 1: Input data
    sourceType: null,            // 'youtube' | 'text'
    youtubeUrl: '',
    transcript: '',              // YouTube transcription result
    articleParams: {
      topic: '',
      audience: '',
      goal: '',
      goalCustom: '',
      type: '',
      typeCustom: '',
      platform: '',
      platformCustom: '',
      volume: '',
      needsResearch: false,
    },

    // Step 2: Plan & Article generation
    plan: '',                    // Current plan text
    planApproved: false,
    research: '',                // Collected research text
    researchApproved: false,
    writingStyle: '',            // 'egorka' | 'not-egorka'
    article: '',                 // Generated article text
    articleHtml: '',             // HTML version of article

    // Step 3: Fact-check
    factCheckPassed: false,
    factCheckErrors: [],
    factCheckSummary: '',

    // Step 4: Humanization
    finalText: '',               // Humanized final text
  };

  // ── Listeners ──────────────────────────────────────────────────
  const listeners = [];

  function subscribe(fn) {
    listeners.push(fn);
    return () => {
      const idx = listeners.indexOf(fn);
      if (idx > -1) listeners.splice(idx, 1);
    };
  }

  function notify() {
    listeners.forEach(fn => fn(state));
  }

  // ── Getters ────────────────────────────────────────────────────
  function get() {
    return state;
  }

  function getCurrentStep() {
    return state.currentStep;
  }

  // ── Setters ────────────────────────────────────────────────────

  function setCurrentStep(step) {
    state.currentStep = step;
    notify();
  }

  function setSourceType(type) {
    state.sourceType = type;
    notify();
  }

  function setYouTubeUrl(url) {
    state.youtubeUrl = url;
  }

  function setTranscript(text) {
    state.transcript = text;
    notify();
  }

  function setArticleParam(key, value) {
    state.articleParams[key] = value;
  }

  function setArticleParams(params) {
    Object.assign(state.articleParams, params);
  }

  function setPlan(plan) {
    state.plan = plan;
    notify();
  }

  function setPlanApproved(approved) {
    state.planApproved = approved;
    notify();
  }

  function setResearch(research) {
    state.research = research;
    notify();
  }

  function setResearchApproved(approved) {
    state.researchApproved = approved;
    notify();
  }

  function setWritingStyle(style) {
    state.writingStyle = style;
    notify();
  }

  function setArticle(article) {
    state.article = article;
    notify();
  }

  function setArticleHtml(html) {
    state.articleHtml = html;
    notify();
  }

  function setFactCheckResult(isValid, errors, summary) {
    state.factCheckPassed = isValid;
    state.factCheckErrors = errors || [];
    state.factCheckSummary = summary || '';
    notify();
  }

  function setFinalText(text) {
    state.finalText = text;
    notify();
  }

  /** Reset everything to initial state */
  function reset() {
    state.currentStep = 1;
    state.sourceType = null;
    state.youtubeUrl = '';
    state.transcript = '';
    state.articleParams = {
      topic: '',
      audience: '',
      goal: '',
      goalCustom: '',
      type: '',
      typeCustom: '',
      platform: '',
      platformCustom: '',
      volume: '',
      needsResearch: false,
    };
    state.plan = '';
    state.planApproved = false;
    state.research = '';
    state.researchApproved = false;
    state.writingStyle = '';
    state.article = '';
    state.articleHtml = '';
    state.factCheckPassed = false;
    state.factCheckErrors = [];
    state.factCheckSummary = '';
    state.finalText = '';
    notify();
  }

  /**
   * Get the effective article parameters for API calls.
   * Resolves "other" values to custom inputs.
   */
  function getEffectiveParams() {
    const p = state.articleParams;
    return {
      topic: p.topic,
      audience: p.audience,
      goal: p.goal === 'other' ? p.goalCustom : p.goal,
      type: p.type === 'other' ? p.typeCustom : p.type,
      platform: p.platform === 'other' ? p.platformCustom : p.platform,
      volume: p.volume,
      needsResearch: p.needsResearch,
      sourceText: state.transcript || null,
    };
  }

  // ── Public API ─────────────────────────────────────────────────
  return {
    get,
    getCurrentStep,
    subscribe,
    setCurrentStep,
    setSourceType,
    setYouTubeUrl,
    setTranscript,
    setArticleParam,
    setArticleParams,
    setPlan,
    setPlanApproved,
    setResearch,
    setResearchApproved,
    setWritingStyle,
    setArticle,
    setArticleHtml,
    setFactCheckResult,
    setFinalText,
    getEffectiveParams,
    reset,
  };
})();
