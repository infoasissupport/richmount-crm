/* Settings page (settings.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon, esc = R.esc;

  R.page({
    id: 'settings',
    permission: 'settings.view',
    title: 'Settings',
    sub: 'Display preferences and demo controls. Preferences are stored in this browser.',
    mount() {
      const app = $('#app');
      $('#prefs').innerHTML = `<header class="card-head"><h2><span class="head-ic">${icon('settings')}</span>Preferences</h2></header>
        <div class="toggle-row"><div><strong>Collapse sidebar</strong><small>Show icons only on desktop</small></div><label class="switch"><input type="checkbox" data-pref="collapsed"${app.classList.contains('collapsed') ? ' checked' : ''} aria-label="Collapse sidebar"><i></i></label></div>
        <div class="toggle-row"><div><strong>Compact tables</strong><small>Tighter row spacing in all tables</small></div><label class="switch"><input type="checkbox" data-pref="compact"${document.body.classList.contains('compact') ? ' checked' : ''} aria-label="Compact tables"><i></i></label></div>`;
      $('#prefs').addEventListener('change', e => {
        const p = e.target.dataset.pref;
        if (p === 'collapsed') { app.classList.toggle('collapsed', e.target.checked); R.store.set('collapsed', e.target.checked); }
        if (p === 'compact') { document.body.classList.toggle('compact', e.target.checked); R.store.set('compact', e.target.checked); }
        R.toast('Preference saved');
      });

      $('#demo').innerHTML = `<header class="card-head"><h2><span class="head-ic">${icon('shield')}</span>Demo Controls</h2></header>
        <div class="toggle-row"><div><strong>Current role</strong><small>Switch to see each role's menu, KPIs and permissions</small></div>
          <span class="select-wrap" style="min-width:220px"><select id="roleSel">${Object.keys(R.ROLES).map(id => `<option value="${id}"${id === R.me.key ? ' selected' : ''}>${esc(R.ROLES[id].label)} — ${esc(R.ROLES[id].user)}</option>`).join('')}</select></span></div>
        <div class="toggle-row"><div><strong>Quotation approver</strong><small>Client document is ambiguous — configurable until confirmed${R.can('settings.edit') ? '' : ' (Super Admin only)'}</small></div>
          <span class="select-wrap" style="min-width:220px"><select id="approverSel"${R.can('settings.edit') ? '' : ' disabled'}><option value="estimation"${API.settings.quoteApprover === 'estimation' ? ' selected' : ''}>Estimation User (and Super Admin)</option><option value="admin"${API.settings.quoteApprover === 'admin' ? ' selected' : ''}>Super Admin only</option></select></span></div>
        <div class="toggle-row"><div><strong>Saved filter view</strong><small>${R.store.get('savedFilter', null) ? 'A default filter view is saved' : 'No saved view'}</small></div><button class="btn btn-ghost btn-sm" id="clearSaved">Clear</button></div>
        <div class="toggle-row"><div><strong>Reset demo data</strong><small>Discards every lead, quote, expense and approval created in this browser</small></div><button class="btn btn-danger btn-sm" id="resetDemo">Reset</button></div>`;
      $('#roleSel').addEventListener('change', e => R.switchRole(e.target.value));
      $('#approverSel').addEventListener('change', e => R.act(() => API.setSetting('quoteApprover', e.target.value), 'Quotation approver updated'));
      $('#clearSaved').addEventListener('click', () => { R.store.del('savedFilter'); R.toast('Saved view cleared', 'info'); });
      $('#resetDemo').addEventListener('click', () => R.confirmDialog({ title: 'Reset demo data?', text: 'All changes made in this browser will be discarded and the original sample data restored.', ok: 'Reset', danger: true })
        .then(ok => {
          if (!ok) return;
          API.resetDemo();
          ['filters', 'savedFilter', 'leadDraft', 'openQuotes'].forEach(k => R.store.del(k));
          R.flash('Demo data reset', 'info');
          location.reload();
        }));

      $('#about').innerHTML = `<header class="card-head"><h2><span class="head-ic">${icon('info')}</span>Company</h2></header>
        <dl class="kv"><dt>Company</dt><dd>Rich Mount Contracting LLC</dd><dt>Base currency</dt><dd>AED</dd><dt>VAT rate</dt><dd>5%</dd>
        <dt>Branches</dt><dd>${API.branches.length}</dd><dt>Accounting</dt><dd>Zoho Books (two-way sync)</dd><dt>Data</dt><dd>Clickable demo — sample data; your changes are kept in this browser (localStorage)</dd><dt>Saved actions</dt><dd>${(() => { try { return JSON.parse(localStorage.getItem('rm.journal.v2') || '[]').length; } catch (e) { return 0; } })()} recorded since the last reset</dd></dl>`;
    }
  });
})();
