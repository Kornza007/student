/**
 * lang/i18n.js - Multi-Language Controller & Engine
 * Supports:
 * - Automatic device/client language detection (Thai -> 'th', Others -> 'en')
 * - User preference persistence in localStorage ('app_lang')
 * - Single switch toggle button (TH | EN) & direct selection
 * - Template variable interpolation: t('text_trained_days', '', { trained: 10, total: 90, percent: 11 })
 * - Event broadcasting on language change ('languageChanged')
 */

(function () {
    const STORAGE_KEY = 'app_lang';
    const SUPPORTED_LANGS = ['th', 'en'];
    const DEFAULT_LANG = 'th';

    /**
     * Detect initial language based on device/browser settings
     */
    function detectInitialLanguage() {
        const savedLang = localStorage.getItem(STORAGE_KEY);
        if (savedLang && SUPPORTED_LANGS.includes(savedLang)) {
            return savedLang;
        }

        // Check browser / system language
        const browserLang = (
            (navigator.languages && navigator.languages[0]) ||
            navigator.language ||
            navigator.userLanguage ||
            ''
        ).toLowerCase();

        // If Thai -> TH, otherwise any other language -> EN
        if (browserLang.startsWith('th')) {
            return 'th';
        }
        return 'en';
    }

    let currentLang = detectInitialLanguage();

    /**
     * Get dictionary object for current language
     */
    function getDictionary(lang) {
        if (lang === 'en' && typeof LANG_EN !== 'undefined') {
            return LANG_EN;
        }
        if (typeof LANG_TH !== 'undefined') {
            return LANG_TH;
        }
        return {};
    }

    /**
     * Translate key with optional fallback and variable replacements
     * @param {string} key - Dictionary translation key
     * @param {string} fallback - Default string if key is not found
     * @param {object} params - Variable interpolation object e.g. { trained: 5, total: 90 }
     */
    function t(key, fallback = '', params = {}) {
        const dict = getDictionary(currentLang);
        let text = (dict && dict[key] !== undefined) ? dict[key] : (fallback || key);

        if (params && typeof params === 'object') {
            Object.keys(params).forEach(pKey => {
                const regex = new RegExp(`\\{${pKey}\\}`, 'g');
                text = text.replace(regex, params[pKey]);
            });
        }
        return text;
    }

    /**
     * Toggle between TH and EN
     */
    function toggleLanguage() {
        const nextLang = (currentLang === 'th') ? 'en' : 'th';
        setLanguage(nextLang);
    }

    /**
     * Set active language and update the page
     * @param {string} lang - 'th' or 'en'
     */
    function setLanguage(lang) {
        if (!SUPPORTED_LANGS.includes(lang)) return;
        currentLang = lang;
        try {
            localStorage.setItem(STORAGE_KEY, lang);
        } catch (e) {
            console.warn('Could not save language to localStorage', e);
        }

        // Update document lang attribute
        document.documentElement.lang = lang;

        // Apply translations to DOM
        applyTranslations();

        // Update active class on Language Switcher buttons/toggles
        syncSwitcherButtons();

        // Dispatch custom event for dynamic components (Charts, Tables, etc.)
        window.dispatchEvent(new CustomEvent('languageChanged', {
            detail: { language: lang, t: t }
        }));
    }

    /**
     * Apply translations to all DOM elements marked with data-i18n attributes
     */
    function applyTranslations() {
        // Page Title
        const pageTitleEl = document.querySelector('[data-i18n-page-title]');
        if (pageTitleEl) {
            const titleKey = pageTitleEl.getAttribute('data-i18n-page-title');
            if (titleKey) {
                document.title = t(titleKey, document.title);
            }
        }

        // Inner Text
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (key) {
                el.innerText = t(key, el.innerText);
            }
        });

        // HTML Content
        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            const key = el.getAttribute('data-i18n-html');
            if (key) {
                el.innerHTML = t(key, el.innerHTML);
            }
        });

        // Placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (key) {
                el.setAttribute('placeholder', t(key, el.getAttribute('placeholder')));
            }
        });

        // Titles / Tooltips
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (key) {
                el.setAttribute('title', t(key, el.getAttribute('title')));
            }
        });
    }

    /**
     * Sync highlight on all Language Switcher controls on the page
     */
    function syncSwitcherButtons() {
        // Multi-button group
        document.querySelectorAll('.lang-switch-btn').forEach(btn => {
            const btnLang = btn.getAttribute('data-lang');
            if (btnLang === currentLang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Single Toggle Widget
        document.querySelectorAll('.lang-switch-toggle').forEach(widget => {
            widget.setAttribute('data-current-lang', currentLang);
            const optTh = widget.querySelector('.th-opt');
            const optEn = widget.querySelector('.en-opt');
            if (optTh && optEn) {
                if (currentLang === 'th') {
                    optTh.classList.add('active');
                    optEn.classList.remove('active');
                } else {
                    optEn.classList.add('active');
                    optTh.classList.remove('active');
                }
            }
        });
    }

    /**
     * Initialize on DOM Content Loaded
     */
    function init() {
        document.documentElement.lang = currentLang;
        applyTranslations();
        syncSwitcherButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose global functions to window
    window.i18n = {
        t: t,
        setLanguage: setLanguage,
        toggleLanguage: toggleLanguage,
        getCurrentLanguage: () => currentLang,
        applyTranslations: applyTranslations
    };
    window.t = t;
    window.setLanguage = setLanguage;
    window.toggleLanguage = toggleLanguage;
})();
