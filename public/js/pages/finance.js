/* Finance page (finance.html) — accountant approval center + invoices */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon;
  let approvals, transfers, invoices;

  const decisionCol = (approveFn, rejectFn) => ({
    label: 'Action', cls: 'ta-c',
    render: x => x.status !== 'pending' ? (x.rejectReason ? `<small style="color:var(--muted)" title="${R.esc(x.rejectReason)}">${R.esc(x.rejectReason)}</small>` : '<span style="color:var(--muted)">—</span>')
      : R.can('expenses.approve') ? `<span class="row-actions"><button class="btn btn-success btn-sm" data-act="approve">Approve</button><button class="btn btn-ghost btn-sm" data-act="reject">Reject</button><button class="act edit" data-act="edit" title="Edit" aria-label="Edit">${icon('edit')}</button></span>`
        : '<span style="color:var(--muted)">Accountant only</span>'
  });

  R.page({
    id: 'finance',
    permission: 'transactions.view',
    title: 'Finance',
    sub: 'Approve or reject submitted transactions. Approved items are cleared and posted to Zoho Books (demo integration).',
    actions: [{ label: 'Create Invoice', icon: 'invoice', perm: 'invoices.create', run: () => R.invoiceForm() }],
    mount() {
      approvals = R.dataTable($('#approvalsTable'), {
        title: 'Expense Approvals', icon: 'check-circle', exportName: 'Expense approvals',
        columns: R.COLS.expenses.concat([{ label: 'Receipt', get: e => e.receipt || '—', render: e => e.receipt ? `<span class="pill pill-gray">${icon('file-text')}${R.esc(e.receipt)}</span>` : '—' }, decisionCol()]),
        tabs: [
          { id: 'pending', label: 'Pending', filter: e => e.status === 'pending' },
          { id: 'syncing', label: 'Approved · Syncing', filter: e => e.status === 'approved' && e.zoho !== 'synced' },
          { id: 'cleared', label: 'Cleared', filter: e => e.status === 'approved' && e.zoho === 'synced' },
          { id: 'rejected', label: 'Rejected', filter: e => e.status === 'rejected' }
        ],
        rows: () => R.state.ctx.expenses.slice().sort((a, b) => b.date - a.date),
        onRow: e => e.projectId && R.go(`project.html?id=${e.projectId}`),
        onAction: (act, e) => (act === 'approve' ? R.approveExpense(e) : act === 'reject' ? R.rejectExpense(e) : R.editExpenseForm(e.id))
      });
      transfers = R.dataTable($('#transferApprovals'), {
        title: 'Transfer Approvals', icon: 'refresh', exportName: 'Transfer approvals', compact: true, pageSize: 15,
        columns: R.COLS.transfers.concat([{ label: 'Requested By', get: t => t.createdBy }, Object.assign(decisionCol(), {
          render: t => t.status !== 'pending' ? '<span style="color:var(--muted)">—</span>' : R.can('transfers.approve')
            ? `<span class="row-actions"><button class="btn btn-success btn-sm" data-act="approve">Approve</button><button class="btn btn-ghost btn-sm" data-act="reject">Reject</button><button class="act edit" data-act="edit" title="Edit" aria-label="Edit">${icon('edit')}</button></span>` : '<span style="color:var(--muted)">Accountant only</span>'
        })]),
        tabs: [{ id: 'pending', label: 'Pending', filter: t => t.status === 'pending' }, { id: 'approved', label: 'Approved', filter: t => t.status === 'approved' }, { id: 'rejected', label: 'Rejected', filter: t => t.status === 'rejected' }],
        rows: () => R.state.ctx.transfers.slice().sort((a, b) => b.date - a.date),
        onAction: (act, t) => (act === 'approve' ? R.approveTransfer(t) : act === 'reject' ? R.rejectTransfer(t) : R.editTransferForm(t.id))
      });
      invoices = R.dataTable($('#invoicesTable'), {
        title: 'Invoices', icon: 'invoice', exportName: 'Invoices', print: true,
        toolbar: R.can('invoices.create') ? `<button class="btn btn-primary btn-sm" data-new>${icon('plus', 'ic sm')}Create Invoice</button>` : '',
        columns: R.COLS.invoices.concat([{ label: '', cls: 'ta-c', render: i => `<button class="act view" data-act="view" title="Preview" aria-label="Preview">${icon('eye')}</button>` }]),
        tabs: [{ id: 'all', label: 'All', filter: () => true }, { id: 'draft', label: 'Draft', filter: i => i.status === 'draft' }, { id: 'submitted', label: 'Submitted', filter: i => i.status === 'submitted' }],
        rows: () => R.state.ctx.invoices.filter(i => i.status === 'draft' || R.inRange(i.date, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.date - a.date),
        onRow: i => R.invoicePreview(i.id),
        onAction: (act, i) => R.invoicePreview(i.id)
      });
      const nb = $('[data-new]', $('#invoicesTable'));
      if (nb) nb.addEventListener('click', () => R.invoiceForm());
      if (R.params.get('tab') === 'invoices') R.focusSection($('#invoicesTable'));
      if (R.params.get('tab') === 'transfers') R.focusSection($('#transferApprovals'));
    },
    render() {
      R.renderKpis($('#kpis'), ['pendingApprovals', 'expenses', 'invoiced', 'awaitingActivation']);
      approvals.draw();
      transfers.draw();
      invoices.draw();
    }
  });
})();
