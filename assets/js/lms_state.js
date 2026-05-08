(function(global) {
  'use strict';

  let scheduled = false;

  function emitLmsStateUpdatedOnce() {
    if (scheduled) return;

    scheduled = true;

    requestAnimationFrame(() => {
      document.dispatchEvent(new CustomEvent('lms_state_updated'));
      scheduled = false;
    });
  }

  function syncModuleState() {
    // MUST remain on document
    document.dispatchEvent(new CustomEvent('module_flow_updated'));

    // Coalesced event
    emitLmsStateUpdatedOnce();
  }

  function syncCourseProgress(courseId) {
    const detail = (typeof courseId === 'number' && courseId > 0)
      ? { courseId }
      : undefined;

    // MUST remain on window (catalog.js depends on this)
    window.dispatchEvent(new CustomEvent('course_progress_updated', { detail }));

    // Coalesced event
    emitLmsStateUpdatedOnce();
  }

  global.LMS_STATE = {
    syncModuleState,
    syncCourseProgress,
  };
})(window);
