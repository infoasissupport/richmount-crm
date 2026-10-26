/* Quotations page (quotations.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon, esc = R.esc;
  let table;

  /* the next workflow action for a quotation, shown inline */
  function nextAction(q) {
    const l = API.lead(q.leadId);
    switch (q.status) {
      case 'draft': case 'rejected': return R.can('quotes.edit') ? { label: 'Edit', icon: 'edit' } : null;
      case 'pending': return R.can('quotes.approve') ? { label: 'Review', icon: 'check-circle' } : null;
      case 'approved': return R.can('quotes.send') ? { label: 'Send', icon: 'send' } : null;
      case 'sent': return R.can('quotes.award') ? { label: 'Award', icon: 'award', run: () => R.awardQuote(q) } : null;
      case 'awarded':
        if (l.projectId) return { label: 'Project', icon: 'briefcase', run: () => R.go(`project.html?id=${l.projectId}`) };
        return R.can('projects.create') ? { label: 'Create Project', icon: 'briefcase', run: () => R.projectForm(l.id) } : null;
      default: return null;
    }
  }
  const stepCol = {
    label: 'Next Step', cls: 'ta-c',
    render: q => {
      const n = nextAction(q);
      return `<span class="row-actions">${n ? `<button class="btn btn-soft btn-sm" data-act="step">${icon(n.icon, 'ic sm')}${esc(n.label)}</button>` : ''}<button class="act view" data-act="open" title="Open" aria-label="Open">${icon('eye')}</button></span>`;
    }
  };

  R.page({
    id: 'quotes',
    permission: 'quotes.view',
    title: 'Quotations',
    sub: 'Draft, approve, send and award quotations. Each branch uses its own quotation format.',
    actions: [{ label: 'Create Quotation', icon: 'file-plus', perm: 'quotes.create', run: () => R.quoteForm() }],
    mount() {
      table = R.dataTable($('#quotesTable'), {
        title: 'Quotations', icon: 'file-text', exportName: 'Quotations', print: true,
        columns: R.COLS.quotes.concat([stepCol]),
        tabs: [
          { id: 'all', label: 'All', filter: () => true },
          { id: 'draft', label: 'Draft', filter: q => q.status === 'draft' || q.status === 'rejected' },
          { id: 'pending', label: 'Pending Approval', filter: q => q.status === 'pending' },
          { id: 'approved', label: 'Approved', filter: q => q.status === 'approved' },
          { id: 'sent', label: 'Sent', filter: q => q.status === 'sent' },
          { id: 'awarded', label: 'Awarded', filter: q => q.status === 'awarded' },
          { id: 'activation', label: 'Awaiting Project', filter: q => q.status === 'awarded' && !API.lead(q.leadId).projectId }
        ],
        tab: R.params.get('tab'),
        onTab: t => R.setParam('tab', t === 'all' ? null : t),
        rows: () => R.state.ctx.quotes.filter(q => R.inRange(q.date, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.date - a.date),
        onRow: q => R.go(`quote.html?id=${q.id}`),
        onAction: (act, q) => {
          const n = act === 'step' && nextAction(q);
          if (n && n.run) n.run(); else R.go(`quote.html?id=${q.id}`);
        }
      });
      if (R.params.get('new') && R.can('quotes.create')) R.quoteForm();
    },
    render() {
      R.renderKpis($('#kpis'), ['quotes', 'draftQuotes', 'sentQuotes', 'awarded']);
      table.draw();
    }
  });
})();
