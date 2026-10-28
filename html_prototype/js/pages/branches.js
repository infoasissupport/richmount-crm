/* Branches page (branches.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$;
  let table;

  R.page({
    id: 'branches',
    permission: 'branches.view',
    title: 'Branches',
    sub: 'Every record carries its branch. Each branch has its own quotation format.',
    mount() {
      const inP = d => R.inRange(d, R.state.ctx.from, R.state.ctx.to);
      table = R.dataTable($('#branchesTable'), {
        title: 'All Branches', icon: 'building-2', exportName: 'Branches', compact: true,
        columns: [
          { label: 'Code', get: b => b.code, cls: 'strong' },
          { label: 'Branch', get: b => b.name },
          { label: 'Country', get: b => b.country },
          { label: 'Quote Format', get: b => b.quoteFormat, render: b => R.pill(b.quoteFormat, 'primary') },
          { label: 'Engineers', get: b => API.engineers.filter(e => e.branchId === b.id).length, cls: 'num' },
          { label: 'Leads (period)', get: b => API.leads.filter(l => l.branchId === b.id && inP(l.createdAt)).length, cls: 'num' },
          { label: 'Active Projects', get: b => API.projects.filter(p => p.branchId === b.id && R.OPEN_STATUSES.includes(p.status)).length, cls: 'num' },
          { label: 'Action', cls: 'ta-c', render: b => `<button class="btn btn-soft btn-sm" data-act="view">${R.state.filters.branch === b.id ? 'Current view' : 'View branch'}</button>` }
        ],
        rows: API.branches,
        onAction: (act, b) => R.setBranch(b.id)
      });
      R.dataTable($('#engineersTable'), {
        title: 'Engineers by Branch', icon: 'hard-hat', exportName: 'Engineers', compact: true,
        columns: [
          { label: 'Engineer', get: e => e.name, cls: 'strong' },
          { label: 'Branch', get: e => R.branchOf(e.branchId).name },
          { label: 'Email', get: e => e.email },
          { label: 'Zoho Petty Cash Account', get: e => e.pettyCash }
        ],
        rows: API.engineers
      });
    },
    render() { table.draw(); }
  });
})();
