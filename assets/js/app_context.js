/**
 * Normalizes window.APP_CONTEXT to shared page namespaces.
 * Pages merge patches via kaApplyAppContext — shallow merge per namespace;
 * nested `assessments.edit` is deep-merged so URLs/CSRF are not dropped by partial patches.
 */
(function(global) {
  'use strict';

  var NS = ['catalog', 'dashboard', 'module', 'assessments', 'notifications'];

  function skeleton() {
    var o = {};
    NS.forEach(function(k) {
      o[k] = {};
    });
    return o;
  }

  function isPlainObject(x) {
    return Boolean(x) && typeof x === 'object' && !Array.isArray(x);
  }

  function mergeNs(prev, patch) {
    var base = skeleton();
    NS.forEach(function(k) {
      if (isPlainObject(prev) && isPlainObject(prev[k])) {
        Object.assign(base[k], prev[k]);
      }
    });
    NS.forEach(function(k) {
      if (isPlainObject(patch) && isPlainObject(patch[k])) {
        Object.assign(base[k], patch[k]);
      }
    });
    // Deep-merge assessments.edit so a partial patch cannot wipe saveQuestionUrl / CSRF (shallow assign replaces the whole edit object).
    if (isPlainObject(base.assessments)) {
      var prevEdit = isPlainObject(prev) && prev.assessments && isPlainObject(prev.assessments.edit)
        ? prev.assessments.edit : null;
      var patchEdit = isPlainObject(patch) && patch.assessments && isPlainObject(patch.assessments.edit)
        ? patch.assessments.edit : null;
      if (prevEdit || patchEdit) {
        base.assessments.edit = Object.assign({}, prevEdit || {}, patchEdit || {});
      }
    }
    return base;
  }

  /**
   * Deep-merge namespace objects onto APP_CONTEXT (preserves sibling namespaces).
   * @param {Object=} patch Partial page context namespaces.
   */
  global.kaApplyAppContext = function(patch) {
    global.APP_CONTEXT = mergeNs(global.APP_CONTEXT || skeleton(), patch || {});
  };

  if (!global.APP_CONTEXT) {
    global.APP_CONTEXT = skeleton();
  }
})(window);
