/* Lead Management page (leads.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$;
  let table;

  R.page({
    id: 'leads',
    permission: 'leads.view',
    title: 'Lead Management',
    sub: 'Create, assign and track leads from first contact to awarded quotation.',
    actions: [
      { label: 'Assign Engineers', icon: 'user', cls: 'btn-ghost', perm: 'leads.assign', run: () => R.assignBoard() },
      { label: 'New Lead', icon: 'plus', perm: 'leads.create', run: () => R.leadForm() }
    ],
    mount(c) {
      table = R.dataTable($('#leadsTable'), {
        title: 'All Leads', icon: 'file-user', exportName: 'Leads', print: true,
        columns: R.COLS.leads.concat([R.viewMoreCol]),
        tabs: [
          { id: 'all', label: 'All', filter: () => true },
          { id: 'new', label: 'New / Unquoted', filter: l => l.stage === 'new' && !l.lost },
          { id: 'draft', label: 'Draft Quote', filter: l => l.stage === 'draft' },
          { id: 'sent', label: 'Quote Sent', filter: l => l.stage === 'sent' },
          { id: 'awarded', label: 'Awarded', filter: l => l.stage === 'awarded' },
          { id: 'lost', label: 'Lost', filter: l => l.lost && l.stage === 'new' }
        ],
        tab: R.params.get('assign') ? 'new' : R.params.get('tab'),
        onTab: t => R.setParam('tab', t === 'all' ? null : t),
        rows: () => R.state.ctx.leads.filter(l => R.inRange(l.createdAt, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.createdAt - a.createdAt),
        onRow: l => R.go(`lead.html?id=${l.id}`),
        onAction: (act, l, redraw, btn) => act === 'menu' ? R.leadMenu(btn, l.id) : R.go(`lead.html?id=${l.id}`)
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
