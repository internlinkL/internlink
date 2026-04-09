// ─────────────────────────────────────────────
//  notifications.js — internLink
//  Shared notification system for all student pages.
//  Include this script in every student HTML page.
//  Requires: a <div id="notifDropdown"> and bell button in the navbar.
// ─────────────────────────────────────────────

(function () {
  // ── Session-based seen tracking ───────────────────────────────────────────
  // Store seen notification IDs in sessionStorage (cleared on browser close)
  function getSeenIds() {
    try { return new Set(JSON.parse(sessionStorage.getItem('notif_seen') || '[]')); }
    catch { return new Set(); }
  }
  function markAllSeen(ids) {
    sessionStorage.setItem('notif_seen', JSON.stringify([...ids]));
  }

  // ── Fetch notifications from server ──────────────────────────────────────
  let allNotifications = [];

  function fetchNotifications() {
    fetch('../php/get_notifications.php', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        allNotifications = data.notifications || [];
        const seen      = getSeenIds();
        const unread    = allNotifications.filter(n => !seen.has(n.id));
        updateBadge(unread.length);
        renderDropdown(allNotifications, seen);
      })
      .catch(() => {}); // fail silently — notifications are non-critical
  }

  // ── Badge ─────────────────────────────────────────────────────────────────
  function updateBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;
    badge.textContent = count > 9 ? '9+' : count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }

  // ── Dropdown render ───────────────────────────────────────────────────────
  function renderDropdown(notifications, seen) {
    const list = document.getElementById('notifList');
    if (!list) return;

    if (!notifications.length) {
      list.innerHTML = `
        <div style="text-align:center;padding:32px 20px;color:var(--muted)">
          <div style="font-size:2rem;margin-bottom:8px">🔔</div>
          <div style="font-size:.875rem">No notifications yet</div>
        </div>`;
      return;
    }

    list.innerHTML = notifications.map(n => {
      const isUnread = !seen.has(n.id);
      const timeAgo  = formatTime(n.time);
      const colors   = {
        success: { bg: 'rgba(52,211,153,.1)',  border: 'rgba(52,211,153,.25)',  text: '#34d399' },
        danger:  { bg: 'rgba(248,113,113,.1)', border: 'rgba(248,113,113,.25)', text: '#f87171' },
        info:    { bg: 'rgba(79,142,247,.1)',  border: 'rgba(79,142,247,.25)',  text: '#4f8ef7' },
      };
      const c = colors[n.type] || colors.info;
      return `
        <div class="notif-item ${isUnread ? 'notif-unread' : ''}"
             style="padding:12px 16px;border-bottom:1px solid var(--border);cursor:default;
                    ${isUnread ? 'background:rgba(79,142,247,.04)' : ''}">
          <div style="display:flex;align-items:flex-start;gap:10px">
            <div style="width:34px;height:34px;border-radius:8px;flex-shrink:0;
                        background:${c.bg};border:1px solid ${c.border};
                        display:flex;align-items:center;justify-content:center;font-size:1rem">
              ${n.icon}
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.82rem;font-weight:700;color:${c.text};margin-bottom:2px">
                ${esc(n.title)}
                ${isUnread ? '<span style="display:inline-block;width:6px;height:6px;background:var(--accent);border-radius:50%;margin-left:5px;vertical-align:middle"></span>' : ''}
              </div>
              <div style="font-size:.78rem;color:var(--muted);line-height:1.4">${esc(n.message)}</div>
              <div style="font-size:.72rem;color:var(--muted);margin-top:4px;opacity:.7">${timeAgo}</div>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  // ── Toggle dropdown ───────────────────────────────────────────────────────
  let dropdownOpen = false;

  function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    if (!dropdown) return;

    dropdownOpen = !dropdownOpen;
    dropdown.style.display = dropdownOpen ? 'block' : 'none';

    if (dropdownOpen) {
      // Mark all as seen when dropdown is opened
      const ids = new Set(allNotifications.map(n => n.id));
      markAllSeen(ids);
      updateBadge(0);
      renderDropdown(allNotifications, ids);
    }
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function (e) {
    if (!dropdownOpen) return;
    const btn      = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (btn && btn.contains(e.target)) return;
    if (dropdown && dropdown.contains(e.target)) return;
    dropdownOpen = false;
    if (dropdown) dropdown.style.display = 'none';
  });

  // ── Helpers ───────────────────────────────────────────────────────────────
  function esc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function formatTime(timeStr) {
    if (!timeStr) return '';
    const diff = Math.floor((Date.now() - new Date(timeStr).getTime()) / 1000);
    if (diff < 60)   return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  // Expose toggle function globally so onclick in HTML can call it
  window.toggleNotifDropdown = toggleNotifDropdown;

  // Fetch on page load, then every 60 seconds
  document.addEventListener('DOMContentLoaded', function () {
    fetchNotifications();
    setInterval(fetchNotifications, 60000);
  });
})();
