/* Lead Management page (leads.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, esc = R.esc, icon = R.icon;
  let table;

  /* same columns and cell design as the dashboard's Recent Leads (reports keep the plain R.COLS.leads) */
  const COLUMNS = [
    { label: 'Lead No', get: l => l.leadNumber },
    { label: 'Lead Name', get: l => l.name, cls: 'strong', render: l => `<span class="cell-who">${R.avatar(l.name)}<span class="cell-text">${esc(l.name)}</span></span>` },
    { label: 'Company', get: l => l.company, render: l => `<span class="cell-text">${esc(l.company)}<small>${esc(R.deptName(l.department))}</small></span>` },
    { label: 'Assigned To', get: l => R.engName(l.engineerId), render: l => `<span class="cell-who">${R.avatar(R.engName(l.engineerId))}<span class="cell-text">${esc(R.engName(l.engineerId))}<small>Project Engineer</small></span></span>` },
    { label: 'Source', get: l => l.source, render: l => R.sourcePill(l.source) },
    { label: 'Status', get: l => API.leadStatus(l), render: R.leadPill },
    { label: 'Date', headIcon: 'arrow-down', get: l => R.fmtDate(l.createdAt), render: l => `<span class="cell-date">${icon('calendar', 'ic sm')}${R.fmtDate(l.createdAt)}</span>` },
    { label: 'Action', cls: 'ta-c', render: () => `<span class="row-actions">
      <button class="act view" data-act="view" title="View" aria-label="View">${icon('eye')}</button>
      <button class="act edit" data-act="edit" title="Edit" aria-label="Edit"${R.can('leads.edit') ? '' : ' disabled'}>${icon('edit')}</button>
      <button class="act" data-act="menu" title="More" aria-label="More actions">${icon('more-vertical')}</button></span>` }
  ];

  R.page({
    id: 'leads',
    permission: 'leads.view',
    title: 'Lead Management',
    actions: [
      { label: 'Export CSV', icon: 'download', cls: 'btn-ghost', run: () => table.exportCSV() },
      { label: 'Print', icon: 'printer', cls: 'btn-ghost', run: () => table.print() },
      { label: 'Assign Engineers', icon: 'user', cls: 'btn-ghost', perm: 'leads.assign', run: () => R.assignBoard() },
      { label: 'New Lead', icon: 'plus', perm: 'leads.create', run: () => R.leadForm() }
    ],
    mount(c) {
      table = R.dataTable($('#leadsTable'), {
        title: 'All Leads', icon: 'file-user', exportName: 'Leads', print: true, headTools: true,
        columns: COLUMNS, rowNumbers: 'Sl No',
        tabs: [
          { id: 'all', label: 'All Status', filter: () => true },
          { id: 'new', label: 'New / Unquoted', filter: l => l.stage === 'new' && !l.lost },
          { id: 'draft', label: 'Draft Quote', filter: l => l.stage === 'draft' },
          { id: 'sent', label: 'Quote Sent', filter: l => l.stage === 'sent' },
          { id: 'awarded', label: 'Awarded', filter: l => l.stage === 'awarded' },
          { id: 'lost', label: 'Lost', filter: l => l.lost && l.stage === 'new' }
        ],
        tabSelect: true, period: l => l.createdAt, searchPlaceholder: 'Search leads by name, company, source...',
        tab: R.params.get('assign') ? 'new' : R.params.get('tab'),
        onTab: t => R.setParam('tab', t === 'all' ? null : t),
        rows: () => R.state.ctx.leads.filter(l => R.inRange(l.createdAt, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.createdAt - a.createdAt),
        onRow: l => R.go(`lead.html?id=${l.id}`),
        onAction: (act, l, redraw, btn) => act === 'menu' ? R.leadMenu(btn, l.id) : act === 'edit' ? R.leadForm(l) : R.go(`lead.html?id=${l.id}`)
      });
      if (R.params.get('new') && R.can('leads.create')) R.leadForm();
      if (R.params.get('assign')) R.assignBoard();
    },
    render() {
      R.renderKpis($('#kpis'), ['leads', 'draftQuotes', 'sentQuotes', 'awarded']);
      table.draw();
    }
  });
})();
