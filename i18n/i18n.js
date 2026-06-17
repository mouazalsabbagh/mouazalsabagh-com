/*!
 * i18n.js — lightweight EN/AR localization for mouazalsabbagh.com
 * Usage:
 *   <script src="i18n/i18n.js" data-i18n-src="i18n/strings.json"></script>
 *   Tag elements:  <span data-i18n="nav.home">Home</span>
 *                  <input data-i18n-attr="placeholder:form.email">
 *   Toggle:        I18N.setLang('ar')   |   I18N.toggle()
 * Language resolves from ?lang= , then localStorage, then <html lang>, then 'en'.
 */
(function () {
  var SCRIPT = document.currentScript;
  var SRC = (SCRIPT && SCRIPT.getAttribute('data-i18n-src')) || 'i18n/strings.json';
  var STORE_KEY = 'site_lang';
  var state = { lang: 'en', rtl: ['ar'], strings: {}, ready: false };

  function resolveLang(meta) {
    var langs = (meta && meta.languages) || ['en', 'ar'];
    var q = new URLSearchParams(location.search).get('lang');
    var saved = null;
    try { saved = localStorage.getItem(STORE_KEY); } catch (e) {}
    var htmlLang = document.documentElement.getAttribute('lang');
    var pick = [q, saved, htmlLang, (meta && meta.default) || 'en'].find(function (l) {
      return l && langs.indexOf(l) !== -1;
    });
    return pick || 'en';
  }

  function t(key) {
    var entry = state.strings[key];
    return entry && (entry[state.lang] != null ? entry[state.lang] : entry.en);
  }

  function apply() {
    // text content
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      var v = t(el.getAttribute('data-i18n'));
      if (v != null) el.textContent = v;
    });
    // attributes: data-i18n-attr="placeholder:form.email; title:nav.home"
    document.querySelectorAll('[data-i18n-attr]').forEach(function (el) {
      el.getAttribute('data-i18n-attr').split(';').forEach(function (pair) {
        var p = pair.split(':');
        if (p.length === 2) {
          var v = t(p[1].trim());
          if (v != null) el.setAttribute(p[0].trim(), v);
        }
      });
    });
    // direction + lang on <html>
    var isRtl = state.rtl.indexOf(state.lang) !== -1;
    var html = document.documentElement;
    html.setAttribute('lang', state.lang);
    html.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
    html.classList.toggle('rtl', isRtl);
    // reflect on any toggle buttons
    document.querySelectorAll('[data-i18n-toggle]').forEach(function (b) {
      b.textContent = state.lang === 'ar' ? 'English' : 'العربية';
    });
    document.dispatchEvent(new CustomEvent('i18n:applied', { detail: { lang: state.lang } }));
  }

  function setLang(lang) {
    if (!state.strings || Object.keys(state.strings).length === 0) return;
    state.lang = lang;
    try { localStorage.setItem(STORE_KEY, lang); } catch (e) {}
    apply();
  }

  function toggle() { setLang(state.lang === 'ar' ? 'en' : 'ar'); }

  function init(data) {
    state.strings = data.strings || {};
    state.rtl = (data._meta && data._meta.rtl) || ['ar'];
    state.lang = resolveLang(data._meta);
    state.ready = true;
    apply();
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-i18n-toggle]');
      if (b) { e.preventDefault(); toggle(); }
    });
  }

  window.I18N = { setLang: setLang, toggle: toggle, t: t, get lang() { return state.lang; }, get ready() { return state.ready; } };

  fetch(SRC, { cache: 'no-cache' })
    .then(function (r) { if (!r.ok) throw new Error('i18n load ' + r.status); return r.json(); })
    .then(init)
    .catch(function (err) { console.error('[i18n]', err); });
})();
