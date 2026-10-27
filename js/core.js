/* =========================================================
   Rich Mount ERP — shared core for every page
   - Layout (sidebar, top bar, filter bar, login)
   - UI components (toast, menu, modal, drawer, data table)
   - Roles, permissions, filters, KPI definitions
   - Workflow forms and quick-view drawers
   Pages register themselves with RM.page({...}).
   ========================================================= */
(function () {
  'use strict';

  const API = window.RM_API;
  const TODAY = API.TODAY;

  /* =======================================================
     Helpers
     ======================================================= */
  const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const $ = (s, el) => (el || document).querySelector(s);
  const $$ = (s, el) => Array.from((el || document).querySelectorAll(s));
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  // full inline SVG (paths come from js/icons.js)
  const icon = (n, cls) => `<svg class="${cls || 'ic'}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">${(window.RMIcons || {})[n] || ''}</svg>`;
  const pad = n => String(n).padStart(2, '0');
  const toISO = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const parseISO = s => { const p = String(s).split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); };
  const startOfDay = d => { const x = new Date(d); x.setHours(0, 0, 0, 0); return x; };
  const endOfDay = d => { const x = new Date(d); x.setHours(23, 59, 59, 999); return x; };
  const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
  const addYears = (d, n) => { const x = new Date(d); x.setFullYear(x.getFullYear() + n); return x; };
  const startOfMonth = d => new Date(d.getFullYear(), d.getMonth(), 1);
  const endOfMonth = d => endOfDay(new Date(d.getFullYear(), d.getMonth() + 1, 0));
  const addMonths = (d, n) => new Date(d.getFullYear(), d.getMonth() + n, 1);
  const inRange = (d, a, b) => !!d && d >= a && d <= b;
  const sum = (arr, f) => arr.reduce((s, x) => s + f(x), 0);
  const fmtDate = d => d ? `${pad(d.getDate())} ${MONTHS[d.getMonth()]} ${d.getFullYear()}` : '—';
  const fmtTime = d => { let h = d.getHours(); const ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; return `${pad(h)}:${pad(d.getMinutes())} ${ap}`; };
  const fmtDateTime = d => d ? `${fmtDate(d)} · ${fmtTime(d)}` : '—';
  const fmtNum = n => Math.round(n).toLocaleString('en-US');
  const fmtAED = n => 'AED ' + fmtNum(n);
  const fmtAED2 = n => 'AED ' + (+n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const fmtAEDCompact = n => Math.abs(n) >= 1e6 ? `AED ${(n / 1e6).toFixed(2)}M` : fmtAED(n);
  const fmtAgo = d => {
    const mins = Math.round((API.now() - d) / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins} min ago`;
    if (mins < 24 * 60) return `${Math.round(mins / 60)} hr ago`;
    if (mins < 48 * 60) return 'Yesterday';
    return fmtDate(d);
  };
  const initials = s => String(s).split(/\s+/).filter(w => /[A-Za-z]/.test(w[0] || '')).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  /* initials avatar; the tone is picked from the name so a person keeps the same colour */
  const AV_TONES = ['pink', 'blue', 'amber', 'purple', 'green', 'gray'];
  const avatar = name => {
    const h = [...String(name)].reduce((n, ch) => n + ch.charCodeAt(0), 0);
    return `<span class="avatar sm av-${AV_TONES[h % AV_TONES.length]}" aria-hidden="true">${esc(initials(name))}</span>`;
  };
  const SOURCE = {
    'Email': ['mail', 'red'], 'Website': ['globe', 'blue'], 'Social Media': ['share', 'purple'],
    'Referral': ['users', 'green'], 'Phone': ['phone', 'amber'], 'Exhibition': ['award', 'pink']
  };
  const sourcePill = src => { const m = SOURCE[src] || ['info', 'gray']; return `<span class="pill pill-${m[1]} src-pill">${icon(m[0])}${esc(src)}</span>`; };
  const debounce = (fn, ms) => { let t; return function () { const a = arguments; clearTimeout(t); t = setTimeout(() => fn.apply(null, a), ms); }; };
  const branchOf = id => API.branch(id) || { name: '—', short: '—', code: '—', quoteFormat: '—' };
  const engName = id => (API.engineer(id) || { name: 'Unassigned' }).name;
  const deptName = id => (API.department(id) || { name: '—' }).name;
  const statusMeta = id => API.projectStatuses.find(s => s.id === id) || { name: id, color: '#999' };
  const quoteStatus = id => API.quoteStatuses.find(s => s.id === id) || { name: id, tone: 'gray' };
  const projLabel = id => { const p = API.project(id); return p ? p.projectNumber : '—'; };
  const leadOfQuote = q => API.lead(q.leadId) || { name: '—', company: '—', subject: '—' };
  const params = new URLSearchParams(location.search);
  const go = url => { location.href = url; };
  const fileNames = input => input && input.files ? Array.from(input.files).map(f => f.name) : [];

  const AUTH = window.RM_AUTH;
  const store = {
    get(k, d) { try { const v = AUTH.storage.get('rm.' + k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { AUTH.storage.set('rm.' + k, JSON.stringify(v)); },
    del(k) { AUTH.storage.del('rm.' + k); }
  };

  /* =======================================================
     Central definitions
     ======================================================= */
  const LEAD_TONE = { New: 'green', Contacted: 'blue', Proposal: 'amber', Negotiation: 'purple', Won: 'primary', Lost: 'gray' };
  const PROJECT_TONE = { popending: 'amber', open: 'primary', inprogress: 'pink', onhold: 'amber', completed: 'purple', invoicepending: 'blue', closed: 'green' };
  const STAGE_TONE = { new: 'green', draft: 'amber', sent: 'purple', awarded: 'primary' };
  const OPEN_STATUSES = ['open', 'inprogress'];
  const DRAFTISH = ['draft', 'pending', 'approved', 'rejected'];
  const ACTIVITY = {
    meeting: { label: 'Meeting', icon: 'calendar' },
    review: { label: 'Review', icon: 'file-text' },
    visit: { label: 'Site Visit', icon: 'map-pin' },
    approval: { label: 'Approval', icon: 'check-circle' }
  };
  const pill = (text, tone, title) => `<span class="pill pill-${tone}"${title ? ` title="${esc(title)}"` : ''}><i></i>${esc(text)}</span>`;
  const leadPill = l => { const s = API.leadStatus(l); return pill(s, LEAD_TONE[s]); };
  const quotePill = q => { const s = quoteStatus(q.status); return pill(s.name, s.tone, q.rejectReason || ''); };
  const projectPill = p => pill(statusMeta(p.status).name, PROJECT_TONE[p.status]);
  const stageName = id => (API.stages.find(s => s.id === id) || {}).name || id;
  const expStatusName = e => e.status === 'approved' ? (e.zoho === 'synced' ? 'Cleared' : 'Approved · Syncing') : e.status === 'rejected' ? 'Rejected' : 'Pending';
  const expStatus = e => e.status === 'approved' ? (e.zoho === 'synced' ? pill('Cleared', 'green') : pill('Approved · Syncing', 'blue'))
    : e.status === 'rejected' ? pill('Rejected', 'red', e.rejectReason) : pill('Pending', 'amber');
  const transferStatus = t => t.status === 'approved' ? `<span class="pill pill-green">${icon('lock')}Approved</span>`
    : t.status === 'rejected' ? pill('Rejected', 'red', t.rejectReason) : `<span class="pill pill-amber">${icon('lock')}Pending · Locked</span>`;
  const invoicePill = i => i.status === 'draft' ? pill('Draft', 'amber') : pill('Submitted', 'green');
  const transferRoute = t => `${t.fromType === 'main' ? 'Main Cash Account' : engName(t.fromEngineerId)} → ${t.toType === 'main' ? 'Main Cash Account' : engName(t.toEngineerId)}`;

  /* ---------- current user: ONE source of truth (AuthService, js/auth.js) ---------- */
  const me = AUTH.session();
  const can = perm => AUTH.can(perm);
  const myEngineer = () => me.engineerId || null;
  const roleLabel = () => me.roleLabel;
  API.setActor(me);

  /* role-aware dashboard composition (data inside is still permission-filtered) */
  const DASHBOARDS = {
    admin: { kpis: ['leads', 'quotes', 'activeProjects', 'expenses', 'invoiced'], quick: ['newLead', 'createQuote', 'addProject', 'addExpense', 'createInvoice', 'reports'] },
    estimation: { kpis: ['leads', 'draftQuotes', 'pendingQuotes', 'sentQuotes', 'awarded'], quick: ['newLead', 'createQuote', 'pendingQuotes', 'reports'] },
    engineer: { kpis: ['leads:My Leads', 'quotes:My Quotations', 'activeProjects:My Projects', 'cashExpense:My Expenses', 'cashInHand'], quick: ['createQuote', 'addExpense', 'newTransfer', 'recordPayment', 'cashInHand', 'reports'] },
    accountant: { kpis: ['awarded', 'awaitingActivation', 'pendingApprovals', 'expenses', 'invoiced'], quick: ['addProject', 'approvals', 'createInvoice', 'zoho', 'cashInHand', 'reports'] }
  };
  /* read-only view of the demo users for pages (label, user, email, kpis, quick) */
  const ROLES = {};
  AUTH.USERS.forEach(u => { ROLES[u.key] = Object.assign({ label: u.roleLabel, user: u.name, email: u.email, engineerId: u.engineerId, branches: u.branches }, DASHBOARDS[u.key]); });

  /* navigation: every item and sub-item declares the permission it needs */
  const NAV = [
    { id: 'dashboard', label: 'Dashboard', icon: 'nav-dashboard', href: 'index.html', perm: 'dashboard.view' },
    { id: 'leads', label: 'Lead Management', icon: 'nav-leads', href: 'leads.html', perm: 'leads.view', children: [['All Leads', 'leads.html', 'leads.view'], ['Add Lead', 'leads.html?new=1', 'leads.create'], ['Assign Engineer', 'leads.html?assign=1', 'leads.assign']] },
    { id: 'quotes', label: 'Quotations', icon: 'nav-quotes', href: 'quotations.html', perm: 'quotes.view', children: [['All Quotations', 'quotations.html', 'quotes.view'], ['Create Quotation', 'quotations.html?new=1', 'quotes.create'], ['Pending Approval', 'quotations.html?tab=pending', 'quotes.view'], ['Sent Quotations', 'quotations.html?tab=sent', 'quotes.view'], ['Awarded Quotes', 'quotations.html?tab=awarded', 'quotes.view']] },
    { id: 'projects', label: 'Projects', icon: 'hard-hat', href: 'projects.html', perm: 'projects.view', children: [['All Projects', 'projects.html', 'projects.view'], ['Create Project / PO', 'projects.html?tab=activation', 'projects.create'], ['In Progress', 'projects.html?tab=inprogress', 'projects.view'], ['Work Completed', 'projects.html?tab=completed', 'projects.view']] },
    { id: 'expenses', label: 'Expenses', icon: 'nav-expenses', href: 'expenses.html', perm: 'expenses.view', children: [['All Expenses', 'expenses.html', 'expenses.view'], ['Add Expense / Scan Receipt', 'expenses.html?new=1', 'expenses.create'], ['Cash in Hand', 'expenses.html?tab=cashinhand', 'expenses.view'], ['Internal Transfers', 'expenses.html?tab=transfers', 'transfers.view']] },
    { id: 'finance', label: 'Finance', icon: 'nav-finance', href: 'finance.html', perm: 'transactions.view', children: [['Transactions / Approvals', 'finance.html', 'transactions.view'], ['Transfer Approvals', 'finance.html?tab=transfers', 'transfers.approve'], ['Invoices', 'finance.html?tab=invoices', 'invoices.view']] },
    { id: 'zoho', label: 'Zoho Books', icon: 'nav-zoho', href: 'zoho.html', perm: 'zoho.view' },
    { id: 'reports', label: 'Reports', icon: 'nav-reports', href: 'reports.html', perm: 'reports.view' },
    { id: 'users', label: 'Users & Roles', icon: 'nav-users', href: 'users.html', perm: 'users.view', children: [['Users', 'users.html', 'users.view'], ['Roles & Permissions', 'users.html#roles', 'roles.view'], ['Audit Log', 'users.html#audit', 'audit.view']] },
    { id: 'branches', label: 'Branches', icon: 'nav-branches', href: 'branches.html', perm: 'branches.view' },
    { id: 'settings', label: 'Settings', icon: 'sliders', href: 'settings.html', perm: 'settings.view' }
  ];
  const navAllowed = id => { const n = NAV.find(x => x.id === id); return !n || can(n.perm); };

  /* =======================================================
     State (filters are shared across pages)
     ======================================================= */
  const DEFAULT_FILTERS = { country: 'UAE', branch: 'dxb', department: 'all', projectStatus: 'all', engineer: 'all', from: '2026-01-01', to: toISO(TODAY) };
  const allowedBranches = () => API.branches.filter(b => AUTH.allowedBranchIds(API.branches).includes(b.id));
  /* keep filters inside the user's branch / assignment access */
  function scopeFilters(f) {
    const allowed = allowedBranches().map(b => b.id);
    if (me.branches !== '*') {
      if (f.branch === 'all' ? allowed.length === 1 : !allowed.includes(f.branch)) f.branch = allowed[0];
      if (f.branch !== 'all') f.country = branchOf(f.branch).country;
    }
    if (me.engineerId) f.engineer = me.engineerId;
    return f;
  }
  const state = {
    // the filter bar was removed; only the topbar branch switcher (country + branch) carries over between visits
    filters: scopeFilters((({ country, branch }) => Object.assign({}, DEFAULT_FILTERS, country && { country }, branch && { branch }))(store.get('filters', {}))),
    role: me.key,
    user: me,
    ctx: null
  };
  const guard = (perm, fn) => function () {
    if (!can(perm)) { toast(`${roleLabel()} does not have permission for this action.`, 'error'); return; }
    return fn.apply(null, arguments);
  };

  function scopeOf(f) {
    return r => (f.country === 'all' || branchOf(r.branchId).country === f.country)
      && (f.branch === 'all' || r.branchId === f.branch)
      && (f.department === 'all' || !r.department || r.department === f.department)
      && (f.engineer === 'all' || r.engineerId === f.engineer);
  }
  function compute() {
    const f = state.filters;
    const from = startOfDay(parseISO(f.from)), to = endOfDay(parseISO(f.to));
    const inScope = scopeOf(f);
    const statusOk = p => f.projectStatus === 'all' || p.status === f.projectStatus;
    const mine = r => AUTH.inScope(r, me);
    const projects = API.projects.filter(p => inScope(p) && mine(p) && statusOk(p));
    const ids = new Set(projects.map(p => p.id));
    return {
      from, to, inScope,
      leads: API.leads.filter(l => inScope(l) && AUTH.canSeeLead(l, me)),
      quotes: API.quotes.filter(q => inScope(q) && mine(q)),
      projects,
      allProjects: API.projects.filter(p => inScope(p) && mine(p)),
      /* vehicle expenses have no project — they follow the branch / engineer filters instead */
      expenses: API.expenses.filter(e => (e.projectId ? ids.has(e.projectId) : inScope(e) && mine(e))),
      invoices: API.invoices.filter(i => ids.has(i.projectId)),
      transfers: API.transfers.filter(t => inScope(t) && mine(t)),
      payments: API.payments.filter(p => inScope(p) && mine(p)),
      engineers: API.engineers.filter(e => inScope({ branchId: e.branchId, engineerId: e.id }) && mine({ branchId: e.branchId, engineerId: e.id }))
    };
  }
  const branchVisible = id => {
    const f = state.filters, b = branchOf(id);
    return (f.country === 'all' || b.country === f.country) && (f.branch === 'all' || id === f.branch);
  };
  const scopeLabel = () => {
    const f = state.filters, country = API.countries.find(c => c.id === f.country);
    return f.branch !== 'all' ? branchOf(f.branch).name : (country ? `All ${country.name} Branches` : 'All Branches');
  };
  const rangeLabel = () => `${fmtDate(state.ctx.from)} – ${fmtDate(state.ctx.to)} · ${scopeLabel()}`;

  /* =======================================================
     UI primitives
     ======================================================= */
  function toast(msg, type) {
    const t = document.createElement('div');
    t.className = 'toast ' + (type || 'success');
    t.innerHTML = icon(type === 'error' ? 'alert' : type === 'info' ? 'info' : 'check-circle') + `<span>${esc(msg)}</span>`;
    $('#toasts').appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 220); }, 3600);
  }
  /* toast queued before navigating to another page */
  const flash = (msg, type) => { try { sessionStorage.setItem('rm.flash', JSON.stringify([msg, type])); } catch (e) { /* ignore */ } };
  /* run a data-layer action; show its validation error instead of failing silently */
  function act(fn, ok, type) {
    try {
      const r = fn();
      if (ok) toast(typeof ok === 'function' ? ok(r) : ok, type);
      return r === undefined ? true : r;
    } catch (e) { toast(e.message, 'error'); return null; }
  }

  let openPop = null;
  function closePop() {
    if (!openPop) return;
    openPop.anchor.setAttribute('aria-expanded', 'false');
    openPop.el.remove();
    openPop = null;
  }
  function popover(anchor, html, opts) {
    opts = opts || {};
    if (openPop && openPop.anchor === anchor) { closePop(); return null; }
    closePop();
    const el = document.createElement('div');
    el.className = 'popover';
    if (opts.width) el.style.width = Math.min(opts.width, window.innerWidth - 16) + 'px';
    el.innerHTML = html;
    document.body.appendChild(el);
    const r = anchor.getBoundingClientRect(), w = el.offsetWidth, h = el.offsetHeight;
    let left = opts.align === 'right' ? r.right - w : r.left;
    left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
    let top = r.bottom + 6;
    if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
    el.style.left = left + 'px';
    el.style.top = top + 'px';
    anchor.setAttribute('aria-expanded', 'true');
    openPop = { el, anchor };
    return el;
  }
  function menu(anchor, items, opts) {
    items = items.filter(Boolean);
    const html = items.map((it, i) => {
      if (it.divider) return '<div class="pop-div"></div>';
      if (it.header) return `<div class="pop-head">${esc(it.header)}</div>`;
      if (it.html) return it.html;
      return `<button class="pop-item${it.danger ? ' danger' : ''}" data-i="${i}"${it.disabled ? ' disabled' : ''}>${it.icon ? icon(it.icon) : ''}<span>${esc(it.label)}${it.hint ? `<br><small>${esc(it.hint)}</small>` : ''}</span>${it.checked ? icon('check', 'ic ck') : ''}</button>`;
    }).join('');
    const el = popover(anchor, html, opts);
    if (!el) return;
    el.addEventListener('click', e => {
      const b = e.target.closest('[data-i]');
      if (!b) return;
      const it = items[+b.dataset.i];
      closePop();
      if (it.run) it.run();
    });
  }

  const layers = [];
  let zTop = 100;
  function modal(o) {
    const wrap = document.createElement('div');
    wrap.className = 'modal-wrap';
    wrap.style.zIndex = ++zTop;
    wrap.innerHTML = `<div class="modal modal-${o.size || 'md'}" role="dialog" aria-modal="true" aria-label="${esc(o.title)}">
      <div class="modal-head"><div><h3>${esc(o.title)}</h3>${o.sub ? `<p>${esc(o.sub)}</p>` : ''}</div>
      <button class="icon-btn" data-close aria-label="Close">${icon('x')}</button></div>
      <div class="modal-body">${o.body}</div>${o.foot ? `<div class="modal-foot">${o.foot}</div>` : ''}</div>`;
    document.body.appendChild(wrap);
    requestAnimationFrame(() => wrap.classList.add('open'));
    let closed = false;
    const close = () => {
      if (closed) return;
      closed = true;
      const i = layers.indexOf(close); if (i > -1) layers.splice(i, 1);
      wrap.classList.remove('open');
      setTimeout(() => wrap.remove(), 180);
      if (o.onClose) o.onClose();
    };
    wrap.addEventListener('mousedown', e => { if (e.target === wrap) close(); });
    $$('[data-close]', wrap).forEach(b => b.addEventListener('click', close));
    layers.push(close);
    const m = { el: wrap, close };
    if (o.onMount) o.onMount(wrap, close);
    const first = $('input:not([type=hidden]):not([type=file]):not([readonly]), select, textarea', $('.modal-body', wrap));
    if (first && !o.noFocus) setTimeout(() => first.focus(), 60);
    return m;
  }

  function confirmDialog(o) {
    return new Promise(resolve => {
      let done = false;
      const m = modal({
        title: o.title, size: o.size || 'sm', noFocus: true,
        body: `<div class="confirm-text">${o.text}</div>`,
        foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn ${o.danger ? 'btn-danger' : 'btn-primary'}" data-ok>${esc(o.ok || 'Confirm')}</button>`,
        onClose: () => { if (!done) resolve(false); }
      });
      $('[data-ok]', m.el).addEventListener('click', () => { done = true; resolve(true); m.close(); });
    });
  }
  /* reason prompt used for rejections */
  function promptDialog(o) {
    return new Promise(resolve => {
      let done = false;
      const m = modal({
        title: o.title, sub: o.sub, size: 'sm',
        body: `<form id="promptForm" class="form-grid" novalidate>${field(o.label || 'Reason', `<textarea name="reason" rows="3" required maxlength="300" placeholder="${esc(o.placeholder || '')}"></textarea>`, { full: 1, req: 1 })}</form>`,
        foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn ${o.danger ? 'btn-danger' : 'btn-primary'}" type="submit" form="promptForm">${esc(o.ok || 'Submit')}</button>`,
        onClose: () => { if (!done) resolve(null); }
      });
      $('#promptForm', m.el).addEventListener('submit', e => {
        e.preventDefault();
        if (!validate(e.target)) return;
        done = true;
        resolve(val(e.target, 'reason').trim());
        m.close();
      });
    });
  }

  let drawer = null;
  function openDrawer(render) {
    if (!drawer) {
      const wrap = document.createElement('div');
      wrap.className = 'drawer-wrap';
      wrap.innerHTML = `<aside class="drawer" role="dialog" aria-modal="true"><div class="drawer-head"><h3></h3><button class="icon-btn" data-close aria-label="Close">${icon('x')}</button></div><div class="drawer-body"></div><div class="drawer-foot"></div></aside>`;
      document.body.appendChild(wrap);
      requestAnimationFrame(() => wrap.classList.add('open'));
      const close = () => {
        if (!drawer) return;
        const i = layers.indexOf(close); if (i > -1) layers.splice(i, 1);
        wrap.classList.remove('open');
        setTimeout(() => wrap.remove(), 200);
        drawer = null;
      };
      wrap.addEventListener('mousedown', e => { if (e.target === wrap) close(); });
      $('[data-close]', wrap).addEventListener('click', close);
      layers.push(close);
      drawer = { el: wrap, close, render: null };
    } else {
      const i = layers.indexOf(drawer.close);
      if (i > -1) { layers.splice(i, 1); layers.push(drawer.close); }
    }
    drawer.el.style.zIndex = ++zTop;
    drawer.render = render;
    paintDrawer();
  }
  function paintDrawer() {
    if (!drawer) return;
    const r = drawer.render();
    if (!r) { drawer.close(); return; }
    $('.drawer-head h3', drawer.el).textContent = r.title;
    $('.drawer-body', drawer.el).innerHTML = r.body;
    const foot = $('.drawer-foot', drawer.el);
    const actions = (r.actions || []).filter(Boolean);
    foot.hidden = !actions.length;
    foot.innerHTML = actions.map((a, i) => `<button class="btn ${a.cls || 'btn-ghost'}" data-a="${i}">${a.icon ? icon(a.icon) : ''}${esc(a.label)}</button>`).join('');
    $$('[data-a]', foot).forEach(b => b.addEventListener('click', () => actions[+b.dataset.a].run()));
  }

  /* ---------- form helpers ---------- */
  const opts = (list, value) => list.map(o => `<option value="${esc(o.v)}"${String(o.v) === String(value) ? ' selected' : ''}${o.disabled ? ' disabled' : ''}>${esc(o.t)}</option>`).join('');
  function field(label, control, o) {
    o = o || {};
    const tag = o.div ? 'div' : 'label';
    return `<${tag} class="field${o.full ? ' full' : ''}"><span class="field-label">${esc(label)}${o.star ? ' <b>*</b>' : ''}</span>${control}${o.hint ? `<small class="hint">${o.hint}</small>` : ''}</${tag}>`;
  }
  /* file inputs → styled upload area with drag & drop and a list of chosen files.
     The real <input type="file"> stays (invisible, covering the area), so forms read it exactly as before. */
  const fmtSize = b => b < 1024 ? `${b} B` : b < 1048576 ? `${Math.round(b / 1024)} KB` : `${(b / 1048576).toFixed(1)} MB`;
  function enhanceFileInput(input) {
    if (input.dataset.enhanced) return;
    input.dataset.enhanced = '1';
    const types = (input.accept || '').split(',').map(t => t.trim()).filter(Boolean)
      .map(t => t === 'image/*' ? 'Images' : t.replace(/^\./, '').toUpperCase()).join(', ');
    const box = document.createElement('div');
    box.className = 'file-drop';
    box.innerHTML = `<span class="fd-icon">${icon('upload')}</span><span class="fd-text"><span><strong>Click to upload</strong> or drag &amp; drop</span><small>${esc(types || 'Any file type')}${input.multiple ? ' · multiple files allowed' : ''}</small></span>`;
    const list = document.createElement('ul');
    list.className = 'fd-list';
    input.parentNode.insertBefore(box, input);
    box.appendChild(input);
    box.after(list);
    const paint = () => {
      const files = Array.from(input.files || []);
      list.innerHTML = files.map(f => `<li>${icon('file-text', 'ic sm')}<span>${esc(f.name)}</span><small>${fmtSize(f.size)}</small></li>`).join('') +
        (files.length ? `<li class="fd-clear"><button type="button" class="link-btn">${icon('x', 'ic xs')}Clear</button></li>` : '');
      box.classList.toggle('has-files', files.length > 0);
    };
    input.addEventListener('change', () => setTimeout(paint)); // after any page handler that resets the input
    input.addEventListener('dragenter', () => box.classList.add('drag'));
    ['dragleave', 'drop'].forEach(ev => input.addEventListener(ev, () => box.classList.remove('drag')));
    list.addEventListener('click', e => {
      if (!e.target.closest('.fd-clear button')) return;
      e.preventDefault();
      input.value = '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }
  /* data-plain opts an input out (it brings its own compact button) */
  const enhanceFileInputs = root => $$('input[type=file]:not([data-plain])', root).forEach(enhanceFileInput);

  const selectCtl = (name, list, value, attrs) => `<span class="select-wrap"><select name="${name}" ${attrs || ''}>${opts(list, value)}</select></span>`;
  const branchOpts = () => API.branches.map(b => ({ v: b.id, t: `${b.name} (${b.code})` }));
  const engOpts = branchId => API.engineers.filter(e => !branchId || e.branchId === branchId).map(e => ({ v: e.id, t: e.name }));
  const deptOpts = () => API.departments.map(d => ({ v: d.id, t: d.name }));
  const val = (form, name) => form.elements[name] ? form.elements[name].value : '';

  function validate(form) {
    let ok = true;
    /* once shown, an error clears as soon as its field is edited */
    if (!form.dataset.liveErrors) {
      form.dataset.liveErrors = '1';
      const clear = e => {
        const f = e.target.closest && e.target.closest('.field.invalid');
        if (!f) return;
        f.classList.remove('invalid');
        $$('.field-error', f).forEach(x => x.remove());
      };
      form.addEventListener('input', clear);
      form.addEventListener('change', clear);
    }
    $$('.field-error', form).forEach(e => e.remove());
    $$('.field.invalid', form).forEach(e => e.classList.remove('invalid'));
    const mark = (inp, msg) => {
      ok = false;
      const f = inp.closest('.field');
      if (f && !f.classList.contains('invalid')) { f.classList.add('invalid'); f.insertAdjacentHTML('beforeend', `<small class="field-error">${msg}</small>`); }
    };
    $$('input, select, textarea', form).forEach(inp => {
      if (inp.disabled) return;
      const v = String(inp.value).trim();
      if (inp.required && !v) return mark(inp, 'This field is required');
      if (!v) return;
      if (inp.type === 'number' && inp.required && !(+v > 0)) return mark(inp, 'Enter a value greater than 0');
      if (inp.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return mark(inp, 'Enter a valid email address');
      if (inp.dataset.phone !== undefined && !/^\+?[\d\s-]{7,}$/.test(v)) return mark(inp, 'Enter a valid phone number');
      if (inp.max && inp.type === 'number' && +v > +inp.max) return mark(inp, `Maximum is ${fmtNum(+inp.max)}`);
    });
    if (!ok) { const first = $('.field.invalid input, .field.invalid select, .field.invalid textarea', form); if (first) first.focus(); }
    return ok;
  }

  function downloadFile(name, content, type) {
    const blob = new Blob([content], { type: type || 'text/plain;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }
  function downloadCSV(name, columns, rows) {
    const cols = columns.filter(c => c.get);
    const q = v => `"${String(v == null ? '' : v).replace(/"/g, '""')}"`;
    const csv = [cols.map(c => q(c.label)).join(',')].concat(rows.map(r => cols.map(c => q(c.get(r))).join(','))).join('\r\n');
    downloadFile(name.replace(/[^\w-]+/g, '_') + '.csv', '﻿' + csv, 'text/csv;charset=utf-8');
    toast(`${name} downloaded (${rows.length} rows)`);
  }
  const PRINT_CSS = 'body{font:12px Inter,Arial,sans-serif;padding:24px;color:#121418}h1{font-size:18px;color:#941C3C;margin:0 0 4px}p{color:#666;margin:0 0 16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#f4f5f7}';
  function printHTML(title, html, css) {
    const w = window.open('', '_blank');
    if (!w) { toast('Allow pop-ups to print.', 'error'); return; }
    w.document.write(`<!doctype html><title>${esc(title)}</title><style>${css || PRINT_CSS}</style>${html}`);
    w.document.close();
    w.focus();
    w.print();
  }
  function printTable(title, columns, rows) {
    const cols = columns.filter(c => c.get);
    printHTML(title, `<h1>Rich Mount ERP — ${esc(title)}</h1><p>${esc(rangeLabel())} · ${rows.length} rows</p>
      <table><tr>${cols.map(c => `<th>${esc(c.label)}</th>`).join('')}</tr>${rows.slice(0, 2000).map(x => `<tr>${cols.map(c => `<td>${esc(c.get(x))}</td>`).join('')}</tr>`).join('')}</table>`);
  }

  /* ---------- data table (used on pages and in modals) ---------- */
  /* date periods for list filters; periodStart → first day included, null = all time */
  const PERIODS = [['month', 'This Month'], ['7', 'Last 7 Days'], ['30', 'Last 30 Days'], ['year', 'This Year'], ['all', 'All Time']];
  function periodStart(p, to) {
    if (p === 'month') return startOfMonth(to);
    if (p === 'year') return new Date(to.getFullYear(), 0, 1);
    if (p === '7' || p === '30') return startOfDay(addDays(to, -(+p) + 1));
    return null;
  }
  function dataTable(host, o) {
    let q = '', page = 0, shown = [];
    let tab = o.tabs ? ((o.tabs.find(t => t.id === o.tab) || o.tabs[0]).id) : null;
    const size = o.pageSize || 10;
    const titleH = o.title ? `<h2>${o.icon ? `<span class="head-ic">${icon(o.icon)}</span>` : ''}${esc(o.title)}</h2>` : '';
    const search = `<label class="search-input${o.headTools ? ' recent-search' : ''}">${icon('search', 'ic sm')}<input type="search" placeholder="${esc(o.searchPlaceholder || 'Search records...')}" data-q aria-label="Search records"></label>`;
    const tabSelH = o.tabs && o.tabSelect ? `<span class="select-wrap mini with-icon">${icon('filter')}<select data-tabsel aria-label="Status">${o.tabs.map(t => `<option value="${t.id}">${esc(t.label)}</option>`).join('')}</select></span>` : '';
    const periodH = o.period ? `<span class="select-wrap mini with-icon">${icon('calendar')}<select data-period aria-label="Date period">${PERIODS.map(p => `<option value="${p[0]}"${p[0] === (o.periodDefault || 'all') ? ' selected' : ''}>${p[1]}</option>`).join('')}</select></span>` : '';
    /* headTools: export/print live in the page header; the card head matches the dashboard's Recent Leads head */
    host.innerHTML = `${o.headTools
        ? `<header class="card-head recent-head"><h2 class="lg">${esc(o.title || '')}</h2><div class="head-actions">${search}${tabSelH}${periodH}${o.toolbar || ''}${o.headExtra || ''}</div></header>`
        : `${o.title ? `<header class="card-head">${titleH}${o.headExtra || ''}</header>` : ''}`}
      ${o.tabs && !o.tabSelect ? `<div class="tabs" role="tablist">${o.tabs.map(t => `<button class="tab" role="tab" data-tab="${t.id}">${esc(t.label)}<span></span></button>`).join('')}</div>` : ''}
      ${o.intro || ''}
      ${o.headTools ? '' : `<div class="tm-toolbar">
        ${search}${tabSelH}
        <span class="tm-count"></span>
        ${o.toolbar || ''}
        <button class="btn btn-ghost btn-sm" data-export>${icon('download', 'ic sm')}Export CSV</button>
        ${o.print ? `<button class="btn btn-ghost btn-sm" data-print aria-label="Print">${icon('printer', 'ic sm')}Print</button>` : ''}
      </div>`}
      <div class="table-wrap ${o.compact ? 'tall' : 'page'}"><table class="table cards"><thead><tr>${o.rowNumbers ? `<th>${esc(o.rowNumbers)}</th>` : ''}${o.columns.map(c => `<th class="${c.cls || ''}">${esc(c.label)}${c.headIcon ? ` ${icon(c.headIcon, 'ic sm')}` : ''}</th>`).join('')}</tr></thead><tbody></tbody></table></div>
      <div class="pager dt-pager"><span class="pager-info"></span><span class="pager-btns"></span></div>`;
    const tbody = $('tbody', host);
    const source = () => (typeof o.rows === 'function' ? o.rows() : o.rows);
    function filtered() {
      let list = source();
      if (o.tabs) list = list.filter(o.tabs.find(t => t.id === tab).filter);
      if (q) list = list.filter(r => o.columns.some(c => c.get && String(c.get(r)).toLowerCase().includes(q)));
      const ps = o.period && periodStart($('[data-period]', host).value, TODAY);
      if (ps) list = list.filter(r => o.period(r) >= ps);
      return list;
    }
    function draw() {
      if (o.tabs) {
        const all = source();
        $$('[data-tab]', host).forEach(b => {
          const t = o.tabs.find(x => x.id === b.dataset.tab);
          $('span', b).textContent = all.filter(t.filter).length;
          b.classList.toggle('active', t.id === tab);
          b.setAttribute('aria-selected', String(t.id === tab));
        });
        const sel = $('[data-tabsel]', host);
        if (sel) {
          o.tabs.forEach((t, i) => { sel.options[i].textContent = t.id === 'all' ? t.label : `${t.label} (${all.filter(t.filter).length})`; });
          sel.value = tab;
        }
      }
      const list = filtered();
      const pages = Math.max(1, Math.ceil(list.length / size));
      if (page >= pages) page = pages - 1;
      shown = list.slice(page * size, (page + 1) * size);
      tbody.innerHTML = shown.length
        ? shown.map((r, i) => `<tr data-i="${i}" class="${o.onRow ? 'clickable' : ''}">${o.rowNumbers ? `<td class="dt-no" data-label="${esc(o.rowNumbers)}">${page * size + i + 1}</td>` : ''}${o.columns.map(c => `<td class="${c.cls || ''}${!c.label || c.label === 'Action' ? ' cell-act' : ''}" data-label="${esc(c.label || '')}">${c.render ? c.render(r) : esc(c.get(r))}</td>`).join('')}</tr>`).join('')
        : `<tr><td class="empty" colspan="${o.columns.length + (o.rowNumbers ? 1 : 0)}">${esc(o.empty || 'No records match the current filters.')}</td></tr>`;
      if ($('.tm-count', host)) $('.tm-count', host).textContent = `${fmtNum(list.length)} record${list.length === 1 ? '' : 's'}`;
      $('.pager', host).hidden = list.length <= size;
      $('.pager-info', host).textContent = list.length ? `Showing ${page * size + 1}–${page * size + shown.length} of ${fmtNum(list.length)}` : '';
      // ‹ 1 2 3 4 5 › — at most 5 page numbers around the current page (same as the dashboard pager)
      const first = Math.max(0, Math.min(page - 2, pages - 5)), last = Math.min(pages - 1, first + 4);
      let nums = '';
      for (let n = first; n <= last; n++) nums += `<button class="btn btn-ghost btn-sm pager-num${n === page ? ' active' : ''}" data-page="${n}"${n === page ? ' aria-current="page"' : ''}>${n + 1}</button>`;
      $('.pager-btns', host).innerHTML = `<button class="btn btn-ghost btn-sm" data-page="${page - 1}"${page === 0 ? ' disabled' : ''} aria-label="Previous page">${icon('chevron-right', 'ic sm flip')}</button>${nums}`
        + `<button class="btn btn-ghost btn-sm" data-page="${page + 1}"${page >= pages - 1 ? ' disabled' : ''} aria-label="Next page">${icon('chevron-right', 'ic sm')}</button>`;
    }
    $('[data-q]', host).addEventListener('input', debounce(e => { q = e.target.value.trim().toLowerCase(); page = 0; draw(); }, 120));
    $$('[data-tab]', host).forEach(b => b.addEventListener('click', () => { tab = b.dataset.tab; page = 0; draw(); if (o.onTab) o.onTab(tab); }));
    const tabSel = $('[data-tabsel]', host);
    if (tabSel) tabSel.addEventListener('change', () => { tab = tabSel.value; page = 0; draw(); if (o.onTab) o.onTab(tab); });
    const perSel = $('[data-period]', host);
    if (perSel) perSel.addEventListener('change', () => { page = 0; draw(); });
    const exportCSV = () => downloadCSV(o.exportName || o.title || 'Export', o.columns, filtered());
    const print = () => printTable(o.exportName || o.title, o.columns, filtered());
    const ex = $('[data-export]', host);
    if (ex) ex.addEventListener('click', exportCSV);
    const pr = $('[data-print]', host);
    if (pr) pr.addEventListener('click', print);
    $('.pager-btns', host).addEventListener('click', e => {
      const b = e.target.closest('[data-page]');
      if (b && !b.disabled) { page = +b.dataset.page; draw(); $('.table-wrap', host).scrollTop = 0; }
    });
    tbody.addEventListener('click', e => {
      const tr = e.target.closest('tr[data-i]');
      if (!tr) return;
      const row = shown[+tr.dataset.i];
      const btn = e.target.closest('[data-act]');
      if (btn) { if (o.onAction) o.onAction(btn.dataset.act, row, draw, btn); return; }
      if (e.target.closest('a')) return;
      if (o.onRow) o.onRow(row);
    });
    draw();
    return { draw, el: host, exportCSV, print, setTab(t) { tab = t; page = 0; draw(); } };
  }
  function tableModal(o) {
    const m = modal({ title: o.title, sub: o.sub, body: '<div data-host></div>', size: 'xl', noFocus: true });
    const t = dataTable($('[data-host]', m.el), Object.assign({}, o, { title: null, compact: true, exportName: o.title }));
    return { draw: t.draw, close: m.close };
  }

  /* ---------- shared column sets ---------- */
  const viewMoreCol = { label: 'Action', cls: 'ta-c', render: () => `<span class="row-actions"><button class="act view" data-act="view" title="View" aria-label="View">${icon('eye')}</button><button class="act" data-act="menu" title="More" aria-label="More actions">${icon('more-vertical')}</button></span>` };
  const COLS = {
    leads: [
      { label: 'Lead No', get: l => l.leadNumber },
      { label: 'Lead Name', get: l => l.name, cls: 'strong' },
      { label: 'Company', get: l => l.company },
      { label: 'Contact', get: l => l.contact || '—' },
      { label: 'Subject', get: l => l.subject },
      { label: 'Branch', get: l => branchOf(l.branchId).short },
      { label: 'Assigned To', get: l => engName(l.engineerId) },
      { label: 'Est. Value', get: l => l.estValue || 0, render: l => l.estValue ? fmtAED(l.estValue) : '—', cls: 'num' },
      { label: 'Status', get: l => API.leadStatus(l), render: leadPill },
      { label: 'Created', get: l => fmtDate(l.createdAt) }
    ],
    quotes: [
      { label: 'Quote No', get: q => q.number, cls: 'strong' },
      { label: 'Client', get: q => leadOfQuote(q).name },
      { label: 'Subject', get: q => leadOfQuote(q).subject },
      { label: 'Branch', get: q => branchOf(q.branchId).short },
      { label: 'Format', get: q => branchOf(q.branchId).quoteFormat },
      { label: 'Engineer', get: q => engName(q.engineerId) },
      { label: 'Total (incl. VAT)', get: q => q.total, render: q => fmtAED(q.total), cls: 'num' },
      { label: 'Status', get: q => quoteStatus(q.status).name, render: quotePill },
      { label: 'Quote Date', get: q => fmtDate(q.date) }
    ],
    projects: [
      { label: 'Project No', get: p => p.projectNumber, cls: 'strong' },
      { label: 'Project', get: p => p.name },
      { label: 'Branch', get: p => branchOf(p.branchId).short },
      { label: 'Engineer', get: p => engName(p.engineerId) },
      { label: 'PO Number', get: p => p.poNumber || (API.hasPO(p) ? 'PO received (no number)' : p.emailApproval ? 'Email approval — PO awaited' : 'PO pending') },
      { label: 'Value', get: p => p.value, render: p => fmtAED(p.value), cls: 'num' },
      { label: 'Status', get: p => statusMeta(p.status).name, render: projectPill },
      { label: 'Start', get: p => fmtDate(p.startDate) }
    ],
    expenses: [
      { label: 'Ref', get: e => e.ref, cls: 'strong' },
      { label: 'Date', get: e => fmtDate(e.date) },
      { label: 'Project', get: e => (e.projectId ? projLabel(e.projectId) : `Vehicle · ${e.vehicle || '—'}`) },
      { label: 'Engineer', get: e => engName(e.engineerId) },
      { label: 'Method', get: e => e.type === 'cash' ? 'Cash' : 'Card', render: e => e.type === 'cash' ? 'Cash' : `Card <small style="color:var(--muted)">${esc(e.card || '')}</small>` },
      { label: 'Vendor', get: e => e.vendor },
      { label: 'Type', get: e => e.category },
      { label: 'Amount', get: e => e.amount, render: e => fmtAED2(e.amount), cls: 'num' },
      { label: 'Status', get: expStatusName, render: expStatus }
    ],
    transfers: [
      { label: 'Ref', get: t => t.ref, cls: 'strong' },
      { label: 'Date', get: t => fmtDate(t.date) },
      { label: 'From → To', get: transferRoute },
      { label: 'Note', get: t => t.note || '—' },
      { label: 'Amount', get: t => t.amount, render: t => fmtAED(t.amount), cls: 'num' },
      { label: 'Status', get: t => t.status, render: transferStatus }
    ],
    invoices: [
      { label: 'Invoice No', get: i => i.number, cls: 'strong' },
      { label: 'Date', get: i => fmtDate(i.date) },
      { label: 'Project', get: i => projLabel(i.projectId) },
      { label: 'Quote', get: i => i.quoteNumber },
      { label: 'Type', get: i => i.final ? 'Final' : 'Progress', render: i => i.final ? pill('Final', 'primary') : pill('Progress', 'blue') },
      { label: 'Amount', get: i => i.amount, render: i => fmtAED(i.amount), cls: 'num' },
      { label: 'VAT', get: i => i.tax, render: i => fmtAED(i.tax), cls: 'num' },
      { label: 'Total', get: i => i.total, render: i => fmtAED(i.total), cls: 'num' },
      { label: 'Status', get: i => i.status, render: invoicePill }
    ]
  };

  /* =======================================================
     KPI definitions + renderer (all computed from live state)
     ======================================================= */
  const live = e => e.status !== 'rejected';
  const KPI = {
    leads: { label: 'Total Leads', icon: 'users', calc: (c, a, b) => c.leads.filter(l => inRange(l.createdAt, a, b)).length, href: 'leads.html' },
    quotes: { label: 'Quotations', icon: 'file-text', calc: (c, a, b) => c.quotes.filter(q => inRange(q.date, a, b)).length, href: 'quotations.html' },
    draftQuotes: { label: 'Draft Quotations', icon: 'file-text', calc: (c, a, b) => c.quotes.filter(q => DRAFTISH.includes(q.status) && inRange(q.date, a, b)).length, href: 'quotations.html?tab=draft' },
    pendingQuotes: { label: 'Pending Approval', icon: 'clock', calc: (c, a, b) => c.quotes.filter(q => q.status === 'pending' && inRange(q.date, a, b)).length, href: 'quotations.html?tab=pending' },
    sentQuotes: { label: 'Sent Quotations', icon: 'send', calc: (c, a, b) => c.quotes.filter(q => inRange(q.sentDate, a, b)).length, href: 'quotations.html?tab=sent' },
    awarded: { label: 'Awarded Quotes', icon: 'award', calc: (c, a, b) => c.quotes.filter(q => inRange(q.awardDate, a, b)).length, href: 'quotations.html?tab=awarded' },
    quoteValue: { label: 'Quoted Value', icon: 'coins', money: true, calc: (c, a, b) => sum(c.quotes.filter(q => inRange(q.date, a, b)), q => q.amount), href: 'quotations.html' },
    activeProjects: { label: 'Active Projects', icon: 'briefcase', calc: (c, a, b) => c.projects.filter(p => p.createdAt <= b && (!p.endDate || p.endDate >= a) && p.status !== 'closed').length, href: 'projects.html' },
    awaitingActivation: { label: 'Awaiting Project / PO', icon: 'clock', noTrend: true, calc: (c, a, b) => c.leads.filter(l => l.stage === 'awarded' && !l.projectId).length + c.allProjects.filter(p => p.status === 'popending').length, href: 'projects.html?tab=activation' },
    completedProjects: { label: 'Work Completed', icon: 'check-circle', calc: (c, a, b) => c.projects.filter(p => inRange(p.completedAt, a, b)).length, href: 'projects.html?tab=completed' },
    expenses: { label: 'Total Expenses', icon: 'wallet', money: true, calc: (c, a, b) => sum(c.expenses.filter(e => live(e) && inRange(e.date, a, b)), e => e.amount), href: 'expenses.html' },
    cashExpense: { label: 'Cash Expense', icon: 'wallet', money: true, calc: (c, a, b) => sum(c.expenses.filter(e => e.type === 'cash' && live(e) && inRange(e.date, a, b)), e => e.amount), href: 'expenses.html?tab=cash' },
    cardExpense: { label: 'Card Expense', icon: 'credit-card', money: true, calc: (c, a, b) => sum(c.expenses.filter(e => e.type === 'card' && live(e) && inRange(e.date, a, b)), e => e.amount), href: 'expenses.html?tab=card' },
    transfers: { label: 'Total Transfers', icon: 'refresh', money: true, calc: (c, a, b) => sum(c.transfers.filter(t => t.status === 'approved' && inRange(t.date, a, b)), t => t.amount), href: 'expenses.html?tab=transfers' },
    invoiced: { label: 'Total Invoiced', icon: 'invoice', money: true, calc: (c, a, b) => sum(c.invoices.filter(i => i.status === 'submitted' && inRange(i.date, a, b)), i => i.amount), href: 'finance.html?tab=invoices' },
    cashInHand: { label: 'Cash in Hand', icon: 'wallet', money: true, noTrend: true, calc: (c, a, b) => sum(c.engineers, e => API.cashBalance(e.id, b)), href: 'expenses.html?tab=cashinhand' },
    pendingApprovals: {
      label: 'Pending Approvals', icon: 'clock', noTrend: true,
      calc: (c, a, b) => c.expenses.filter(e => e.status === 'pending' && e.date <= b).length + c.transfers.filter(t => t.status === 'pending').length, href: 'finance.html'
    }
  };

  function renderKpis(el, keys) {
    const c = state.ctx, pf = addYears(c.from, -1), pt = addYears(c.to, -1);
    el.innerHTML = keys.map(entry => {
      const parts = entry.split(':'), key = parts[0], k = KPI[key];
      const label = parts[1] || k.label;
      const cur = k.calc(c, c.from, c.to);
      const prev = k.noTrend ? 0 : k.calc(c, pf, pt);
      let badge = '', note = '';
      if (k.noTrend) note = `As of ${fmtDate(c.to)}`;
      else if (!prev) note = 'No data last year';
      else {
        const pct = (cur - prev) / Math.abs(prev) * 100;
        badge = `<span class="kpi-trend ${pct >= 0 ? 'up' : 'down'}" title="vs same period last year">${icon(pct >= 0 ? 'arrow-up' : 'arrow-down', 'ic xs')}${Math.abs(pct).toFixed(0)}%</span>`;
      }
      return `<a class="kpi" href="${k.href}" data-kpi="${key}" title="Open ${esc(label)}">
        <span class="kpi-top"><span class="kpi-icon">${icon(k.icon)}</span>${badge}</span>
        <span class="kpi-value">${k.money ? fmtAEDCompact(cur) : fmtNum(cur)}</span>
        <span class="kpi-label">${esc(label)}</span>
        ${note ? `<span class="kpi-note">${note}</span>` : ''}</a>`;
    }).join('');
  }

  /* =======================================================
     Workflow forms
     ======================================================= */
  const LEAD_DRAFT_KEY = 'leadDraft';
  /* client names for the lead form: saved clients + names already used on leads */
  const clientNames = () => [...new Set(store.get('clients', []).concat(API.leads.map(l => l.name)))].sort((a, b) => a.localeCompare(b));
  function addClientForm(onAdd) {
    const m = modal({
      title: 'Add Client', size: 'sm',
      body: `<form class="form-grid" id="clientForm" novalidate>${field('Client Name', '<input name="client" required maxlength="80" placeholder="Client Name">', { req: 1, star: 1, full: 1 })}</form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="clientForm">${icon('plus')}Add Client</button>`
    });
    const fm = $('#clientForm', m.el);
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const name = val(fm, 'client').trim();
      if (clientNames().some(c => c.toLowerCase() === name.toLowerCase())) { toast(`${name} is already in the client list`, 'info'); m.close(); onAdd(name); return; }
      store.set('clients', store.get('clients', []).concat(name));
      m.close();
      onAdd(name);
      toast(`${name} added to clients`);
    });
  }
  function leadForm(lead) {
    const isEdit = !!lead, f = state.filters;
    const draft = !isEdit ? store.get(LEAD_DRAFT_KEY, null) : null;
    const src = lead || (draft && draft.values) || {};
    const branchId = src.branchId || (f.branch !== 'all' ? f.branch : 'dxb');
    const engId = src.engineerId || myEngineer() || (f.engineer !== 'all' ? f.engineer : '');
    const v = k => esc(src[k] == null ? '' : src[k]);
    const leadDate = src.leadDate ? (src.leadDate instanceof Date ? toISO(src.leadDate) : src.leadDate) : toISO(TODAY);
    const m = modal({
      title: isEdit ? 'Edit Lead' : 'New Lead', size: 'lg',
      sub: isEdit ? `${lead.leadNumber} · ${stageName(lead.stage)}` : '',
      body: `<form class="form-grid" id="leadForm" novalidate>
        ${draft ? `<div class="info-box full" id="draftNote">${icon('info')}<span>Restored your unsaved draft from ${esc(fmtTime(new Date(draft.savedAt)))}. <button type="button" class="link-btn" id="discardDraft">Discard draft</button></span></div>` : ''}
        ${field('Lead Number', `<input value="${esc(isEdit ? lead.leadNumber : API.nextLeadNumber())}" readonly>`, { hint: isEdit ? '' : 'Assigned automatically' })}
        ${field('Lead Date', `<input name="leadDate" type="date" required value="${leadDate}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Client Name', `<span class="field-inline"><input name="name" required maxlength="80" value="${v('name')}" placeholder="Client Name" list="clientList" autocomplete="off"><datalist id="clientList">${clientNames().map(c => `<option value="${esc(c)}">`).join('')}</datalist>
          <button type="button" class="btn btn-soft" id="addClient">${icon('plus', 'ic sm')}Add Client</button></span>`, { req: 1, star: 1, full: 1, div: 1 })}
        ${field('RFQ / PR Number', `<input name="rfqNumber" maxlength="40" value="${v('rfqNumber')}" placeholder="RFQ / PR Number">`, { star: 1 })}
        ${field('Project Subject', `<input name="subject" required maxlength="100" value="${v('subject')}" placeholder="Project Subject">`, { req: 1 })}
        ${field('Contact Person', `<input name="contact" required maxlength="60" value="${v('contact')}" placeholder="Full name">`, { req: 1 })}
        ${field('Phone', `<input name="phone" required data-phone maxlength="20" value="${v('phone')}" placeholder="Phone Number" inputmode="tel">`, { req: 1 })}
        ${field('Email', `<input name="email" type="email" maxlength="80" value="${v('email')}" placeholder="Email">`)}
        ${field('Budget (AED)', `<input name="estValue" type="number" min="0" step="1000" value="${v('estValue')}" placeholder="0">`)}
       
        ${field('Source', selectCtl('source', API.sources.map(s => ({ v: s, t: s })), src.source || 'Website'), { star: 1 })}
        <input type="hidden" name="branchId" value="${esc(branchId)}">
        ${field('Assigned Engineer', selectCtl('engineerId', engOpts(branchId), engId, myEngineer() ? 'disabled' : ''), { star: 1 })}
        ${field('Notes', `<textarea name="notes" rows="3" maxlength="500" placeholder="Note">${v('notes')}</textarea>`, { full: 1, star: 1 })}
        ${field('Attachments', '<input name="attachments" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.dwg,.xlsx,.docx">', { full: 1, star: 1, hint: (src.attachments && src.attachments.length) ? `Attached: ${src.attachments.map(esc).join(', ')}` : '' })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button>${isEdit ? '' : `<button class="btn btn-ghost" type="button" id="saveLeadDraft">${icon('bookmark')}Save Draft</button>`}<button class="btn btn-primary" type="submit" form="leadForm">${icon(isEdit ? 'check' : 'plus')}${isEdit ? 'Save Changes' : 'Create Lead'}</button>`
    });
    const fm = $('#leadForm', m.el);
    const read = () => ({
      leadDate: val(fm, 'leadDate'), name: val(fm, 'name').trim(), rfqNumber: val(fm, 'rfqNumber').trim(), company: val(fm, 'company').trim(), subject: val(fm, 'subject').trim(),
      contact: val(fm, 'contact').trim(), phone: val(fm, 'phone').trim(), email: val(fm, 'email').trim(), estValue: +val(fm, 'estValue') || 0,
      department: val(fm, 'department'), source: val(fm, 'source'), branchId: val(fm, 'branchId'), engineerId: val(fm, 'engineerId'), notes: val(fm, 'notes').trim()
    });
    fm.elements.branchId.addEventListener('change', () => { fm.elements.engineerId.innerHTML = opts(engOpts(fm.elements.branchId.value)); });
    $('#addClient', m.el).addEventListener('click', () => addClientForm(name => {
      $('#clientList', m.el).insertAdjacentHTML('beforeend', `<option value="${esc(name)}">`);
      fm.elements.name.value = name;
    }));
    const dd = $('#discardDraft', m.el);
    if (dd) dd.addEventListener('click', () => { store.del(LEAD_DRAFT_KEY); m.close(); toast('Draft discarded', 'info'); leadForm(); });
    const sd = $('#saveLeadDraft', m.el);
    if (sd) sd.addEventListener('click', () => {
      store.set(LEAD_DRAFT_KEY, { values: read(), savedAt: new Date().toISOString() });
      m.close();
      toast('Lead draft saved — it will be restored when you open New Lead again', 'info');
    });
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const data = read();
      const files = fileNames(fm.elements.attachments);
      if (isEdit) {
        if (files.length) data.attachments = (lead.attachments || []).concat(files);
        if (act(() => API.updateLead(lead.id, data), `${lead.leadNumber} updated`)) m.close();
      } else {
        data.attachments = files;
        const l = act(() => API.createLead(data), r => `${r.leadNumber} created and assigned to ${engName(r.engineerId)}`);
        if (!l) return;
        store.del(LEAD_DRAFT_KEY);
        m.close();
        if (!branchVisible(l.branchId) || (state.filters.engineer !== 'all' && state.filters.engineer !== l.engineerId)) toast('The new lead is outside the current filters, so it is not counted on this view.', 'info');
      }
    });
  }

  function assignForm(id) {
    const l = API.lead(id);
    const m = modal({
      title: 'Assign Engineer', sub: `${l.leadNumber} · ${l.name}`, size: 'sm',
      body: `<form id="assignForm" class="form-grid">${field('Engineer', selectCtl('engineerId', engOpts(l.branchId), l.engineerId), { full: 1, hint: `Engineers from ${branchOf(l.branchId).name}` })}</form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="assignForm">Assign</button>`
    });
    $('#assignForm', m.el).addEventListener('submit', e => {
      e.preventDefault();
      const eng = val(e.target, 'engineerId');
      m.close();
      if (eng !== l.engineerId) act(() => API.assignEngineer(l.id, eng), `${l.name} assigned to ${engName(eng)}`);
    });
  }

  /* assign / re-assign engineers for all unquoted leads in one place */
  function assignBoard() {
    if (!can('leads.assign')) { toast(`${roleLabel()} cannot assign engineers.`, 'error'); return; }
    const rows = state.ctx.leads.filter(l => l.stage === 'new' && !l.lost).sort((a, b) => b.createdAt - a.createdAt);
    if (!rows.length) { toast('There are no unquoted leads in the current filters.', 'info'); return; }
    const changes = {};
    const m = modal({
      title: 'Assign Engineers', size: 'xl', noFocus: true,
      body: `<div class="tm-toolbar"><label class="search-input recent-search">${icon('search', 'ic sm')}<input type="search" placeholder="Search by name, company, subject..." data-q aria-label="Search leads"></label><span class="tm-count" data-count></span></div>
        <div class="table-wrap tall"><table class="table cards assign-table"><thead><tr><th>Sl No</th><th>Lead No</th><th>Lead Name</th><th>Subject</th><th>Created ${icon('arrow-down', 'ic sm')}</th><th>Assigned Engineer</th></tr></thead><tbody>
        ${rows.map((l, i) => `<tr data-id="${l.id}" data-text="${esc((l.leadNumber + ' ' + l.name + ' ' + l.company + ' ' + l.subject).toLowerCase())}">
          <td class="dt-no" data-label="Sl No">${i + 1}</td><td data-label="Lead No">${esc(l.leadNumber)}</td>
          <td class="strong" data-label="Lead Name"><span class="cell-who">${avatar(l.name)}<span class="cell-text">${esc(l.name)}<small>${esc(l.company)}</small></span></span></td>
          <td data-label="Subject"><span class="cell-text">${esc(l.subject)}<small>${esc(deptName(l.department))}</small></span></td>
          <td data-label="Created"><span class="cell-date">${icon('calendar', 'ic sm')}${fmtDate(l.createdAt)}</span></td>
          <td data-label="Assigned Engineer"><span class="cell-who"><span data-av>${avatar(engName(l.engineerId))}</span><span class="select-wrap mini"><select data-lead="${l.id}" aria-label="Engineer for ${esc(l.name)}">${opts(engOpts(l.branchId), l.engineerId)}</select></span></span></td></tr>`).join('')}
        </tbody></table></div>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" data-save disabled>${icon('check')}Save Assignments</button>`
    });
    const saveBtn = $('[data-save]', m.el);
    const sync = () => {
      const n = Object.keys(changes).length;
      saveBtn.disabled = !n;
      saveBtn.innerHTML = `${icon('check')}Save Assignments${n ? ` (${n})` : ''}`;
      $('[data-count]', m.el).textContent = n ? `${n} change${n === 1 ? '' : 's'} pending` : 'No changes yet';
    };
    m.el.addEventListener('change', e => {
      const sel = e.target.closest('select[data-lead]');
      if (!sel) return;
      const l = API.lead(sel.dataset.lead);
      if (sel.value === l.engineerId) delete changes[l.id]; else changes[l.id] = sel.value;
      $('[data-av]', sel.closest('tr')).innerHTML = avatar(engName(sel.value));
      sel.closest('tr').classList.toggle('changed', !!changes[l.id]);
      sync();
    });
    $('[data-q]', m.el).addEventListener('input', e => {
      const q = e.target.value.trim().toLowerCase();
      $$('tbody tr', m.el).forEach(tr => { tr.hidden = !!q && !tr.dataset.text.includes(q); });
    });
    saveBtn.addEventListener('click', () => {
      const ids = Object.keys(changes);
      let done = 0;
      ids.forEach(id => { if (act(() => API.assignEngineer(id, changes[id]))) done++; });
      m.close();
      toast(`${done} lead${done === 1 ? '' : 's'} assigned — engineers notified`);
    });
    sync();
  }

  /* opens the quotation editor for a lead (creating one if needed) */
  function quoteForm(leadId) {
    if (leadId) {
      const l = API.lead(leadId);
      if (l.quote && !['sent', 'awarded'].includes(l.quote.status)) { go(`quote.html?id=${l.quote.id}`); return; }
      go(`quote.html?lead=${leadId}`);
      return;
    }
    const list = state.ctx.leads.filter(l => l.stage === 'new').sort((a, b) => b.createdAt - a.createdAt).slice(0, 100);
    if (!list.length) { toast('No new leads in the current filters. Create a lead first.', 'info'); return; }
    const m = modal({
      title: 'Create Quotation', sub: 'Choose the lead — the quotation editor opens with its details filled in.', size: 'md',
      body: `<form class="form-grid" id="pickLead">${field('Lead', selectCtl('leadId', list.map(l => ({ v: l.id, t: `${l.leadNumber} · ${l.name} — ${l.subject}` })), list[0].id), { full: 1, req: 1 })}</form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="pickLead">${icon('file-plus')}Open Quotation Editor</button>`
    });
    $('#pickLead', m.el).addEventListener('submit', e => { e.preventDefault(); go(`quote.html?lead=${val(e.target, 'leadId')}`); });
  }

  /* awarded quotation → project (PO optional, can be added later) */
  function projectForm(leadId) {
    const awaiting = API.leads.filter(l => l.stage === 'awarded' && !l.projectId && (state.ctx.inScope(l) || l.id === leadId)).sort((a, b) => b.awardDate - a.awardDate);
    if (!awaiting.length) { toast('No awarded quotations are waiting for a project.', 'info'); return; }
    const sel = leadId && awaiting.some(l => l.id === leadId) ? leadId : awaiting[0].id;
    const m = modal({
      title: 'Create Project', sub: 'Details are taken from the awarded quotation. Add the PO now or later.', size: 'lg',
      body: `<form class="form-grid" id="projForm" novalidate>
        ${field('Awarded Quotation', selectCtl('leadId', awaiting.map(l => ({ v: l.id, t: `${l.quote.number} · ${l.name} — ${fmtAED(l.quote.total)}` })), sel), { full: 1, req: 1 })}
        <div class="sum-box" id="projSummary"></div>
        ${field('Project Name', '<input name="name" required maxlength="100">', { req: 1, full: 1 })}
        ${field('RFQ / PR Number', '<input name="rfqNumber" maxlength="40" placeholder="RFQ / PR Number">', { star: 1, hint: 'Taken from the lead — edit if the client issued a new one' })}
        ${field('Work Authorization', selectCtl('auth', [{ v: 'po', t: 'Purchase Order received' }, { v: 'email', t: 'Approved by email — PO to follow' }, { v: 'none', t: 'Not received yet (PO Pending)' }], 'po'), { req: 1 })}
        <div class="info-box" id="authNote" style="grid-column: 1 / -1"></div>
        ${field('PO Number', '<input name="poNumber" maxlength="40" placeholder="Optional — e.g. PO-2026-4471">')}
        ${field('PO Date', `<input name="poDate" type="date" required value="${toISO(TODAY)}">`, { req: 1 })}
        ${field('PO Value (AED)', '<input name="poValue" type="number" required min="1" step="0.01">', { req: 1 })}
        ${field('PO Document', '<input name="poDoc" type="file" accept=".pdf,.jpg,.jpeg,.png">')}
        ${field('Approval Date', `<input name="approvalDate" type="date" required value="${toISO(TODAY)}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Approved By', '<input name="approvedBy" required maxlength="80" placeholder="Client contact / email address">', { req: 1 })}
        ${field('Approval Email', '<input name="approvalDoc" type="file" accept=".pdf,.eml,.msg,.jpg,.jpeg,.png">', { hint: 'Attach the client email that grants the work' })}
        ${field('Project Documents', '<input name="docs" type="file" multiple>')}
        ${field('Project Description', '<textarea name="description" rows="3" maxlength="500" placeholder="Scope, site address, key milestones"></textarea>', { full: 1 })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="projForm">${icon('briefcase')}Create Project</button>`
    });
    const fm = $('#projForm', m.el);
    const fill = () => {
      const l = API.lead(val(fm, 'leadId')), q = l.quote, b = branchOf(l.branchId);
      $('#projSummary', m.el).innerHTML = `<div><span>Customer</span><span>${esc(l.company)}</span></div><div><span>Quotation</span><span>${esc(q.number)}</span></div>
        <div><span>Branch</span><span>${esc(b.name)}</span></div><div><span>Engineer</span><span>${esc(engName(l.engineerId))}</span></div>
        <div><span>Subject</span><span>${esc(l.subject)}</span></div><div class="total"><span>Quotation amount (excl. VAT)</span><span>${fmtAED2(q.amount)}</span></div>`;
      fm.elements.name.value = `${l.name} – ${l.subject}`;
      fm.elements.rfqNumber.value = l.rfqNumber || '';
      fm.elements.poValue.value = Math.round(q.amount);
    };
    fm.elements.leadId.addEventListener('change', fill);
    fill();
    /* PO received → PO fields; email approval → project opens now, PO (and invoicing) later; none → PO Pending */
    const AUTH_GROUPS = { po: ['poNumber', 'poDate', 'poValue', 'poDoc'], email: ['approvalDate', 'approvedBy', 'approvalDoc'] };
    const AUTH_NOTES = {
      po: 'The project opens straight away with the PO recorded.',
      email: 'The project opens so work and expenses can start. Invoices can be raised once the client PO is recorded.',
      none: 'The project stays in PO Pending until the PO is recorded.'
    };
    const syncAuth = () => {
      const a = val(fm, 'auth');
      Object.keys(AUTH_GROUPS).forEach(g => AUTH_GROUPS[g].forEach(n => {
        const inp = fm.elements[n];
        inp.disabled = g !== a;
        inp.closest('.field').hidden = g !== a;
      }));
      $('#authNote', m.el).innerHTML = `${icon('info')}<span>${AUTH_NOTES[a]}</span>`;
    };
    fm.elements.auth.addEventListener('change', syncAuth);
    syncAuth();
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const auth = val(fm, 'auth');
      const d = { name: val(fm, 'name').trim(), rfqNumber: val(fm, 'rfqNumber').trim(), description: val(fm, 'description').trim(), documents: fileNames(fm.elements.docs) };
      if (auth === 'po') {
        const poNumber = val(fm, 'poNumber').trim();
        Object.assign(d, { poNumber: poNumber || null, poDate: parseISO(val(fm, 'poDate')), poValue: +val(fm, 'poValue'), poDocument: fileNames(fm.elements.poDoc)[0] || (poNumber ? `${poNumber}.pdf` : null) });
      } else if (auth === 'email') {
        d.emailApproval = { date: parseISO(val(fm, 'approvalDate')), by: val(fm, 'approvedBy').trim(), document: fileNames(fm.elements.approvalDoc)[0] || null };
      }
      const p = act(() => API.createProject(val(fm, 'leadId'), d));
      if (!p) return;
      m.close();
      flash(`${p.projectNumber} created${API.hasPO(p) ? ' and opened' : p.emailApproval ? ' and opened on email approval — PO awaited' : ' — waiting for PO'}`);
      go(`project.html?id=${p.id}`);
    });
  }

  function poForm(projectId) {
    const p = API.project(projectId);
    const pending = p.status === 'popending';
    const m = modal({
      title: 'Add Purchase Order', sub: `${p.projectNumber} · ${p.name}`, size: 'md',
      body: `<form class="form-grid" id="poForm" novalidate>
        ${field('PO Number', '<input name="poNumber" maxlength="40" placeholder="Optional — e.g. PO-2026-4471">')}
        ${field('PO Date', `<input name="poDate" type="date" required value="${toISO(TODAY)}">`, { req: 1 })}
        ${field('PO Value (AED)', `<input name="poValue" type="number" required min="1" step="0.01" value="${Math.round(p.value)}">`, { req: 1 })}
        ${field('PO Document', '<input name="poDoc" type="file" accept=".pdf,.jpg,.jpeg,.png">')}
        <div class="info-box ok">${icon('check-circle')}<span>${pending
          ? 'Recording the PO changes the status from PO Pending to <b>Open</b> and syncs it to Zoho Books.'
          : 'Work was approved by email. Recording the PO keeps the current status and <b>unlocks invoicing</b> for this project.'}</span></div>
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="poForm">${icon('check')}${pending ? 'Save PO & Open Project' : 'Save PO'}</button>`
    });
    $('#poForm', m.el).addEventListener('submit', e => {
      e.preventDefault();
      const fm = e.target;
      if (!validate(fm)) return;
      const n = val(fm, 'poNumber').trim();
      const lbl = n ? `PO ${n}` : 'PO';
      if (act(() => API.addPO(projectId, { poNumber: n || null, poDate: parseISO(val(fm, 'poDate')), poValue: +val(fm, 'poValue'), poDocument: fileNames(fm.elements.poDoc)[0] || (n ? `${n}.pdf` : null) }), pending ? `${lbl} saved — project is now Open` : `${lbl} saved — invoicing is now available`)) m.close();
    });
  }

  function completionForm(projectId) {
    const p = API.project(projectId);
    const m = modal({
      title: 'Work Completion Report', sub: `${p.projectNumber} · ${p.name}`, size: 'md',
      body: `<form class="form-grid" id="wcForm" novalidate>
        ${field('Completion Date', `<input name="date" type="date" required value="${toISO(TODAY)}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Prepared By', `<input value="${esc(me.name)}" readonly>`)}
        ${field('Completion Notes', '<textarea name="notes" rows="3" required maxlength="500" placeholder="Works completed, snags closed, handover details"></textarea>', { full: 1, req: 1 })}
        ${field('Documents', '<input name="docs" type="file" multiple>', { full: 1, hint: 'Handover certificate, photos, snag list' })}
        <div class="info-box">${icon('info')}<span>The Accountant is notified to raise the final invoice.</span></div>
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="wcForm">${icon('check-circle')}Submit Report</button>`
    });
    $('#wcForm', m.el).addEventListener('submit', e => {
      e.preventDefault();
      const fm = e.target;
      if (!validate(fm)) return;
      if (act(() => API.completeWork(projectId, { date: parseISO(val(fm, 'date')), notes: val(fm, 'notes').trim(), documents: fileNames(fm.elements.docs) }), 'Work completion submitted — Accountant notified')) m.close();
    });
  }

  /* expense with demo OCR: upload → processing → extracted → review → submit */
  function expenseForm(projectId) {
    const scoped = (state.ctx.allProjects.some(p => p.id === projectId) ? state.ctx.allProjects : API.projects.filter(p => p.id === projectId).concat(state.ctx.allProjects))
      .slice().sort((a, b) => (OPEN_STATUSES.includes(b.status) - OPEN_STATUSES.includes(a.status)) || (b.startDate - a.startDate));
    const open = scoped.filter(p => OPEN_STATUSES.includes(p.status));
    const closed = scoped.filter(p => !OPEN_STATUSES.includes(p.status)).slice(0, 8);
    /* opened from a project → project expense types only; vehicle expenses are never booked to a project */
    const types = projectId ? API.categories.filter(c => !API.isVehicleCategory(c)) : API.categories;
    if (!open.length && projectId) { toast('Expense entry is available after the project is officially opened.', 'error'); return; }
    const sel = projectId && open.some(p => p.id === projectId) ? projectId : open.length ? open[0].id : '';
    const projOpts = (open.length ? [] : [{ v: '', t: 'No open projects' }]).concat(open.map(p => ({ v: p.id, t: `${p.projectNumber} · ${p.name}` })))
      .concat(closed.map(p => ({ v: p.id, t: `${p.projectNumber} · ${p.name} (${statusMeta(p.status).name} — not open)`, disabled: true })));
    const vehOpts = () => [{ v: '', t: 'Select vehicle' }].concat(API.vehicles.map(v => ({ v, t: v })), [{ v: '__new', t: '+ Add new vehicle…' }]);
    const m = modal({
      title: 'Add Expense', sub: 'Project expenses need an officially opened project. Fuel and vehicle maintenance are booked to a vehicle.', size: 'lg',
      body: `<form class="form-grid" id="expForm" novalidate>
        ${field('Expense Type', selectCtl('category', types.map(c => ({ v: c, t: c })), 'Materials'))}
        ${field('Vehicle', selectCtl('vehicle', vehOpts(), '', 'required'), { req: 1 })}
        ${field('New Vehicle Number', `<span class="receipt-row"><input name="newVehicle" maxlength="20" placeholder="e.g. DXB A 12345"><button type="button" class="btn btn-soft btn-sm" id="addVehBtn">${icon('plus', 'ic sm')}Add</button></span>`, { div: 1 })}
        ${field('Engineer', selectCtl('engineerId', engOpts(), myEngineer() || (open[0] || {}).engineerId || API.engineers[0].id, myEngineer() ? 'disabled' : ''), { req: 1 })}
        ${field('Project', selectCtl('projectId', projOpts, sel, 'required'), { full: 1, req: 1 })}
        ${field('Payment Method', `<span class="seg"><label><input type="radio" name="type" value="cash" checked><span>${icon('wallet', 'ic sm')}Cash</span></label><label><input type="radio" name="type" value="card"><span>${icon('credit-card', 'ic sm')}Company Card</span></label></span>`, { div: 1 })}
        ${field('Card', selectCtl('card', [{ v: '', t: 'Select card' }].concat(API.companyCards.map(c => ({ v: c, t: c }))), '', 'required'), { req: 1 })}
        ${field('Receipt', `<span class="receipt-row"><input name="receipt" type="file" accept="image/*,.pdf"><button type="button" class="btn btn-soft btn-sm" id="ocrBtn">${icon('scan', 'ic sm')}Scan Receipt</button></span>`, { full: 1, div: 1, hint: 'Demo OCR — the extraction is simulated in this prototype; no OCR service is connected.' })}
        <div class="ocr-steps full" id="ocrSteps" hidden><span data-s="1">Upload</span><span data-s="2">Processing</span><span data-s="3">Extracted</span><span data-s="4">Review</span></div>
        <div class="info-box full" id="ocrNote" hidden></div>
        ${field('Vendor', `<input name="vendor" required maxlength="80" list="vendorList" autocomplete="off" placeholder="e.g. Danube Building Materials"><datalist id="vendorList">${API.vendors.map(v => `<option value="${esc(v)}">`).join('')}</datalist>`, { req: 1 })}
        ${field('Amount (AED)', '<input name="amount" type="number" min="0.01" step="0.01" required placeholder="0.00">', { req: 1 })}
        ${field('Date', `<input name="date" type="date" required value="${toISO(TODAY)}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Receipt Reference', '<input name="refNo" maxlength="40" placeholder="Invoice / receipt no.">')}
        ${field('Description', '<textarea name="description" rows="2" maxlength="300" placeholder="What was purchased and why"></textarea>', { full: 1 })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="expForm">${icon('send')}Submit for Approval</button>`
    });
    const fm = $('#expForm', m.el);
    const steps = $('#ocrSteps', m.el);
    const setStep = n => { steps.hidden = false; $$('[data-s]', steps).forEach(s => { s.classList.toggle('done', +s.dataset.s < n); s.classList.toggle('current', +s.dataset.s === n); }); };
    $('#ocrBtn', m.el).addEventListener('click', e => {
      const b = e.currentTarget;
      const file = fileNames(fm.elements.receipt)[0];
      setStep(1);
      b.disabled = true;
      b.innerHTML = '<span class="spin"></span>Processing…';
      setTimeout(() => setStep(2), 300);
      setTimeout(() => {
        fm.elements.vendor.value = 'ABC Trading';
        fm.elements.amount.value = '250.00';
        fm.elements.date.value = toISO(TODAY);
        fm.elements.refNo.value = 'INV-ABC-' + (40000 + Math.floor(Math.random() * 9999));
        fm.elements.description.value = 'Site consumables (extracted from receipt)';
        ['vendor', 'amount', 'date', 'refNo'].forEach(n => fm.elements[n].classList.add('ocr-filled'));
        setStep(4);
        const note = $('#ocrNote', m.el);
        note.hidden = false;
        note.innerHTML = `${icon('scan')}<span>Extracted from ${esc(file || 'the receipt')}: vendor, amount (incl. VAT AED 11.90), date and reference. <b>Review and correct any field before submitting.</b></span>`;
        b.disabled = false;
        b.innerHTML = icon('refresh', 'ic sm') + 'Re-scan';
      }, 1400);
    });
    fm.addEventListener('input', e => e.target.classList && e.target.classList.remove('ocr-filled'));
    /* the card dropdown only shows (and is only required) when paying by company card */
    const syncCard = () => {
      const isCard = fm.querySelector('input[name=type]:checked').value === 'card';
      fm.elements.card.disabled = !isCard;
      fm.elements.card.closest('.field').hidden = !isCard;
    };
    $$('input[name=type]', fm).forEach(r => r.addEventListener('change', () => { syncCard(); if (!fm.elements.card.disabled) fm.elements.card.focus(); }));
    syncCard();
    /* fuel / vehicle maintenance → vehicle + engineer instead of a project */
    const show = (name, on, keepDisabled) => { fm.elements[name].disabled = !on || !!keepDisabled; fm.elements[name].closest('.field').hidden = !on; };
    const syncVehicle = () => {
      const veh = API.isVehicleCategory(val(fm, 'category'));
      show('vehicle', veh);
      show('newVehicle', veh && fm.elements.vehicle.value === '__new');
      show('engineerId', veh, myEngineer());
      show('projectId', !veh);
    };
    fm.elements.category.addEventListener('change', syncVehicle);
    fm.elements.vehicle.addEventListener('change', () => { syncVehicle(); if (fm.elements.vehicle.value === '__new') fm.elements.newVehicle.focus(); });
    const addVehicle = () => {
      const n = act(() => API.addVehicle(fm.elements.newVehicle.value), r => `Vehicle ${r} added`);
      if (!n) return;
      fm.elements.vehicle.innerHTML = opts(vehOpts(), n);
      fm.elements.newVehicle.value = '';
      syncVehicle();
    };
    $('#addVehBtn', m.el).addEventListener('click', addVehicle);
    fm.elements.newVehicle.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addVehicle(); } });
    syncVehicle();
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!fm.elements.vehicle.disabled && fm.elements.vehicle.value === '__new') { toast('Add the new vehicle number first, or pick an existing vehicle.', 'error'); return; }
      if (!validate(fm)) return;
      const type = fm.querySelector('input[name=type]:checked').value;
      const veh = API.isVehicleCategory(val(fm, 'category'));
      const ex = act(() => API.addExpense(Object.assign({
        projectId: veh ? null : val(fm, 'projectId'), type, amount: Math.round(+val(fm, 'amount') * 100) / 100,
        card: type === 'card' ? val(fm, 'card') : null, vendor: val(fm, 'vendor').trim(), category: val(fm, 'category'),
        description: val(fm, 'description').trim(), date: parseISO(val(fm, 'date')), refNo: val(fm, 'refNo').trim(),
        receipt: fileNames(fm.elements.receipt)[0] || null
      }, veh ? { vehicle: val(fm, 'vehicle'), engineerId: myEngineer() || val(fm, 'engineerId') } : {})), r => `${r.ref} submitted · waiting for accountant approval`);
      if (ex) m.close();
    });
  }

  function transferForm() {
    const me = myEngineer();
    const engs = state.ctx.engineers.length ? state.ctx.engineers : API.engineers;
    const eo = engs.map(e => ({ v: e.id, t: `${e.name} · cash ${fmtAED(API.cashBalance(e.id))}` }));
    const m = modal({
      title: 'New Internal Transfer', sub: 'Transfers need Accountant approval. After submission they are locked for everyone except the Accountant.', size: 'md',
      body: `<form class="form-grid" id="trForm" novalidate>
        ${field('Transfer Type', selectCtl('kind', [{ v: 'request', t: 'Petty cash top-up (Main → Engineer)' }, { v: 'return', t: 'Return cash to office (Engineer → Main)' }, { v: 'e2e', t: 'Engineer → Engineer' }], 'request'), { full: 1 })}
        ${field('Engineer', selectCtl('engineer', eo, me || eo[0].v, me ? 'disabled' : ''), { req: 1 })}
        <label class="field" id="toWrap" hidden><span class="field-label">Recipient Engineer</span>${selectCtl('toEngineer', API.engineers.map(e => ({ v: e.id, t: e.name })), '')}</label>
        ${field('Amount (AED)', '<input name="amount" type="number" min="1" step="1" required>', { req: 1 })}
        ${field('Date', `<input name="date" type="date" required value="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Note', '<textarea name="note" rows="2" maxlength="200" required placeholder="Purpose of the transfer"></textarea>', { full: 1, req: 1 })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="trForm">${icon('send')}Submit Transfer</button>`
    });
    const fm = $('#trForm', m.el);
    const sync = () => { $('#toWrap', m.el).hidden = val(fm, 'kind') !== 'e2e'; };
    fm.elements.kind.addEventListener('change', sync);
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const kind = val(fm, 'kind'), eng = me || val(fm, 'engineer');
      const d = { amount: +val(fm, 'amount'), date: parseISO(val(fm, 'date')), note: val(fm, 'note').trim() };
      if (kind === 'request') Object.assign(d, { fromType: 'main', toType: 'engineer', toEngineerId: eng });
      else if (kind === 'return') Object.assign(d, { fromType: 'engineer', fromEngineerId: eng, toType: 'main' });
      else Object.assign(d, { fromType: 'engineer', fromEngineerId: eng, toType: 'engineer', toEngineerId: val(fm, 'toEngineer') });
      if (act(() => API.createTransfer(d), r => `${r.ref} submitted · waiting for accountant approval`)) m.close();
    });
  }
  function editTransferForm(id) {
    const t = API.transfer(id);
    if (!can('transfers.edit')) { toast('Submitted transfers are locked. Only the Accountant can edit them.', 'error'); return; }
    if (t.status !== 'pending') { toast('Only pending transfers can be edited.', 'error'); return; }
    const kind = t.fromType === 'main' ? 'request' : t.toType === 'main' ? 'return' : 'e2e';
    const eo = API.engineers.map(e => ({ v: e.id, t: `${e.name} · cash ${fmtAED(API.cashBalance(e.id))}` }));
    const m = modal({
      title: `Edit ${t.ref}`, sub: `${transferRoute(t)} · requested by ${t.createdBy} · Accountant edit`, size: 'md',
      body: `<form class="form-grid" id="etForm" novalidate>
        ${field('Transfer Type', selectCtl('kind', [{ v: 'request', t: 'Petty cash top-up (Main → Engineer)' }, { v: 'return', t: 'Return cash to office (Engineer → Main)' }, { v: 'e2e', t: 'Engineer → Engineer' }], kind), { full: 1 })}
        ${field('Engineer', selectCtl('engineer', eo, t.fromEngineerId || t.toEngineerId), { req: 1 })}
        <label class="field" id="etToWrap"><span class="field-label">Recipient Engineer</span>${selectCtl('toEngineer', API.engineers.map(e => ({ v: e.id, t: e.name })), kind === 'e2e' ? t.toEngineerId : '')}</label>
        ${field('Amount (AED)', `<input name="amount" type="number" min="1" step="1" required value="${t.amount}">`, { req: 1 })}
        ${field('Date', `<input name="date" type="date" required value="${toISO(t.date)}">`, { req: 1 })}
        ${field('Note', `<textarea name="note" rows="2" maxlength="200" required>${esc(t.note)}</textarea>`, { full: 1, req: 1 })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="etForm">${icon('check')}Save Changes</button>`
    });
    const fm = $('#etForm', m.el);
    const sync = () => { $('#etToWrap', m.el).hidden = val(fm, 'kind') !== 'e2e'; };
    fm.elements.kind.addEventListener('change', sync);
    sync();
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const k = val(fm, 'kind'), eng = val(fm, 'engineer');
      const d = { amount: +val(fm, 'amount'), date: parseISO(val(fm, 'date')), note: val(fm, 'note').trim() };
      if (k === 'request') Object.assign(d, { fromType: 'main', toType: 'engineer', toEngineerId: eng });
      else if (k === 'return') Object.assign(d, { fromType: 'engineer', fromEngineerId: eng, toType: 'main' });
      else Object.assign(d, { fromType: 'engineer', fromEngineerId: eng, toType: 'engineer', toEngineerId: val(fm, 'toEngineer') });
      if (act(() => API.updateTransfer(id, d), `${t.ref} updated`)) m.close();
    });
  }

  function paymentForm() {
    const open = state.ctx.allProjects.filter(p => p.status !== 'popending' && p.status !== 'closed');
    if (!open.length) { toast('No active projects in the current filters.', 'info'); return; }
    const m = modal({
      title: 'Record Customer Payment', sub: 'Cash collected by an engineer on site. It is added to their cash in hand and shown to the Accountant.', size: 'md',
      body: `<form class="form-grid" id="payForm" novalidate>
        ${field('Project', selectCtl('projectId', open.map(p => ({ v: p.id, t: `${p.projectNumber} · ${p.name}` })), open[0].id), { full: 1, req: 1 })}
        ${field('Received By', selectCtl('engineerId', engOpts(), myEngineer() || open[0].engineerId, myEngineer() ? 'disabled' : ''))}
        ${field('Amount (AED)', '<input name="amount" type="number" min="1" step="1" required>', { req: 1 })}
        ${field('Date', `<input name="date" type="date" required value="${toISO(TODAY)}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Note', '<input name="note" maxlength="120" placeholder="Receipt no. / purpose">')}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="payForm">${icon('check')}Record Payment</button>`
    });
    const fm = $('#payForm', m.el);
    fm.elements.projectId.addEventListener('change', () => { if (!myEngineer()) fm.elements.engineerId.value = API.project(val(fm, 'projectId')).engineerId; });
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      if (act(() => API.recordPayment({ projectId: val(fm, 'projectId'), engineerId: myEngineer() || val(fm, 'engineerId'), amount: +val(fm, 'amount'), date: parseISO(val(fm, 'date')), note: val(fm, 'note').trim() }), r => `${r.ref} recorded — cash in hand updated`)) m.close();
    });
  }

  function cashLedgerModal(engineerId) {
    const e = API.engineer(engineerId);
    const rows = API.cashLedger(engineerId).slice().reverse();
    const inflow = sum(rows.filter(r => r.amount > 0), r => r.amount), outflow = -sum(rows.filter(r => r.amount < 0), r => r.amount);
    tableModal({
      title: `Cash in Hand · ${e.name}`, sub: `${e.pettyCash} · current balance ${fmtAED2(API.cashBalance(engineerId))} (cash in ${fmtAED(inflow)} − cash out ${fmtAED(outflow)})`,
      columns: [
        { label: 'Date', get: r => fmtDate(r.date) }, { label: 'Ref', get: r => r.ref, cls: 'strong' }, { label: 'Type', get: r => r.kind },
        { label: 'Description', get: r => r.desc },
        { label: 'In', get: r => r.amount > 0 ? r.amount : '', render: r => r.amount > 0 ? `<span style="color:var(--green)">+${fmtAED2(r.amount)}</span>` : '—', cls: 'num' },
        { label: 'Out', get: r => r.amount < 0 ? -r.amount : '', render: r => r.amount < 0 ? `<span style="color:var(--red)">−${fmtAED2(-r.amount)}</span>` : '—', cls: 'num' },
        { label: 'Running Balance', get: r => r.balance, render: r => `<b>${fmtAED2(r.balance)}</b>`, cls: 'num' }
      ],
      rows
    });
  }

  /* progress invoice (in progress) or final invoice (work completed) */
  function invoiceForm(projectId) {
    const eligible = API.projects.filter(p => (state.ctx.inScope(p) || p.id === projectId) && API.hasPO(p) && (p.status === 'completed' || p.status === 'inprogress'));
    if (!eligible.length) { toast('No projects are ready for invoicing. Invoices need the client PO, and final invoices need the work to be completed.', 'info'); return; }
    const sel = projectId && eligible.some(p => p.id === projectId) ? projectId : (eligible.find(p => p.status === 'completed') || eligible[0]).id;
    const invoicedOf = id => sum(API.invoices.filter(i => i.projectId === id), i => i.amount);
    const m = modal({
      title: 'Create Invoice', sub: 'Customer, project, quotation and amount are filled in from the project.', size: 'lg',
      body: `<form class="form-grid" id="invForm" novalidate>
        ${field('Project', selectCtl('projectId', eligible.map(p => ({ v: p.id, t: `${p.projectNumber} · ${p.name} (${statusMeta(p.status).name})` })), sel), { full: 1, req: 1 })}
        <div class="sum-box" id="invSum"></div>
        ${field('Invoice Type', selectCtl('kind', [{ v: 'final', t: 'Final invoice (closes the project when submitted)' }, { v: 'progress', t: 'Progress invoice' }], 'final'), { full: 1 })}
        ${field('Amount (AED, excl. VAT)', '<input name="amount" type="number" min="1" step="1" required>', { req: 1 })}
        ${field('VAT', selectCtl('taxRate', [{ v: .05, t: 'VAT 5%' }, { v: 0, t: 'Zero-rated (0%)' }], .05))}
        ${field('Invoice Date', `<input name="date" type="date" required value="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Notes', '<input name="notes" maxlength="150" placeholder="Shown on the invoice">')}
        <div class="sum-box" id="invTotals"></div>
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="invForm">${icon('invoice')}Create Invoice</button>`
    });
    const fm = $('#invForm', m.el);
    const totals = () => {
      const a = +val(fm, 'amount') || 0, r = +val(fm, 'taxRate');
      $('#invTotals', m.el).innerHTML = `<div><span>Amount</span><span>${fmtAED2(a)}</span></div><div><span>VAT ${(r * 100).toFixed(0)}%</span><span>${fmtAED2(a * r)}</span></div><div class="total"><span>Invoice total</span><span>${fmtAED2(a * (1 + r))}</span></div>`;
    };
    const upd = () => {
      const p = API.project(val(fm, 'projectId')), l = API.lead(p.leadId), done = invoicedOf(p.id), rem = Math.max(0, p.value - done);
      $('#invSum', m.el).innerHTML = `<div><span>Customer</span><span>${esc(l.company)}</span></div><div><span>Quotation</span><span>${esc(p.quoteNumber)}</span></div>
        <div><span>Project value</span><span>${fmtAED(p.value)}</span></div><div><span>Invoiced so far</span><span>${fmtAED(done)}</span></div><div class="total"><span>Remaining to invoice</span><span>${fmtAED(rem)}</span></div>`;
      const kind = fm.elements.kind;
      kind.options[0].disabled = p.status !== 'completed';
      kind.value = p.status === 'completed' ? 'final' : 'progress';
      fm.elements.amount.value = p.status === 'completed' ? Math.round(rem) : Math.round(rem * .3);
      totals();
    };
    fm.elements.projectId.addEventListener('change', upd);
    fm.addEventListener('input', totals);
    fm.addEventListener('change', totals);
    upd();
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!validate(fm)) return;
      const inv = act(() => API.createInvoice({ projectId: val(fm, 'projectId'), amount: +val(fm, 'amount'), taxRate: +val(fm, 'taxRate'), date: parseISO(val(fm, 'date')), final: val(fm, 'kind') === 'final', notes: val(fm, 'notes').trim() }),
        r => r.final ? `${r.number} created — project is now Invoice Pending` : `${r.number} submitted`);
      if (!inv) return;
      m.close();
      invoicePreview(inv.id);
    });
  }

  function invoiceDocHTML(inv) {
    const p = API.project(inv.projectId), l = API.lead(p.leadId), b = branchOf(p.branchId);
    return `<div class="doc">
      <div class="doc-head"><div><img src="images/rich-mount-logo.png" alt="" width="54"><strong>RICH MOUNT CONTRACTING LLC</strong><small>${esc(b.name)} · TRN 100 234 567 800 003</small></div>
        <div class="doc-title"><h2>${inv.final ? 'TAX INVOICE — FINAL' : 'TAX INVOICE — PROGRESS'}</h2><small>${esc(inv.number)} · ${fmtDate(inv.date)}</small></div></div>
      <div class="doc-grid"><div><small>Bill to</small><b>${esc(l.company)}</b><span>${esc(l.contact || '')}</span></div>
        <div><small>Project</small><b>${esc(p.projectNumber)}</b><span>${esc(p.name)}</span></div>
        <div><small>Reference</small><b>${esc(p.quoteNumber)}</b><span>PO ${esc(p.poNumber || '—')}</span></div></div>
      <table class="doc-table"><thead><tr><th>Description</th><th class="num">Amount</th></tr></thead>
        <tbody><tr><td>${inv.final ? 'Final account — balance of contract value' : 'Progress claim against works completed'}${inv.notes ? `<br><small>${esc(inv.notes)}</small>` : ''}</td><td class="num">${fmtAED2(inv.amount)}</td></tr></tbody></table>
      <div class="doc-totals"><div><span>Subtotal</span><span>${fmtAED2(inv.amount)}</span></div><div><span>VAT ${(inv.taxRate * 100).toFixed(0)}%</span><span>${fmtAED2(inv.tax)}</span></div><div class="grand"><span>Total due</span><span>${fmtAED2(inv.total)}</span></div></div>
      <p class="doc-foot">Status: ${inv.status === 'draft' ? 'Draft — not yet submitted' : 'Submitted'} · Generated by Rich Mount ERP (demo)</p></div>`;
  }
  function invoicePreview(id) {
    const inv = API.invoice(id);
    const canSubmit = inv.status === 'draft' && can('invoices.submit');
    const m = modal({
      title: `Invoice ${inv.number}`, sub: inv.final ? 'Final invoice — submitting it closes the project' : 'Progress invoice', size: 'lg', noFocus: true,
      body: invoiceDocHTML(inv),
      foot: `<button class="btn btn-ghost" data-print>${icon('printer')}Print</button><button class="btn btn-ghost" data-dl>${icon('download')}Download</button><button class="btn btn-ghost" data-close>Close</button>${canSubmit ? `<button class="btn btn-primary" data-submit>${icon('send')}Submit Invoice${inv.final ? ' & Close Project' : ''}</button>` : ''}`
    });
    const html = () => `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"><style>${DOC_CSS}</style>${invoiceDocHTML(API.invoice(id))}`;
    $('[data-print]', m.el).addEventListener('click', () => printHTML(inv.number, html(), DOC_CSS));
    $('[data-dl]', m.el).addEventListener('click', () => { downloadFile(`${inv.number}.html`, `<!doctype html><meta charset="utf-8"><title>${inv.number}</title>${html()}`, 'text/html'); toast(`${inv.number} downloaded`); });
    const sb = $('[data-submit]', m.el);
    if (sb) sb.addEventListener('click', () => confirmDialog({ title: 'Submit invoice?', text: inv.final ? `Submitting <b>${esc(inv.number)}</b> (${fmtAED2(inv.total)}) will change the project status to <b>Closed</b> and sync to Zoho Books.` : `Submit <b>${esc(inv.number)}</b>?`, ok: inv.final ? 'Submit & Close Project' : 'Submit' })
      .then(ok => { if (ok && act(() => API.submitInvoice(id), inv.final ? `${inv.number} submitted — project closed` : `${inv.number} submitted`)) m.close(); }));
  }
  const DOC_CSS = `.doc{font:13px Inter,Arial,sans-serif;color:#121418;background:#fff}.doc-head{display:flex;justify-content:space-between;gap:16px;border-bottom:3px solid #941C3C;padding-bottom:12px;margin-bottom:14px}
    .doc-head strong{display:block;font-size:15px;margin-top:6px}.doc-head small{color:#666}.doc-title{text-align:right}.doc-title h2{margin:0;color:#941C3C;font-size:17px}
    .doc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px}.doc-grid small{display:block;color:#888;font-size:11px;text-transform:uppercase}.doc-grid b,.doc-grid span{display:block}
    .doc-table{width:100%;border-collapse:collapse;margin-bottom:12px}.doc-table th{background:#F4F5F7;text-align:left;padding:8px;border-bottom:1px solid #ddd;font-size:12px}.doc-table td{padding:8px;border-bottom:1px solid #eee;vertical-align:top}.doc-table .num{text-align:right;white-space:nowrap}
    .doc-totals{margin-left:auto;max-width:300px}.doc-totals div{display:flex;justify-content:space-between;padding:4px 0}.doc-totals .grand{border-top:2px solid #941C3C;font-weight:700;font-size:15px;margin-top:4px;padding-top:8px}
    .doc-foot{color:#888;font-size:11px;margin-top:18px}.doc-sec h4{margin:14px 0 4px;font-size:12px;text-transform:uppercase;color:#941C3C}.doc-sec p{white-space:pre-line;margin:0;color:#333}`;

  /* expense / transfer approvals (shared by Finance page and drawers) */
  /* closest Zoho Books vendor to the name the engineer typed ('' when nothing is close) */
  function matchVendor(name) {
    const norm = s => String(s || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    const n = norm(name);
    if (!n) return '';
    return API.vendors.find(v => norm(v) === n)
      || API.vendors.find(v => norm(v).includes(n) || n.includes(norm(v)))
      || API.vendors.find(v => norm(v.split(' ')[0]).length > 2 && n.startsWith(norm(v.split(' ')[0])))
      || '';
  }
  /* accountant edits a pending expense before approving it */
  function editExpenseForm(id) {
    const e = API.expense(id);
    if (!can('expenses.approve')) { toast('Submitted expenses are locked. Only the Accountant can edit them.', 'error'); return; }
    if (e.status !== 'pending') { toast('Only pending expenses can be edited.', 'error'); return; }
    const veh = API.isVehicleCategory(e.category);
    const types = API.categories.filter(c => API.isVehicleCategory(c) === veh);
    const m = modal({
      title: `Edit ${e.ref}`, sub: `${e.projectId ? projLabel(e.projectId) : `Vehicle · ${e.vehicle}`} · ${engName(e.engineerId)}`, size: 'lg',
      body: `<form class="form-grid" id="eeForm" novalidate>
        ${field('Expense Type', selectCtl('category', types.map(c => ({ v: c, t: c })), e.category))}
        ${veh ? field('Vehicle', selectCtl('vehicle', API.vehicles.map(v => ({ v, t: v })), e.vehicle, 'required'), { req: 1 }) : ''}
        ${field('Payment Method', selectCtl('type', [{ v: 'cash', t: 'Cash' }, { v: 'card', t: 'Company Card' }], e.type))}
        ${field('Card', selectCtl('card', [{ v: '', t: 'Select card' }].concat(API.companyCards.map(c => ({ v: c, t: c }))), e.card || '', 'required'), { req: 1 })}
        ${field('Vendor', `<input name="vendor" required maxlength="80" list="eeVendors" autocomplete="off" value="${esc(e.vendor)}"><datalist id="eeVendors">${API.vendors.map(v => `<option value="${esc(v)}">`).join('')}</datalist>`, { req: 1 })}
        ${field('Amount (AED)', `<input name="amount" type="number" min="0.01" step="0.01" required value="${e.amount}">`, { req: 1 })}
        ${field('Date', `<input name="date" type="date" required value="${toISO(e.date)}" max="${toISO(TODAY)}">`, { req: 1 })}
        ${field('Receipt Reference', `<input name="refNo" maxlength="40" value="${esc(e.refNo || '')}">`)}
        ${field('Description', `<textarea name="description" rows="2" maxlength="300">${esc(e.description || '')}</textarea>`, { full: 1 })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="eeForm">${icon('check')}Save Changes</button>`
    });
    const fm = $('#eeForm', m.el);
    const syncCard = () => { const on = val(fm, 'type') === 'card'; fm.elements.card.disabled = !on; fm.elements.card.closest('.field').hidden = !on; };
    fm.elements.type.addEventListener('change', syncCard);
    syncCard();
    fm.addEventListener('submit', ev => {
      ev.preventDefault();
      if (!validate(fm)) return;
      const d = {
        category: val(fm, 'category'), type: val(fm, 'type'), card: val(fm, 'card'), vendor: val(fm, 'vendor').trim(),
        amount: +val(fm, 'amount'), date: parseISO(val(fm, 'date')), refNo: val(fm, 'refNo').trim(), description: val(fm, 'description').trim()
      };
      if (veh) d.vehicle = val(fm, 'vehicle');
      if (act(() => API.updateExpense(id, d), `${e.ref} updated`)) m.close();
    });
  }
  /* the accountant confirms which Zoho Books vendor the expense is posted to */
  function approveExpense(e) {
    return new Promise(resolve => {
      let done = false;
      const match = matchVendor(e.vendor);
      const vendorOpts = [{ v: '', t: 'Select vendor' }].concat(API.vendors.slice().sort().map(v => ({ v, t: v })), [{ v: '__new', t: '+ New vendor in Zoho Books…' }]);
      const m = modal({
        title: `Approve ${e.ref}?`, sub: `${fmtAED2(e.amount)} · ${engName(e.engineerId)} — the cleared transaction is posted to Zoho Books.`, size: 'md',
        body: `<form class="form-grid" id="apprForm" novalidate>
          <div class="sum-box"><div><span>Vendor as entered</span><span>${esc(e.vendor || '—')}</span></div><div><span>Expense type</span><span>${esc(e.category)}</span></div>
            <div><span>Reference</span><span>${esc(e.refNo || '—')}</span></div><div class="total"><span>Amount</span><span>${fmtAED2(e.amount)}</span></div></div>
          ${field('Zoho Books Vendor', selectCtl('vendor', vendorOpts, match, 'required'), { full: 1, req: 1, hint: match ? (match === e.vendor ? 'Matches the vendor entered' : `Suggested from "${esc(e.vendor)}" — check before approving`) : 'No matching vendor found — select the correct one or create it' })}
          ${field('New Vendor Name', `<input name="newVendor" maxlength="80" value="${esc(match ? '' : e.vendor || '')}" placeholder="Vendor name as in Zoho Books">`, { full: 1, req: 1 })}
        </form>`,
        foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-success" type="submit" form="apprForm">${icon('check')}Approve</button>`,
        onClose: () => { if (!done) resolve(null); }
      });
      const fm = $('#apprForm', m.el);
      const sync = () => {
        const isNew = fm.elements.vendor.value === '__new';
        fm.elements.newVendor.disabled = !isNew;
        fm.elements.newVendor.required = isNew;
        fm.elements.newVendor.closest('.field').hidden = !isNew;
      };
      fm.elements.vendor.addEventListener('change', () => { sync(); if (!fm.elements.newVendor.disabled) fm.elements.newVendor.focus(); });
      sync();
      fm.addEventListener('submit', ev => {
        ev.preventDefault();
        if (!validate(fm)) return;
        const vendor = fm.elements.vendor.value === '__new' ? val(fm, 'newVendor').trim() : val(fm, 'vendor');
        const r = act(() => API.approveExpense(e.id, { vendor }), `${e.ref} approved — posting to Zoho Books`);
        if (!r) return;
        done = true;
        m.close();
        resolve(r);
      });
    });
  }
  function rejectExpense(e) {
    return promptDialog({ title: `Reject ${e.ref}?`, sub: `${fmtAED2(e.amount)} · ${e.vendor}`, label: 'Reason for rejection', placeholder: 'e.g. Receipt missing or not legible', ok: 'Reject Expense', danger: true })
      .then(r => r && act(() => API.rejectExpense(e.id, r), `${e.ref} rejected`, 'info'));
  }
  function approveTransfer(t) {
    return confirmDialog({ title: `Approve ${t.ref}?`, text: `${esc(transferRoute(t))} · <b>${fmtAED(t.amount)}</b><br>Cash in hand is updated once approved.`, ok: 'Approve Transfer' })
      .then(ok => ok && act(() => API.approveTransfer(t.id), `${t.ref} approved — cash in hand updated`));
  }
  function rejectTransfer(t) {
    return promptDialog({ title: `Reject ${t.ref}?`, sub: transferRoute(t), label: 'Reason for rejection', ok: 'Reject Transfer', danger: true })
      .then(r => r && act(() => API.rejectTransfer(t.id, r), `${t.ref} rejected`, 'info'));
  }

  /* =======================================================
     Lead workflow + quick-view drawers
     ======================================================= */
  function nextStep(l) {
    const q = l.quote;
    if (l.stage === 'new') return can('quotes.create') && { label: 'Create Quotation', icon: 'file-plus', run: () => quoteForm(l.id) };
    if (q && DRAFTISH.includes(q.status)) return { label: q.status === 'pending' && can('quotes.approve') ? 'Review & Approve' : q.status === 'approved' ? 'Open & Send' : 'Open Quotation', icon: 'file-text', run: () => go(`quote.html?id=${q.id}`) };
    if (q && q.status === 'sent') return can('quotes.award') ? { label: 'Award Quotation', icon: 'award', run: () => awardQuote(q) } : { label: 'Open Quotation', icon: 'file-text', run: () => go(`quote.html?id=${q.id}`) };
    if (l.stage === 'awarded' && !l.projectId) return can('projects.create') && { label: 'Create Project', icon: 'briefcase', run: () => projectForm(l.id) };
    if (l.projectId) return { label: 'Open Project', icon: 'briefcase', run: () => go(`project.html?id=${l.projectId}`) };
    return null;
  }
  function awardQuote(q) {
    return confirmDialog({ title: 'Mark quotation as awarded?', text: `Are you sure you want to mark Quote <b>#${esc(q.number)}</b> (${fmtAED2(q.total)}) as Awarded?<br>Project creation becomes available and the Accountant is notified.`, ok: 'Mark as Awarded' })
      .then(ok => ok && act(() => API.awardQuote(q.id), `${q.number} awarded`));
  }
  function deleteLead(l, after) {
    if (l.quoteId) { toast('Leads with quotations cannot be deleted.', 'error'); return; }
    confirmDialog({ title: `Delete ${l.leadNumber}?`, text: `${esc(l.name)} — ${esc(l.subject)} will be permanently removed.`, ok: 'Delete Lead', danger: true })
      .then(ok => { if (ok && act(() => API.deleteLead(l.id), `${l.leadNumber} deleted`, 'info') && after) after(); });
  }

  function leadMenu(anchor, id) {
    const l = API.lead(id), ns = nextStep(l);
    menu(anchor, [
      { label: 'Open lead', icon: 'eye', run: () => go(`lead.html?id=${id}`) },
      { label: 'Quick view', icon: 'file-user', run: () => leadDrawer(id) },
      { label: 'Edit lead', icon: 'edit', disabled: !can('leads.edit'), run: () => leadForm(l) },
      { label: 'Assign engineer', icon: 'user', disabled: !can('leads.assign'), run: () => assignForm(id) },
      ns ? { divider: true } : null,
      ns ? { label: ns.label, icon: ns.icon, run: ns.run } : null,
      can('leads.delete') && !l.quoteId ? { divider: true } : null,
      can('leads.delete') && !l.quoteId ? { label: 'Delete lead', icon: 'x', danger: true, run: () => deleteLead(l) } : null
    ], { align: 'right', width: 230 });
  }

  function leadDrawer(id) {
    openDrawer(() => {
      const l = API.lead(id);
      if (!l) return null;
      const b = branchOf(l.branchId), q = l.quote, idx = API.stages.findIndex(s => s.id === l.stage);
      const kv = [['Lead No', l.leadNumber], ['Company', l.company], ['Contact', [l.contact, l.phone].filter(Boolean).join(' · ') || '—'], ['Project Subject', l.subject],
        ['Branch', `${b.name} · ${b.quoteFormat}`], ['Assigned Engineer', engName(l.engineerId)], ['Estimated Value', l.estValue ? fmtAED(l.estValue) : '—'], ['Created', fmtDate(l.createdAt)]];
      if (q) kv.push(['Quotation', `${q.number} · ${quoteStatus(q.status).name}`], ['Quote Total', fmtAED2(q.total)]);
      if (l.projectId) { const p = API.project(l.projectId); kv.push(['Project', `${p.projectNumber} · ${statusMeta(p.status).name}`]); }
      const ns = nextStep(l);
      return {
        title: 'Lead Details',
        body: `<div class="dr-hero"><span class="avatar lg">${initials(l.name)}</span><div><h4>${esc(l.name)}</h4><p>${esc(l.subject)}</p></div>${leadPill(l)}</div>
          <ol class="stepper">${API.stages.map((s, i) => `<li class="${i < idx ? 'done' : i === idx ? 'current' : ''}"><span>${i < idx ? icon('check', 'ic xs') : i + 1}</span>${s.short}</li>`).join('')}</ol>
          <dl class="kv">${kv.map(r => `<dt>${esc(r[0])}</dt><dd>${esc(r[1])}</dd>`).join('')}</dl>`,
        actions: [
          ns && Object.assign({ cls: 'btn-primary' }, ns),
          { label: 'Open Lead', icon: 'eye', run: () => go(`lead.html?id=${l.id}`) },
          can('leads.edit') && { label: 'Edit', icon: 'edit', run: () => leadForm(l) }
        ]
      };
    });
  }

  function activityDrawer(id) {
    openDrawer(() => {
      const a = API.activities.find(x => x.id === id);
      if (!a || a.done) return null;
      const meta = ACTIVITY[a.type];
      const extra = a.type === 'approval' ? { label: 'Open Approval Center', icon: 'check-circle', run: () => go('finance.html') }
        : a.type === 'review' ? { label: 'Quotations Pending Approval', icon: 'file-text', run: () => go('quotations.html?tab=pending') } : null;
      return {
        title: meta.label,
        body: `<div class="dr-hero"><span class="avatar lg">${icon(meta.icon, 'ic lg')}</span><div><h4>${esc(a.title)}</h4><p>${esc(a.sub)}</p></div><span class="tag tag-${a.type}">${meta.label}</span></div>
          <dl class="kv"><dt>When</dt><dd>${fmtDateTime(a.when)}</dd><dt>Branch</dt><dd>${esc(branchOf(a.branchId).name)}</dd><dt>Owner</dt><dd>${esc(a.owner || '—')}</dd></dl>`,
        actions: [
          { label: 'Mark as Done', icon: 'check', cls: 'btn-primary', run: () => act(() => API.completeActivity(id), 'Activity marked as done') },
          extra
        ]
      };
    });
  }

  /* timeline renderer — uses <template id="tplEvent"> when the page provides it */
  function renderTimeline(host, events) {
    host.innerHTML = '';
    const tpl = $('#tplEvent');
    if (!events.length) { host.innerHTML = '<li class="empty-state">No activity yet.</li>'; return; }
    events.forEach(ev => {
      let li;
      if (tpl) {
        li = tpl.content.firstElementChild.cloneNode(true);
        $('[data-f=time]', li).textContent = fmtDateTime(ev.at);
        $('[data-f=text]', li).textContent = ev.text;
        $('[data-f=by]', li).textContent = ev.by;
      } else {
        li = document.createElement('li');
        li.className = 'ev';
        li.innerHTML = `<span class="ev-dot"></span><div><strong>${esc(ev.text)}</strong><small>${esc(fmtDateTime(ev.at))} · ${esc(ev.by)}</small></div>`;
      }
      li.dataset.kind = ev.kind;
      host.appendChild(li);
    });
  }

  /* quick actions shared by dashboard and pages */
  const ACTIONS = {
    newLead: { label: 'New Lead', icon: 'users', run: guard('leads.create', () => leadForm()) },
    createQuote: { label: 'Create Quotation', icon: 'file-text', run: guard('quotes.create', () => quoteForm()) },
    addProject: { label: 'Create Project', icon: 'briefcase', run: guard('projects.create', () => projectForm()) },
    addExpense: { label: 'Add Expense', icon: 'wallet', run: guard('expenses.create', () => expenseForm()) },
    createInvoice: { label: 'Create Invoice', icon: 'invoice', run: guard('invoices.create', () => invoiceForm()) },
    newTransfer: { label: 'New Transfer', icon: 'refresh', run: guard('transfers.create', () => transferForm()) },
    recordPayment: { label: 'Customer Payment', icon: 'coins', run: guard('payments.create', () => paymentForm()) },
    reports: { label: 'Reports', icon: 'chart', run: () => go('reports.html') },
    pendingQuotes: { label: 'Quotes to Approve', icon: 'clock', run: () => go('quotations.html?tab=pending') },
    approvals: { label: 'Approval Center', icon: 'check-circle', run: () => go('finance.html') },
    cashInHand: { label: 'Cash in Hand', icon: 'coins', run: () => go('expenses.html?tab=cashinhand') },
    zoho: { label: 'Zoho Sync', icon: 'book', run: () => go('zoho.html') }
  };

  /* =======================================================
     Layout (sidebar, top bar, filter bar, login)
     ======================================================= */
  let current = null;

  function layoutHTML() {
    $('#sidebar').innerHTML = `
      <a class="brand" href="index.html" aria-label="Rich Mount ERP home">
        <img src="images/rich-mount-logo.png" alt="" width="64" height="49">
        <span class="brand-text"><span class="brand-name">RICH MOUNT</span><span class="brand-sub">ERP SYSTEM</span></span>
      </a>
      <div class="sidebar-inner">
        <label class="side-search">${icon('search')}<input type="search" id="menuSearch" placeholder="Search menu..." aria-label="Search menu"></label>
        <nav class="nav" id="nav"></nav>
      
      </div>`;
    const trail = current.id === 'dashboard' ? [['Home']] : (current.crumbs || [[current.title]]);
    const crumb = (current.id === 'dashboard' ? '<span class="crumb">Dashboard</span>' : '<a class="crumb" href="index.html">Dashboard</a>')
      + trail.map((c, i) => icon('chevron-right', 'ic sep') + (c[1] && i < trail.length - 1 ? `<a class="crumb" href="${c[1]}">${esc(c[0])}</a>` : `<span class="crumb current">${esc(c[0])}</span>`)).join('');
    $('#topbar').innerHTML = `
      <button class="tb-btn" id="menuToggle" aria-label="Toggle menu">${icon('menu')}</button>
      <nav class="crumbs" aria-label="Breadcrumb"><a class="crumb-home" href="index.html" aria-label="Dashboard">${icon('home')}</a>${crumb}</nav>
      <div class="gsearch" id="gsearch">${icon('search')}
        <input id="globalSearch" type="search" placeholder="Search across Leads, Projects, Quotes, Expenses..." autocomplete="off" aria-label="Global search">
        <kbd>Ctrl + K</kbd><div class="gs-results" id="gsResults" hidden></div>
      </div>
      <div class="tb-right">
        <button class="tb-branch" id="branchBtn" aria-haspopup="true">${icon('building')}<span id="branchLabel"></span>${icon('chevron-down', 'ic sm')}</button>
        <button class="tb-btn only-mobile" id="searchToggle" aria-label="Search">${icon('search')}</button>
        <button class="tb-btn" id="notifBtn" aria-label="Notifications" aria-haspopup="true">${icon('bell')}<span class="badge" id="notifCount"></span></button>
        <button class="tb-btn" id="settingsBtn" aria-label="Settings" aria-haspopup="true">${icon('settings')}</button>
        <button class="tb-user" id="userBtn" aria-haspopup="true">
          <span class="tb-avatar" id="userAvatar" aria-hidden="true"></span>
          <span class="tb-user-text"><strong id="userName"></strong><small id="userRole"></small></span>${icon('chevron-down', 'ic sm')}
        </button>
      </div>`;
    const fb = $('#filterBar');
    if (fb) {
      /* below 1200px the form moves into a right-side panel opened by the Filters button */
      fb.innerHTML = `<div class="filter-mobile-bar">
          <button type="button" class="btn btn-ghost filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filterPanel">${icon('filter', 'ic sm')}Filters<span class="filter-count" id="filterCount" hidden></span></button>
          <span class="filter-summary" id="filterSummary"></span>
        </div>
        <div class="filter-scrim" id="filterScrim"></div>
        <div class="filter-panel" id="filterPanel" aria-label="Filters">
        <div class="filter-panel-head"><strong>${icon('filter', 'ic sm')}Filters</strong><button type="button" class="icon-btn" id="filterClose" aria-label="Close filters">${icon('x')}</button></div>
        <form id="filterForm" class="filter-form" autocomplete="off">
        <label class="f-field"><span class="f-label">Country</span><span class="select-wrap with-icon">${icon('map-pin')}<select name="country" id="fCountry"></select></span></label>
        <label class="f-field"><span class="f-label">Branch</span><span class="select-wrap with-icon">${icon('building')}<select name="branch" id="fBranch"></select></span></label>
        <label class="f-field"><span class="f-label">Department</span><span class="select-wrap"><select name="department" id="fDepartment"></select></span></label>
        <label class="f-field"><span class="f-label">Project Status</span><span class="select-wrap"><select name="projectStatus" id="fStatus"></select></span></label>
        <label class="f-field"><span class="f-label">Engineer</span><span class="select-wrap"><select name="engineer" id="fEngineer"></select></span></label>
        <div class="f-field f-range"><span class="f-label">Date Range</span>
          <span class="range-input">${icon('calendar')}<input type="date" name="from" id="fFrom" aria-label="From date"><span class="range-sep">–</span><input type="date" name="to" id="fTo" aria-label="To date"></span></div>
        <div class="f-actions">
          <button type="submit" class="btn btn-primary">Apply Filter</button>
          <button type="button" class="icon-btn outline" id="filterOpts" title="Clear / filter options" aria-label="Filter options">${icon('sliders')}</button>
          <button type="button" class="icon-btn outline" id="saveFilter" title="Save as default view" aria-label="Save filter">${icon('bookmark')}</button>
        </div></form></div>`;
    }
    document.body.insertAdjacentHTML('beforeend', `
      <div class="login" id="login" hidden>
        <form class="login-card" id="loginForm">
          <div class="login-brand"><img src="images/rich-mount-logo.png" alt="" width="58" height="44"><div><strong>RICH MOUNT</strong><small>ERP SYSTEM</small></div></div>
          <h2>Sign in</h2><p>Pick a demo role to explore what each user sees.</p>
          <label class="field"><span class="field-label">Email</span><input type="email" value="john.doe@richmount.ae" required></label>
          <label class="field"><span class="field-label">Password</span><input type="password" value="demo1234" required></label>
          <label class="field"><span class="field-label">Demo role</span><span class="select-wrap"><select id="loginRole">${AUTH.USERS.map(u => `<option value="${u.key}">${u.roleLabel} — ${u.name}</option>`).join('')}</select></span></label>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
        </form>
      </div>
      <div class="chart-tip" id="chartTip" role="tooltip"></div>
      <div class="toasts" id="toasts" aria-live="polite"></div>`);
  }

  function renderNav() {
    const here = location.pathname.split('/').pop() + location.search;
    $('#nav').innerHTML = NAV.filter(n => can(n.perm)).map(n => {
      const active = n.id === current.id;
      if (!n.children) {
        return `<div class="nav-group"><a class="nav-item${active ? ' active' : ''}" href="${n.href}" title="${esc(n.label)}"${active ? ' aria-current="page"' : ''}>
          <span class="nav-ic">${icon(n.icon)}</span><span class="nav-label">${esc(n.label)}</span></a></div>`;
      }
      return `<div class="nav-group" data-group="${n.id}">
        <button class="nav-item${active ? ' active' : ''}" data-nav="${n.id}" title="${esc(n.label)}" aria-haspopup="true" aria-expanded="false">
          <span class="nav-ic">${icon(n.icon)}</span><span class="nav-label">${esc(n.label)}</span>${icon('chevron-right', 'ic nav-chev')}</button>
        <div class="nav-sub">${n.children.filter(c => !c[2] || can(c[2])).map(c => `<a class="nav-subitem${c[1] === here ? ' active' : ''}" href="${c[1]}">${esc(c[0])}</a>`).join('')}</div>
      </div>`;
    }).join('');
  }

  const myNotifications = () => API.notifications.filter(n => me.role === 'admin' || n.roles.includes(me.role));
  function renderHeader() {
    $('#branchLabel').textContent = scopeLabel();
    $('#userName').textContent = me.name;
    $('#userRole').textContent = me.roleLabel;
    $('#userAvatar').textContent = initials(me.name);
    $('#userAvatar').dataset.role = me.role;
    $('#userBtn').title = `${me.name} · ${me.email} · ${me.roleLabel}`;
    const unread = myNotifications().filter(n => n.unread).length;
    const badge = $('#notifCount');
    badge.textContent = unread > 99 ? '99+' : unread;
    badge.classList.toggle('zero', !unread);
    const sf = $('#saveFilter');
    if (sf) {
      const saved = JSON.stringify(store.get('savedFilter', null)) === JSON.stringify(state.filters);
      sf.classList.toggle('saved', saved);
      sf.title = saved ? 'This view is saved as your default' : 'Save as default view';
    }
  }

  /* ---------- filters ---------- */
  function fill(sel, list, value) {
    const v = list.some(o => String(o.v) === String(value)) ? value : list[0].v;
    sel.innerHTML = opts(list, v);
    sel.value = v;
    return v;
  }
  function fillBranches(country, value) {
    const list = allowedBranches().filter(b => country === 'all' || b.country === country);
    const head = list.length > 1 || me.branches === '*' ? [{ v: 'all', t: me.branches === '*' ? 'All Branches' : 'All my branches' }] : [];
    return fill($('#fBranch'), head.concat(list.map(b => ({ v: b.id, t: b.short }))), value);
  }
  function fillEngineers(country, branch, value) {
    const list = API.engineers.filter(e => (branch === 'all' || e.branchId === branch) && (country === 'all' || branchOf(e.branchId).country === country)
      && AUTH.inScope({ branchId: e.branchId, engineerId: e.id }, me));
    return fill($('#fEngineer'), [{ v: 'all', t: 'All' }].concat(list.map(e => ({ v: e.id, t: e.name }))), value);
  }
  function syncFilterForm() {
    if (!$('#filterForm')) return;
    const f = state.filters;
    const countries = API.countries.filter(c => allowedBranches().some(b => b.country === c.id));
    f.country = fill($('#fCountry'), (countries.length > 1 ? [{ v: 'all', t: 'All Countries' }] : []).concat(countries.map(c => ({ v: c.id, t: c.name }))), f.country);
    f.branch = fillBranches(f.country, f.branch);
    f.engineer = fillEngineers(f.country, f.branch, f.engineer);
    f.department = fill($('#fDepartment'), [{ v: 'all', t: 'All' }].concat(deptOpts()), f.department);
    f.projectStatus = fill($('#fStatus'), [{ v: 'all', t: 'All' }].concat(API.projectStatuses.map(s => ({ v: s.id, t: s.name }))), f.projectStatus);
    $('#fFrom').value = f.from;
    $('#fTo').value = f.to;
    const oneBranch = allowedBranches().length === 1;
    $('#fCountry').disabled = $('#fBranch').disabled = oneBranch;
    $('#fBranch').title = oneBranch ? `Your access is limited to ${allowedBranches()[0].name}` : '';
    $('#fEngineer').disabled = !!myEngineer();
    $('#fEngineer').title = myEngineer() ? 'Project Engineers see only their own records' : '';
    // compact summary next to the Filters button (responsive view)
    const picked = sel => sel.value !== 'all' ? sel.options[sel.selectedIndex].text : '';
    const extra = [$('#fDepartment'), $('#fStatus'), $('#fEngineer')].filter(x => x.value !== 'all').length;
    $('#filterCount').textContent = extra;
    $('#filterCount').hidden = !extra;
    $('#filterSummary').textContent = [picked($('#fBranch')) || picked($('#fCountry')) || 'All branches', picked($('#fDepartment')), picked($('#fStatus')), picked($('#fEngineer')),
      `${fmtDate(new Date(f.from))} – ${fmtDate(new Date(f.to))}`].filter(Boolean).join(' · ');
  }
  function setFilterPanel(open) {
    const pn = $('#filterPanel');
    if (!pn) return;
    pn.classList.toggle('open', open);
    $('#filterScrim').classList.toggle('open', open);
    $('#filterToggle').setAttribute('aria-expanded', String(open));
    if (open) setTimeout(() => { const first = $('select:not(:disabled), input', pn); if (first) first.focus(); }, 220);
  }
  function readFilterForm() {
    const fm = $('#filterForm');
    return {
      country: val(fm, 'country'), branch: val(fm, 'branch'), department: val(fm, 'department'),
      projectStatus: val(fm, 'projectStatus'), engineer: val(fm, 'engineer'), from: val(fm, 'from'), to: val(fm, 'to')
    };
  }
  function applyFilters(next, silent) {
    if (!next.from || !next.to) { toast('Choose both a start and an end date.', 'error'); return; }
    if (next.from > next.to) { toast('The start date must be before the end date.', 'error'); return; }
    state.filters = scopeFilters(next);
    store.set('filters', state.filters);
    syncFilterForm();
    refresh();
    if (!silent) toast('Filters applied');
  }
  function setBranch(id) {
    if (id !== 'all' && !allowedBranches().some(b => b.id === id)) { toast(`${roleLabel()} has no access to ${branchOf(id).name}.`, 'error'); return; }
    const f = Object.assign({}, state.filters, { branch: id });
    if (id !== 'all') f.country = branchOf(id).country;
    if (f.engineer !== 'all' && id !== 'all' && API.engineer(f.engineer).branchId !== id) f.engineer = 'all';
    applyFilters(f, true);
    toast(`Showing ${id === 'all' ? 'all branches' : branchOf(id).name}`, 'info');
  }

  /* switch the logged-in demo user; the whole app reads it from AuthService */
  function switchRole(key) {
    if (key === me.key) return;
    const u = AUTH.switchUser(key);
    store.del('filters'); // filters belong to the previous user's branch scope
    flash(`Signed in as ${u.name} (${u.roleLabel})`, 'info');
    go('index.html');
  }

  /* ---------- header menus ---------- */
  function openProfileMenu() {
    const anchor = $('#userBtn');
    menu(anchor, [
      { html: `<div class="pop-user"><span class="tb-avatar lg" data-role="${me.role}">${esc(initials(me.name))}</span><div><strong>${esc(me.name)}</strong><small>${esc(me.email)}</small><span class="role-chip">${esc(me.roleLabel)}</span></div></div>` },
      { divider: true },
      { header: 'Switch demo role' }
    ].concat(AUTH.USERS.map(u => ({ label: u.roleLabel, hint: `${u.name} · ${u.email}`, icon: 'shield', checked: u.key === me.key, run: () => switchRole(u.key) })))
      .concat([
        { divider: true },
        { label: 'My profile', icon: 'user', run: () => go('profile.html') },
        { label: 'Log out', icon: 'log-out', danger: true, run: () => { $('#login').hidden = false; $('#loginRole').value = me.key; } }
      ]), { align: 'right', width: 250 });
  }

  function openNotifications() {
    const list = myNotifications().slice(0, 40);
    const NI = { lead: 'users', quote: 'file-text', expense: 'receipt', transfer: 'refresh', zoho: 'alert', project: 'briefcase', invoice: 'invoice' };
    const el = popover($('#notifBtn'), `<div class="notif-head"><strong>Notifications</strong><button class="link-btn" data-readall>Mark all as read</button></div>
      <div class="notif-list">${list.length ? list.map((n, i) => `<button class="notif n-${n.type}${n.unread ? ' unread' : ''}" data-n="${i}"><span class="n-ic">${icon(NI[n.type] || 'bell')}</span><span><strong>${esc(n.title)}</strong><p>${esc(n.text)}</p><small>${esc(fmtAgo(n.time))}</small></span></button>`).join('') : '<div class="gs-empty">No notifications</div>'}</div>`,
    { align: 'right', width: 340 });
    if (!el) return;
    el.addEventListener('click', e => {
      if (e.target.closest('[data-readall]')) { API.markAllRead(me.role); closePop(); toast('All notifications marked as read', 'info'); return; }
      const b = e.target.closest('[data-n]');
      if (!b) return;
      const n = list[+b.dataset.n];
      closePop();
      if (n.unread) API.markRead(n.id);
      if (n.link) go(n.link);
    });
  }

  /* ---------- global search (results open detail pages) ---------- */
  let searchHits = [];
  function runSearch(raw) {
    const box = $('#gsResults'), q = raw.trim().toLowerCase();
    if (q.length < 2) { box.hidden = true; return; }
    const m = s => String(s || '').toLowerCase().includes(q);
    const visibleLead = r => (r.stage !== undefined && r.leadNumber ? AUTH.canSeeLead(r, me) : AUTH.inScope(r, me));
    const groups = [
      { label: 'Leads', icon: 'users', items: !can('leads.view') ? [] : API.leads.filter(l => visibleLead(l) && (m(l.name) || m(l.company) || m(l.leadNumber) || m(l.subject) || m(l.contact))).slice(-5).reverse().map(l => ({ t: `${l.leadNumber} · ${l.name}`, s: `${l.subject} · ${branchOf(l.branchId).short} · ${API.leadStatus(l)}`, url: `lead.html?id=${l.id}` })) },
      { label: 'Quotations', icon: 'file-text', items: !can('quotes.view') ? [] : API.quotes.filter(x => visibleLead(x) && (m(x.number) || m(leadOfQuote(x).name) || m(leadOfQuote(x).company))).slice(-5).reverse().map(x => ({ t: `${x.number} · ${leadOfQuote(x).name}`, s: `${fmtAED(x.total)} · ${quoteStatus(x.status).name}`, url: `quote.html?id=${x.id}` })) },
      { label: 'Projects', icon: 'briefcase', items: !can('projects.view') ? [] : API.projects.filter(p => visibleLead(p) && (m(p.name) || m(p.projectNumber) || m(p.poNumber))).slice(-5).reverse().map(p => ({ t: `${p.projectNumber} · ${p.name}`, s: `${p.poNumber || (API.hasPO(p) ? 'PO received' : p.emailApproval ? 'PO awaited' : 'PO pending')} · ${statusMeta(p.status).name}`, url: `project.html?id=${p.id}` })) },
      { label: 'Invoices', icon: 'invoice', items: !can('invoices.view') ? [] : API.invoices.filter(i => AUTH.inScope(i, me) && m(i.number)).slice(-3).reverse().map(i => ({ t: i.number, s: `${projLabel(i.projectId)} · ${fmtAED(i.total)}`, url: `project.html?id=${i.projectId}` })) },
      { label: 'Transactions', icon: 'receipt', items: !can('expenses.view') ? [] : API.expenses.filter(e => AUTH.inScope(e, me) && m(e.ref)).slice(-3).reverse().map(e => ({ t: e.ref, s: `${e.vendor} · ${fmtAED(e.amount)} · ${expStatusName(e)}`, url: e.projectId ? `project.html?id=${e.projectId}` : 'expenses.html' })) },
      { label: 'Engineers', icon: 'hard-hat', items: myEngineer() ? [] : API.engineers.filter(e => AUTH.inScope({ branchId: e.branchId }, me) && m(e.name)).map(e => ({ t: e.name, s: branchOf(e.branchId).name, run: () => applyFilters(Object.assign({}, state.filters, { country: branchOf(e.branchId).country, branch: e.branchId, engineer: e.id })) })) },
      { label: 'Branches', icon: 'building-2', items: myEngineer() ? [] : allowedBranches().filter(b => m(b.name) || m(b.code)).map(b => ({ t: b.name, s: `${b.code} · ${b.quoteFormat}`, run: () => setBranch(b.id) })) }
    ].filter(g => g.items.length);
    searchHits = [];
    box.innerHTML = groups.length ? groups.map(g => `<div class="gs-group">${g.label}</div>` + g.items.map(it => {
      searchHits.push(it);
      return `<button class="gs-item" data-hit="${searchHits.length - 1}"><span class="gs-ic">${icon(g.icon, 'ic sm')}</span><span><strong>${esc(it.t)}</strong><small>${esc(it.s)}</small></span></button>`;
    }).join('')).join('') : `<div class="gs-empty">No results for “${esc(raw.trim())}”</div>`;
    box.hidden = false;
  }
  const runHit = h => (h.url ? go(h.url) : h.run());
  function closeSearch() { $('#gsResults').hidden = true; $('#topbar').classList.remove('search-open'); }

  /* ---------- events ---------- */
  const redraw = () => { if (current && current.resize) current.resize(); };

  function bindLayout() {
    const app = $('#app');
    $('#menuToggle').addEventListener('click', () => {
      if (window.innerWidth < 1200) app.classList.toggle('nav-open');
      else { app.classList.toggle('collapsed'); store.set('collapsed', app.classList.contains('collapsed')); setTimeout(redraw, 220); }
    });
    $('#scrim').addEventListener('click', () => app.classList.remove('nav-open'));

    /* desktop: sub-pages open in a flyout panel beside the sidebar on click; phones: inline list */
    const desktop = () => window.innerWidth >= 1200;
    $('#nav').addEventListener('click', e => {
      const item = e.target.closest('button[data-nav]');
      if (!item) return;
      if (desktop()) { if (flyout && flyout.item === item) closeFlyout(); else openFlyout(item); return; }
      const g = item.parentElement, open = !g.classList.contains('open');
      $$('.nav-group.open', $('#nav')).forEach(x => { if (x !== g) { x.classList.remove('open'); const b = $('.nav-item', x); if (b) b.setAttribute('aria-expanded', 'false'); } });
      g.classList.toggle('open', open);
      item.setAttribute('aria-expanded', String(open));
    });

    $('#nav').addEventListener('scroll', closeFlyout, { passive: true });

    $('#menuSearch').addEventListener('input', e => {
      closeFlyout();
      const q = e.target.value.trim().toLowerCase();
      $('#nav').classList.toggle('searching', !!q);
      let any = false;
      $$('.nav-group', $('#nav')).forEach(g => {
        const label = $('.nav-label', g).textContent.toLowerCase();
        const subs = $$('.nav-subitem', g);
        let subHit = false;
        subs.forEach(s => { const hit = !q || s.textContent.toLowerCase().includes(q) || label.includes(q); s.hidden = !hit; if (hit && q) subHit = true; });
        const show = !q || label.includes(q) || subHit;
        g.hidden = !show;
        if (show) any = true;
        if (subs.length && q) g.classList.toggle('open', show);
      });
      const empty = $('.nav-empty', $('#nav'));
      if (!any && !empty) $('#nav').insertAdjacentHTML('beforeend', '<div class="nav-empty">No menu items match.</div>');
      if (any && empty) empty.remove();
    });

    $('#branchBtn').addEventListener('click', e => {
      const f = state.filters;
      const list = allowedBranches();
      if (list.length === 1) { toast(`${me.name} (${roleLabel()}) has access to ${list[0].name} only.`, 'info'); return; }
      menu(e.currentTarget, [{ header: 'Branch' }, { label: me.branches === '*' ? 'All Branches' : 'All my branches', icon: 'grid', checked: f.branch === 'all', run: () => setBranch('all') }]
        .concat(list.map(b => ({ label: b.name, hint: `${b.code} · ${b.country}`, icon: 'building', checked: f.branch === b.id, run: () => setBranch(b.id) }))), { align: 'right', width: 240 });
    });
    $('#notifBtn').addEventListener('click', openNotifications);
    $('#settingsBtn').addEventListener('click', e => {
      menu(e.currentTarget, [
        { label: 'Settings & preferences', icon: 'settings', run: () => go('settings.html'), disabled: !navAllowed('settings') },
        { label: 'Zoho Books integration', icon: 'book', run: () => go('zoho.html'), disabled: !navAllowed('zoho') },
        { label: 'Branches & quote formats', icon: 'building-2', run: () => go('branches.html'), disabled: !navAllowed('branches') },
        { divider: true },
        { label: 'Clear filters', icon: 'rotate-ccw', run: () => applyFilters(Object.assign({}, DEFAULT_FILTERS)) }
      ], { align: 'right', width: 240 });
    });
    $('#userBtn').addEventListener('click', openProfileMenu);

    const gs = $('#globalSearch');
    gs.addEventListener('input', debounce(() => runSearch(gs.value), 120));
    gs.addEventListener('focus', () => { if (gs.value.trim().length > 1) runSearch(gs.value); });
    gs.addEventListener('keydown', e => {
      if (e.key === 'Enter' && searchHits[0]) { e.preventDefault(); const h = searchHits[0]; closeSearch(); gs.blur(); runHit(h); }
    });
    $('#gsResults').addEventListener('mousedown', e => {
      const b = e.target.closest('[data-hit]');
      if (!b) return;
      e.preventDefault();
      const h = searchHits[+b.dataset.hit];
      closeSearch(); gs.blur(); runHit(h);
    });
    gs.addEventListener('blur', () => setTimeout(() => { if (document.activeElement !== gs) $('#gsResults').hidden = true; }, 120));
    $('#searchToggle').addEventListener('click', () => { $('#topbar').classList.toggle('search-open'); if ($('#topbar').classList.contains('search-open')) gs.focus(); });

    if ($('#filterForm')) {
      $('#fCountry').addEventListener('change', e => { const b = fillBranches(e.target.value, $('#fBranch').value); fillEngineers(e.target.value, b, $('#fEngineer').value); });
      $('#fBranch').addEventListener('change', e => fillEngineers($('#fCountry').value, e.target.value, $('#fEngineer').value));
      $('#filterForm').addEventListener('submit', e => {
        e.preventDefault();
        const f = readFilterForm();
        applyFilters(f);
        if (f.from && f.to && f.from <= f.to) setFilterPanel(false);
      });
      $('#filterToggle').addEventListener('click', () => setFilterPanel(true));
      $('#filterClose').addEventListener('click', () => setFilterPanel(false));
      $('#filterScrim').addEventListener('click', () => setFilterPanel(false));
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && $('#filterPanel').classList.contains('open') && !$('.popover')) setFilterPanel(false); });
      $('#filterOpts').addEventListener('click', e => menu(e.currentTarget, [
        { label: 'Clear all filters', icon: 'filter', run: () => applyFilters(Object.assign({}, DEFAULT_FILTERS, { country: 'all', branch: 'all', from: '2024-01-01' })) },
        { label: 'Reset to default view', icon: 'rotate-ccw', run: () => applyFilters(Object.assign({}, DEFAULT_FILTERS, store.get('savedFilter', {}))) },
        { label: 'This year to date', icon: 'calendar', run: () => applyFilters(Object.assign({}, state.filters, { from: `${TODAY.getFullYear()}-01-01`, to: toISO(TODAY) })) },
        { label: 'Last year', icon: 'calendar', run: () => applyFilters(Object.assign({}, state.filters, { from: `${TODAY.getFullYear() - 1}-01-01`, to: `${TODAY.getFullYear() - 1}-12-31` })) },
        { divider: true },
        { label: 'Forget saved view', icon: 'x', disabled: !store.get('savedFilter', null), run: () => { store.del('savedFilter'); renderHeader(); toast('Saved view removed', 'info'); } }
      ], { align: 'right', width: 230 }));
      $('#saveFilter').addEventListener('click', () => {
        const f = readFilterForm();
        if (f.from > f.to) { toast('The start date must be before the end date.', 'error'); return; }
        store.set('savedFilter', f);
        applyFilters(f, true);
        toast('Filters saved as your default view');
      });
    }

    $('#loginForm').addEventListener('submit', e => {
      e.preventDefault();
      $('#login').hidden = true;
      const key = $('#loginRole').value;
      if (key !== me.key) switchRole(key);
      else toast(`Signed in as ${me.name}`);
    });

    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        $('#topbar').classList.add('search-open');
        gs.focus(); gs.select();
        return;
      }
      if (e.key === 'Escape') {
        if (flyout) { const it = flyout.item; closeFlyout(); it.focus(); return; }
        if (openPop) { closePop(); return; }
        if (!$('#gsResults').hidden || $('#topbar').classList.contains('search-open')) { closeSearch(); gs.blur(); return; }
        if (layers.length) { layers[layers.length - 1](); return; }
        app.classList.remove('nav-open');
      }
    });
    document.addEventListener('mousedown', e => {
      if (flyout && !flyout.el.contains(e.target) && !flyout.item.contains(e.target)) closeFlyout();
      if (openPop && !openPop.el.contains(e.target) && !openPop.anchor.contains(e.target)) closePop();
      if (!e.target.closest('#gsearch') && !e.target.closest('#searchToggle')) $('#topbar').classList.remove('search-open');
    });
    window.addEventListener('resize', debounce(() => { closePop(); closeFlyout(); redraw(); if (window.innerWidth >= 1200) { app.classList.remove('nav-open'); setFilterPanel(false); } }, 150));
    document.addEventListener('scroll', e => {
      if (openPop && !(e.target instanceof Node && openPop.el.contains(e.target))) closePop();
      const tip = $('#chartTip'); if (tip) tip.classList.remove('show');
    }, { passive: true, capture: true });
  }

  /* ---------- sidebar flyout ---------- */
  let flyout = null;
  function closeFlyout() {
    if (!flyout) return;
    flyout.item.classList.remove('flyout-open');
    flyout.item.setAttribute('aria-expanded', 'false');
    flyout.el.remove();
    flyout = null;
  }
  function openFlyout(item) {
    closeFlyout();
    const def = NAV.find(n => n.id === item.dataset.nav);
    const links = $$('.nav-subitem', item.parentElement);
    const el = document.createElement('div');
    el.className = 'nav-flyout';
    el.setAttribute('role', 'menu');
    el.innerHTML = `<div class="nf-head">${icon(def.icon, 'ic sm')}${esc(def.label)}</div>` +
      links.map(a => `<a class="nf-link${a.classList.contains('active') ? ' active' : ''}" role="menuitem" href="${a.getAttribute('href')}"><i></i>${esc(a.textContent)}</a>`).join('');
    document.body.appendChild(el);
    const r = item.getBoundingClientRect(), side = $('#sidebar .sidebar-inner').getBoundingClientRect();
    const top = Math.max(8, Math.min(r.top - 6, window.innerHeight - el.offsetHeight - 8));
    el.style.left = (side.right + 10) + 'px';
    el.style.top = top + 'px';
    el.style.setProperty('--arrow', Math.max(12, Math.min(r.top + r.height / 2 - top - 6, el.offsetHeight - 24)) + 'px');
    el.addEventListener('keydown', e => {
      const ls = $$('.nf-link', el), i = ls.indexOf(document.activeElement);
      if (e.key === 'ArrowDown') { e.preventDefault(); ls[(i + 1) % ls.length].focus(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); ls[(i - 1 + ls.length) % ls.length].focus(); }
    });
    item.classList.add('flyout-open');
    item.setAttribute('aria-expanded', 'true');
    flyout = { el, item };
  }

  /* mock Zoho worker: items marked "syncing" complete after a short delay */
  let zohoTimer = null;
  function zohoWorker() {
    if (zohoTimer || !API.zohoLog.some(z => z.status === 'syncing')) return;
    zohoTimer = setTimeout(() => { zohoTimer = null; API.processZoho(); }, 1800);
  }

  /* =======================================================
     Page lifecycle
     ======================================================= */
  function refresh() {
    state.ctx = compute();
    renderHeader();
    if (current.allowed && current.render) current.render(state.ctx);
    paintDrawer();
    zohoWorker();
  }

  function renderPageHead(def) {
    const head = $('#pageHead');
    if (!head) return;
    const actions = (def.actions || []).filter(a => !a.perm || can(a.perm));
    head.innerHTML = `<div><h1>${esc(def.title)}</h1>${def.sub ? `<p>${esc(def.sub)}</p>` : ''}</div>
      <div class="page-actions">${actions.map((a, i) => `<button class="btn ${a.cls || 'btn-primary'} btn-lg" data-pa="${i}">${a.icon ? icon(a.icon) : ''}${esc(a.label)}</button>`).join('')}</div>`;
    $$('[data-pa]', head).forEach(b => b.addEventListener('click', () => actions[+b.dataset.pa].run()));
  }

  function page(def) {
    current = def;
    if (store.get('collapsed', false)) $('#app').classList.add('collapsed');
    if (store.get('compact', false)) document.body.classList.add('compact');
    layoutHTML();
    // upgrade file inputs now and whenever forms / modals are added later
    enhanceFileInputs(document);
    new MutationObserver(() => enhanceFileInputs(document)).observe(document.body, { childList: true, subtree: true });
    renderNav();
    syncFilterForm();
    bindLayout();
    state.ctx = compute();
    renderHeader();
    renderPageHead(def);

    /* route guard: page permission (or its navigation permission) + record-level check */
    def.allowed = (def.permission ? can(def.permission) : navAllowed(def.id)) && (!def.allow || def.allow());
    if (!def.allowed) {
      $$('#content > :not(#pageHead)').forEach(el => el.remove());
      $('#content').insertAdjacentHTML('beforeend', `<section class="card denied">${icon('lock', 'ic lg')}<h2>${esc(def.deniedTitle || 'No access')}</h2>
        <p>${esc(def.deniedText || `${me.name} (${roleLabel()}) does not have access to ${def.title}.`)}</p><a class="btn btn-primary" href="index.html">Back to Dashboard</a></section>`);
      $('#pageHead .page-actions').innerHTML = '';
    } else {
      if (def.mount) def.mount(state.ctx);
      if (def.render) def.render(state.ctx);
      if (params.get('lead') && def.id !== 'quotes' && API.lead(params.get('lead'))) leadDrawer(params.get('lead'));
    }

    API.onChange(refresh);
    zohoWorker();
    try {
      const f = JSON.parse(sessionStorage.getItem('rm.flash') || 'null');
      if (f) { sessionStorage.removeItem('rm.flash'); toast(f[0], f[1]); }
    } catch (e) { /* ignore */ }
  }

  function focusSection(el) {
    if (!el) return;
    setTimeout(() => {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      el.classList.add('flash');
      setTimeout(() => el.classList.remove('flash'), 1600);
    }, 120);
  }
  const setParam = (key, value) => {
    const p = new URLSearchParams(location.search);
    if (value == null || value === '') p.delete(key); else p.set(key, value);
    const qs = p.toString();
    history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
  };

  window.RM = {
    API, TODAY, MONTHS, WEEKDAYS, ROLES, PERMS: AUTH.ROLE_PERMISSIONS, KPI, COLS, ACTIONS, ACTIVITY, OPEN_STATUSES, DRAFTISH, DOC_CSS,
    LEAD_TONE, PROJECT_TONE, STAGE_TONE,
    $, $$, esc, icon, pad, toISO, parseISO, startOfDay, endOfDay, addDays, addMonths, startOfMonth, endOfMonth, inRange, sum, debounce,
    fmtDate, fmtTime, fmtDateTime, fmtNum, fmtAED, fmtAED2, fmtAEDCompact, fmtAgo, initials, avatar, sourcePill, branchOf, engName, deptName, statusMeta, quoteStatus,
    projLabel, stageName, leadOfQuote, fileNames,
    pill, leadPill, quotePill, projectPill, expStatus, expStatusName, transferStatus, transferRoute, invoicePill, viewMoreCol,
    AUTH, me, state, store, params, go, can, guard, navAllowed, myEngineer, allowedBranches, scopeOf, branchVisible, scopeLabel, rangeLabel,
    toast, flash, act, menu, popover, closePop, modal, confirmDialog, promptDialog, openDrawer, dataTable, tableModal, periodStart,
    downloadCSV, downloadFile, printHTML, printTable, renderKpis, renderTimeline, field, selectCtl, opts, val, validate,
    leadForm, assignForm, assignBoard, quoteForm, projectForm, poForm, completionForm, expenseForm, transferForm, editTransferForm, paymentForm,
    cashLedgerModal, invoiceForm, invoicePreview, approveExpense, rejectExpense, editExpenseForm, approveTransfer, rejectTransfer,
    leadDrawer, activityDrawer, leadMenu, nextStep, awardQuote, deleteLead,
    applyFilters, setBranch, switchRole, focusSection, setParam,
    page
  };
})();
