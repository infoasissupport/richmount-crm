/* Users & Roles page (users.html) — users, role → permission matrix, audit log */
(function () {
  'use strict';
  const R = window.RM, API = R.API, AUTH = R.AUTH, $ = R.$, icon = R.icon, esc = R.esc;
  const ROLE_TONE = { admin: 'primary', accountant: 'purple', estimation: 'amber', engineer: 'blue' };

  /* permission groups shown in the matrix (labels come from AuthService) */
  const GROUPS = [
    ['Dashboard', ['dashboard.view']],
    ['Leads', ['leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.delete']],
    ['Quotations', ['quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit', 'quotes.approve', 'quotes.send', 'quotes.award']],
    ['Projects', ['projects.view', 'projects.create', 'projects.open', 'projects.edit']],
    ['Expenses & Cash', ['expenses.view', 'expenses.create', 'expenses.approve', 'payments.create', 'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.approve']],
    ['Finance', ['transactions.view', 'transactions.approve', 'invoices.view', 'invoices.create', 'invoices.submit']],
    ['Reports & Integrations', ['reports.view', 'reports.export', 'zoho.view', 'zoho.sync']],
    ['Administration', ['users.view', 'users.edit', 'roles.view', 'roles.edit', 'branches.view', 'branches.edit', 'settings.view', 'settings.edit', 'audit.view']]
  ];
  const branchText = u => u.branches === '*' ? 'All Branches' : u.branches.map(b => R.branchOf(b).name).join(', ');

  R.page({
    id: 'users',
    permission: 'users.view',
    title: 'Users & Roles',
    sub: 'Demo users, the role → permission matrix the whole application enforces, and the audit log.',
    mount() {
      R.dataTable($('#usersTable'), {
        title: 'Users', icon: 'users', exportName: 'Users', compact: true,
        columns: [
          { label: 'Name', get: u => u.name, cls: 'strong' },
          { label: 'Role', get: u => u.roleLabel, render: u => R.pill(u.roleLabel, ROLE_TONE[u.role]) },
          { label: 'Branch Access', get: branchText },
          { label: 'Email', get: u => u.email },
          { label: 'Permissions', get: u => AUTH.permissionsFor(u.role).size, cls: 'num' },
          { label: 'Login', cls: 'ta-c', render: u => u.key === R.me.key ? R.pill('Signed in', 'green') : `<button class="btn btn-ghost btn-sm" data-act="login">${icon('log-out', 'ic sm')}Sign in as</button>` }
        ],
        rows: AUTH.USERS,
        onAction: (act, u) => R.switchRole(u.key)
      }).el.insertAdjacentHTML('beforeend', `<p class="lock-note" style="margin-top:10px">${icon('info', 'ic sm')}Engineers (${API.engineers.map(e => esc(e.name)).join(', ')}) are records used for assignment; the Project Engineer demo login is Ali Khan.</p>`);

      const roles = AUTH.USERS;
      const tick = ok => ok ? `<span class="tick yes">${icon('check', 'ic sm')}</span>` : '<span class="tick no">—</span>';
      $('#matrix').innerHTML = `<header class="card-head"><h2><span class="head-ic">${icon('shield')}</span>Roles &amp; Permissions</h2></header>
        <div class="table-wrap page"><table class="table matrix"><thead><tr><th>Permission</th><th>Key</th>${roles.map(u => `<th class="ta-c">${esc(u.roleLabel)}</th>`).join('')}</tr></thead><tbody>
        ${GROUPS.map(g => `<tr class="group"><td colspan="${roles.length + 2}">${esc(g[0])}</td></tr>` + g[1].map(p => `<tr><td class="strong">${esc(AUTH.PERMISSIONS[p])}</td><td><code>${p}</code></td>${roles.map(u => `<td class="ta-c">${tick(AUTH.permissionsFor(u.role).has(p))}</td>`).join('')}</tr>`).join('')).join('')}
        </tbody></table></div>
        <p class="lock-note" style="margin-top:10px">${icon('info', 'ic sm')}Quotation approval is configurable in Settings (currently: ${API.settings.quoteApprover === 'admin' ? 'Super Admin only' : 'Estimation User and Super Admin'}). In production these checks are enforced by the Laravel API.</p>`;

      if (R.can('audit.view')) {
        R.dataTable($('#auditLog'), {
          title: 'Audit Log', icon: 'clock', exportName: 'Audit log', compact: true, pageSize: 20,
          columns: [
            { label: 'When', get: e => R.fmtDateTime(e.at) },
            { label: 'User', get: e => e.by, cls: 'strong' },
            { label: 'Area', get: e => e.kind },
            { label: 'Action', get: e => e.text },
            { label: 'Reference', get: e => e.projectId ? R.projLabel(e.projectId) : e.quoteId ? (API.quote(e.quoteId) || {}).number || '' : e.leadId ? (API.lead(e.leadId) || {}).leadNumber || '' : '' }
          ],
          rows: () => API.events.slice().sort((a, b) => b.at - a.at).slice(0, 1000)
        });
      } else $('#auditLog').remove();

      if (location.hash) R.focusSection($(location.hash === '#audit' ? '#auditLog' : '#matrix'));
    }
  });
})();
