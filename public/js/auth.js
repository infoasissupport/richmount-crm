/* =========================================================
   Rich Mount ERP — AuthService / PermissionService (demo)

   ONE source of truth for:
   - the demo users (who is logged in)
   - the role → permission matrix
   - branch access
   - the persisted session (survives page changes and refresh)

   When the Laravel backend is connected, `session()` is filled from
   the authenticated API user (id, role, permissions, branches) and the
   rest of the UI keeps calling Auth.can('quotes.approve').
   NOTE: browser storage is NOT security. Real authorization must be
   enforced server-side; hiding buttons is only UX.
   ========================================================= */
(function () {
  'use strict';

  /* ---------- storage: localStorage, mirrored into window.name ----------
     Some ways of opening the demo (file:// in some browsers, editor
     previews) give each page its own or no localStorage. window.name
     survives navigation inside the same tab, so the session and demo data
     still carry across pages. */
  const WN = '__RICHMOUNT__';
  let tab = {};
  try { if (window.name && window.name.indexOf(WN) === 0) tab = JSON.parse(window.name.slice(WN.length)) || {}; } catch (e) { tab = {}; }
  const flushTab = () => { try { window.name = WN + JSON.stringify(tab); } catch (e) { /* ignore */ } };
  const lsGet = k => { try { return localStorage.getItem(k); } catch (e) { return null; } };
  const lsSet = (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* storage blocked or full */ } };
  const has = k => Object.prototype.hasOwnProperty.call(tab, k);
  /* every write is time-stamped in both places; the newer copy wins, so
     other tabs (shared localStorage) and isolated pages (tab copy) both stay correct */
  const STAMP = 'rm.__ts';
  const preferTab = +(tab[STAMP] || 0) > +(lsGet(STAMP) || 0);
  const storage = {
    get(k) {
      if (preferTab && has(k)) return tab[k];
      const v = lsGet(k);
      return v != null ? v : (has(k) ? tab[k] : null);
    },
    set(k, v) {
      const ts = String(Date.now());
      lsSet(k, v); lsSet(STAMP, ts);
      tab[k] = v; tab[STAMP] = ts;
      flushTab();
    },
    del(k) {
      const ts = String(Date.now());
      try { localStorage.removeItem(k); } catch (e) { /* ignore */ }
      lsSet(STAMP, ts);
      delete tab[k]; tab[STAMP] = ts;
      flushTab();
    }
  };

  /* ---------- permissions ---------- */
  const PERMISSIONS = {
    'dashboard.view': 'View dashboard',
    'leads.view': 'View leads', 'leads.create': 'Create leads', 'leads.edit': 'Edit leads', 'leads.assign': 'Assign engineers', 'leads.delete': 'Delete leads',
    'quotes.view': 'View quotations', 'quotes.create': 'Create / clone quotations', 'quotes.edit': 'Edit draft quotations', 'quotes.submit': 'Submit for approval',
    'quotes.approve': 'Approve / reject quotations', 'quotes.send': 'Send quotations', 'quotes.award': 'Mark quotations awarded',
    'projects.view': 'View projects', 'projects.create': 'Create projects', 'projects.open': 'Record PO / open project', 'projects.edit': 'Update project status & work completion',
    'expenses.view': 'View expenses & cash in hand', 'expenses.create': 'Record expenses (OCR)', 'expenses.approve': 'Approve / reject expenses',
    'payments.create': 'Record customer payments',
    'transfers.view': 'View transfers', 'transfers.create': 'Create transfers', 'transfers.edit': 'Edit submitted transfers', 'transfers.approve': 'Approve / reject transfers',
    'transactions.view': 'View approval center', 'transactions.approve': 'Approve transactions',
    'invoices.view': 'View invoices', 'invoices.create': 'Create invoices', 'invoices.submit': 'Submit invoices / close project',
    'reports.view': 'View reports', 'reports.export': 'Download / print reports',
    'zoho.view': 'View Zoho Books status', 'zoho.sync': 'Sync / retry Zoho Books',
    'users.view': 'View users', 'users.edit': 'Manage users', 'roles.view': 'View roles & permissions', 'roles.edit': 'Manage roles',
    'branches.view': 'View branches', 'branches.edit': 'Manage branches',
    'settings.view': 'View settings', 'settings.edit': 'Manage settings', 'audit.view': 'View audit log'
  };
  const ALL = Object.keys(PERMISSIONS);

  /* role → permission matrix (client requirements document) */
  const ROLE_PERMISSIONS = {
    admin: ALL,
    estimation: ['dashboard.view',
      'leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.delete',
      'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit', 'quotes.send', 'quotes.award',
      'projects.view', 'reports.view', 'reports.export'],
    engineer: ['dashboard.view',
      'leads.view', 'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit',
      'projects.view', 'expenses.view', 'expenses.create', 'payments.create', 'transfers.view', 'transfers.create',
      'reports.view', 'reports.export'],
    accountant: ['dashboard.view',
      'quotes.view', 'projects.view', 'projects.create', 'projects.open',
      'expenses.view', 'expenses.approve', 'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.approve',
      'transactions.view', 'transactions.approve', 'invoices.view', 'invoices.create', 'invoices.submit',
      'reports.view', 'reports.export', 'zoho.view', 'zoho.sync']
  };

  /* demo users — one per client-defined role */
  const USERS = [
    { id: 'U1', key: 'admin', name: 'John Doe', email: 'john.doe@richmount.ae', role: 'admin', roleLabel: 'Super Admin', branches: '*', engineerId: null },
    { id: 'U2', key: 'estimation', name: 'Aisha Rahman', email: 'aisha.rahman@richmount.ae', role: 'estimation', roleLabel: 'Estimation User', branches: ['dxb'], engineerId: null },
    { id: 'U3', key: 'engineer', name: 'Ali Khan', email: 'ali.khan@richmount.ae', role: 'engineer', roleLabel: 'Project Engineer', branches: ['dxb'], engineerId: 'e1' },
    { id: 'U4', key: 'accountant', name: 'Meera Joseph', email: 'meera.joseph@richmount.ae', role: 'accountant', roleLabel: 'Accountant', branches: '*', engineerId: null }
  ];

  /* permissions that depend on configuration (set by the data layer) */
  let dynamicPermissions = () => [];

  function permissionsFor(role) {
    const set = new Set(ROLE_PERMISSIONS[role] || []);
    dynamicPermissions(role).forEach(p => set.add(p));
    return set;
  }
  const userByKey = key => USERS.find(u => u.key === key) || null;

  /* ---------- session ---------- */
  const SESSION_KEY = 'rm.activeDemoUser';
  function readKey() {
    let k = storage.get(SESSION_KEY);
    try { k = k && JSON.parse(k); } catch (e) { /* plain string */ }
    if (!userByKey(k)) {
      let legacy = storage.get('rm.role');
      try { legacy = legacy && JSON.parse(legacy); } catch (e) { /* ignore */ }
      k = userByKey(legacy) ? legacy : 'admin'; // Super Admin only on the very first load
      storage.set(SESSION_KEY, JSON.stringify(k));
    }
    return k;
  }
  let activeKey = readKey();

  function session() {
    const u = userByKey(activeKey);
    return Object.assign({}, u, { permissions: permissionsFor(u.role) });
  }

  /* ---------- access checks ---------- */
  function can(perm, user) {
    const u = user || session();
    const perms = u.permissions || permissionsFor(u.role);
    return perms.has(perm);
  }
  /* branch + ownership scope used by the data layer and the UI */
  function inScope(record, user) {
    const u = user || session();
    if (!record) return false;
    if (u.branches !== '*' && record.branchId && !u.branches.includes(record.branchId)) return false;
    if (u.engineerId && record.engineerId && record.engineerId !== u.engineerId) return false;
    return true;
  }
  /* engineers only work leads until the quotation is sent (client workflow) */
  function canSeeLead(lead, user) {
    const u = user || session();
    if (!inScope(lead, u)) return false;
    if (u.engineerId && (lead.stage === 'sent' || lead.stage === 'awarded')) return false;
    return true;
  }
  const allowedBranchIds = (branches, user) => {
    const u = user || session();
    return branches.filter(b => u.branches === '*' || u.branches.includes(b.id)).map(b => b.id);
  };

  window.RM_AUTH = {
    storage, PERMISSIONS, ROLE_PERMISSIONS, USERS,
    session, can, inScope, canSeeLead, permissionsFor, userByKey, allowedBranchIds,
    setDynamicPermissions(fn) { dynamicPermissions = fn; },
    switchUser(key) {
      if (!userByKey(key)) throw new Error('Unknown demo user');
      activeKey = key;
      storage.set(SESSION_KEY, JSON.stringify(key));
      storage.set('rm.role', JSON.stringify(key));
      return session();
    }
  };
})();
