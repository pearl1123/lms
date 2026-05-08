/**
 * Normalizes window.APP_CONTEXT to four namespaces (catalog, dashboard, module, assessments).
 * Pages merge patches via kaApplyAppContext — shallow merge per namespace only.
 */
(function(global) {
  'use strict';

  var NS = ['catalog', 'dashboard', 'module', 'assessments'];

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
    return base;
  }

  /**
   * Deep-merge namespace objects onto APP_CONTEXT (preserves sibling namespaces).
   * @param {Object=} patch Partial { catalog?, dashboard?, module?, assessments? }
   */
  global.kaApplyAppContext = function(patch) {
    global.APP_CONTEXT = mergeNs(global.APP_CONTEXT || skeleton(), patch || {});
  };

  if (!global.APP_CONTEXT) {
    global.APP_CONTEXT = skeleton();
  }
})(window);
