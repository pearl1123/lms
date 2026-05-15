(function() {
  'use strict';

  function byId(id) { return document.getElementById(id); }

  var btn = byId('kaNotifyBtn');
  var badge = byId('kaNotifyBadge');
  var list = byId('kaNotifyList');
  if (!btn || !badge || !list) return;

  // Bootstrap dropdown instance (for ESC close).
  function dropdownHide() {
    try {
      if (window.bootstrap && bootstrap.Dropdown) {
        var inst = bootstrap.Dropdown.getOrCreateInstance(btn);
        inst.hide();
      }
    } catch (e) {}
  }

  var appCtx = (window.APP_CONTEXT && window.APP_CONTEXT.notifications) || {};
  var TYPE_MAP = {
    certificate: { icon: 'fa-award', className: 'certificate' },
    approval:    { icon: 'fa-check-circle', className: 'approval' },
    rejection:   { icon: 'fa-times-circle', className: 'rejection' },
    request:     { icon: 'fa-clock', className: 'request' },
    system:      { icon: 'fa-bell', className: 'system' }
  };
  var ctx = {
    unreadUrl: appCtx.unreadUrl || btn.getAttribute('data-unread-url') || '',
    latestUrl: appCtx.latestUrl || btn.getAttribute('data-latest-url') || '',
    markReadBaseUrl: appCtx.markReadBaseUrl || btn.getAttribute('data-mark-read-base-url') || '',
    allUrl: appCtx.allUrl || btn.getAttribute('data-all-url') || '',
    csrfName: appCtx.csrfName || btn.getAttribute('data-csrf-name') || '',
    csrfHash: appCtx.csrfHash || btn.getAttribute('data-csrf-hash') || ''
  };

  function parseJsonResponse(r) {
    return r.text().then(function(text) {
      try {
        return JSON.parse(text);
      } catch (e) {
        return {};
      }
    });
  }

  btn.setAttribute('aria-label', 'Notifications');
  btn.setAttribute('aria-haspopup', 'menu');

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatRelativeTime(dateString) {
    if (!dateString) return '';
    var ts = Date.parse(String(dateString).replace(' ', 'T'));
    if (!isFinite(ts)) return '';
    var diff = Date.now() - ts;
    if (diff < 0) diff = 0;

    var sec = Math.floor(diff / 1000);
    if (sec < 15) return 'Just now';
    if (sec < 60) return sec + ' secs ago';

    var min = Math.floor(sec / 60);
    if (min === 1) return '1 min ago';
    if (min < 60) return min + ' mins ago';

    var hr = Math.floor(min / 60);
    if (hr === 1) return '1 hour ago';
    if (hr < 24) return hr + ' hours ago';

    var day = Math.floor(hr / 24);
    if (day === 1) return 'Yesterday';
    return day + ' days ago';
  }

  var unreadCount = 0;
  function updateBadge(count) {
    var n = parseInt(count, 10) || 0;
    unreadCount = n;
    if (n > 0) {
      badge.textContent = n > 9 ? '9+' : String(n);
      badge.classList.remove('is-hidden');
      badge.style.display = '';
    } else {
      badge.classList.add('is-hidden');
      badge.style.display = 'none';
    }
  }

  function loadUnreadCount() {
    if (!ctx.unreadUrl) return Promise.resolve();
    return fetch(ctx.unreadUrl, { credentials: 'same-origin' })
      .then(parseJsonResponse)
      .then(function(res) { updateBadge(res && res.count); })
      .catch(function() {});
  }

  function markAsRead(id) {
    if (!id || !ctx.markReadBaseUrl) return Promise.resolve();
    var fd = new FormData();
    if (ctx.csrfName && ctx.csrfHash) fd.append(ctx.csrfName, ctx.csrfHash);
    return fetch(ctx.markReadBaseUrl + encodeURIComponent(String(id)), {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(parseJsonResponse)
      .then(function(res) {
        if (res && typeof res.count !== 'undefined') {
          updateBadge(res.count);
        } else {
          return loadUnreadCount();
        }
      })
      .catch(function() { throw new Error('mark_read_failed'); });
  }

  function typeMeta(type) {
    var key = (type && TYPE_MAP[type]) ? type : 'system';
    return {
      icon: TYPE_MAP[key].icon,
      klass: TYPE_MAP[key].className
    };
  }

  function renderNotifications(items) {
    if (!Array.isArray(items) || items.length === 0) {
      list.innerHTML = ''
        + '<div class="ka-notify-empty" role="status" aria-live="polite">'
        + '  <i class="fa fa-bell-slash" aria-hidden="true"></i>'
        + '  <p>No notifications yet</p>'
        + '</div>';
      return;
    }

    list.innerHTML = items.map(function(it) {
      var title = escapeHtml(it.title || '(No title)');
      var msg = escapeHtml((it.message || '').replace(/\s+/g, ' ').trim());
      var time = escapeHtml(formatRelativeTime(it.created_at));
      var url = escapeHtml(it.url || ctx.allUrl || '#');
      var unread = !it.read;
      var unreadClass = unread ? ' is-unread' : '';
      var dot = unread ? '<div class="ka-notify-dot" aria-hidden="true"></div>' : '';
      var type = (it && it.type_key) ? String(it.type_key) : 'system';
      var meta = typeMeta(type);
      return ''
        + '<a href="' + url + '"'
        + ' class="ka-notif-item ka-notify-item' + unreadClass + '"'
        + ' role="menuitem" tabindex="0"'
        + ' data-id="' + (it.id || 0) + '"'
        + ' data-read="' + (it.read ? '1' : '0') + '">'
        + '  <div class="ka-notify-type ' + meta.klass + '" aria-hidden="true"><i class="fa ' + meta.icon + '"></i></div>'
        + '  <div class="ka-notif-body">'
        + '    <p class="ka-notif-text"><strong>' + title + '</strong>' + (msg ? ' — ' + msg : '') + '</p>'
        + (time ? '    <div class="ka-notif-time">' + time + '</div>' : '')
        + '  </div>'
        + dot
        + '</a>';
    }).join('');
  }

  function loadNotifications() {
    if (!ctx.latestUrl) return Promise.resolve();
    list.innerHTML = ''
      + '<div class="ka-notify-loading" role="status" aria-live="polite">'
      + '  <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>'
      + '  <p>Loading notifications...</p>'
      + '</div>';
    return fetch(ctx.latestUrl, { credentials: 'same-origin' })
      .then(parseJsonResponse)
      .then(function(res) {
        renderNotifications((res && res.items) || []);
      })
      .catch(function() {});
  }

  function optimisticUnreadDelta(delta) {
    updateBadge(Math.max(0, unreadCount + delta));
  }

  function activateRow(row) {
    if (!row) return;
    var id = parseInt(row.getAttribute('data-id'), 10) || 0;
    var url = row.getAttribute('href') || ctx.allUrl || '#';
    var wasUnread = row.classList.contains('is-unread');

    // Optimistic UI: mark read immediately.
    if (wasUnread) {
      row.classList.remove('is-unread');
      row.setAttribute('data-read', '1');
      var dot = row.querySelector('.ka-notify-dot');
      if (dot) dot.remove();
      optimisticUnreadDelta(-1);
    }

    var redirected = false;
    function go() {
      if (redirected) return;
      redirected = true;
      window.location.href = url;
    }

    // Deep link: try mark-read, but always redirect quickly.
    var timer = setTimeout(go, 650);
    markAsRead(id)
      .then(function() {
        clearTimeout(timer);
        go();
      })
      .catch(function() {
        clearTimeout(timer);
        // Rollback optimistic UI on failure.
        if (wasUnread) {
          row.classList.add('is-unread');
          row.setAttribute('data-read', '0');
          if (!row.querySelector('.ka-notify-dot')) {
            row.insertAdjacentHTML('beforeend', '<div class="ka-notify-dot" aria-hidden="true"></div>');
          }
          optimisticUnreadDelta(1);
        }
        go();
      });
  }

  list.addEventListener('click', function(ev) {
    var row = ev.target.closest('.ka-notify-item');
    if (!row) return;
    ev.preventDefault();
    activateRow(row);
  });

  list.addEventListener('keydown', function(ev) {
    var row = ev.target.closest('.ka-notify-item');
    if (!row) return;
    if (ev.key === 'Enter' || ev.key === ' ') {
      ev.preventDefault();
      activateRow(row);
    }
  });

  btn.addEventListener('click', function() {
    loadNotifications();
  });

  document.addEventListener('keydown', function(ev) {
    if (ev.key === 'Escape') {
      dropdownHide();
    }
  });

  loadUnreadCount();
  setInterval(loadUnreadCount, 30000);
})();

