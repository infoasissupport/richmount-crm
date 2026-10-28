/* Expenses page (expenses.html) — expenses, cash in hand, transfers, customer payments */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon;
  let table, cash, transfers, payments;

  /* transfer actions depend on role: accountant approves / edits, everyone else sees it locked */
  const transferActions = t => {
    if (t.status !== 'pending') return '<span style="color:var(--muted)">—</span>';
    if (R.can('transfers.approve')) return `<span class="row-actions"><button class="btn btn-success btn-sm" data-act="approve">Approve</button><button class="btn btn-ghost btn-sm" data-act="reject">Reject</button><button class="act edit" data-act="edit" title="Edit (Accountant)" aria-label="Edit">${icon('edit')}</button></span>`;
    return `<span class="lock-note">${icon('lock', 'ic sm')}Locked</span>`;
  };

  R.page({
    id: 'expenses',
    permission: 'expenses.view',
    title: 'Expenses',
    sub: 'Cash in hand, cash and card expenses, internal transfers and customer cash payments.',
    actions: [
      { label: 'New Transfer', icon: 'refresh', cls: 'btn-ghost', perm: 'transfers.create', run: () => R.transferForm() },
      { label: 'Customer Payment', icon: 'coins', cls: 'btn-ghost', perm: 'payments.create', run: () => R.paymentForm() },
      { label: 'Add Expense / Scan Receipt', icon: 'scan', perm: 'expenses.create', run: () => R.expenseForm() }
    ],
    mount() {
      const tab = R.params.get('tab');
      table = R.dataTable($('#expensesTable'), {
        title: 'Expense History', icon: 'receipt', exportName: 'Expenses', print: true,
        columns: R.COLS.expenses.concat([{ label: 'Description', get: e => e.description || '—' }]),
        tabs: [
          { id: 'all', label: 'All', filter: () => true },
          { id: 'cash', label: 'Cash', filter: e => e.type === 'cash' },
          { id: 'card', label: 'Card', filter: e => e.type === 'card' },
          { id: 'pending', label: 'Pending', filter: e => e.status === 'pending' },
          { id: 'cleared', label: 'Approved / Cleared', filter: e => e.status === 'approved' },
          { id: 'rejected', label: 'Rejected', filter: e => e.status === 'rejected' }
        ],
        tab: ['cash', 'card', 'pending', 'cleared', 'rejected'].includes(tab) ? tab : 'all',
        rows: () => R.state.ctx.expenses.filter(e => R.inRange(e.date, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.date - a.date || (b.createdAt - a.createdAt)),
        onRow: e => e.projectId && R.go(`project.html?id=${e.projectId}`)
      });
      cash = R.dataTable($('#cashTable'), {
        title: 'Cash in Hand by Engineer', icon: 'coins', exportName: 'Cash in hand', compact: true,
        intro: '<p class="lock-note" style="margin-bottom:10px">Opening cash + transfers in + customer payments − cash expenses − transfers out. Click a row for the full ledger.</p>',
        columns: [
          { label: 'Engineer', get: e => e.name, cls: 'strong' },
          { label: 'Branch', get: e => R.branchOf(e.branchId).short },
          { label: 'Zoho Petty Cash', get: e => e.pettyCash },
          { label: 'Balance', get: e => API.cashBalance(e.id), render: e => `<b>${R.fmtAED2(API.cashBalance(e.id))}</b>`, cls: 'num' },
          { label: '', cls: 'ta-c', render: () => `<button class="act view" data-act="ledger" title="Ledger" aria-label="View ledger">${icon('eye')}</button>` }
        ],
        rows: () => R.state.ctx.engineers,
        onRow: e => R.cashLedgerModal(e.id),
        onAction: (act, e) => R.cashLedgerModal(e.id)
      });
      transfers = R.dataTable($('#transfersTable'), {
        title: 'Internal Transfers', icon: 'refresh', exportName: 'Internal transfers', compact: true, pageSize: 15,
        intro: `<p class="lock-note" style="margin-bottom:10px">${icon('lock', 'ic sm')}Create Transfer → Pending → Accountant Approval → Approved → Cash in Hand updated. Submitted transfers can only be edited by the Accountant.</p>`,
        toolbar: R.can('transfers.create') ? `<button class="btn btn-primary btn-sm" data-newtr>${icon('plus', 'ic sm')}New Transfer</button>` : '',
        columns: R.COLS.transfers.concat([{ label: 'Action', cls: 'ta-c', render: transferActions }]),
        tabs: [{ id: 'pending', label: 'Pending', filter: t => t.status === 'pending' }, { id: 'approved', label: 'Approved', filter: t => t.status === 'approved' }, { id: 'rejected', label: 'Rejected', filter: t => t.status === 'rejected' }, { id: 'all', label: 'All', filter: () => true }],
        rows: () => R.state.ctx.transfers.filter(t => t.status === 'pending' || R.inRange(t.date, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.date - a.date),
        onAction: (act, t) => (act === 'approve' ? R.approveTransfer(t) : act === 'reject' ? R.rejectTransfer(t) : R.editTransferForm(t.id))
      });
      payments = R.dataTable($('#paymentsTable'), {
        title: 'Customer Payments Received by Engineers', icon: 'coins', exportName: 'Customer payments', compact: true, pageSize: 10,
        toolbar: R.can('payments.create') ? `<button class="btn btn-primary btn-sm" data-newpay>${icon('plus', 'ic sm')}Record Payment</button>` : '',
        columns: [
          { label: 'Ref', get: p => p.ref, cls: 'strong' }, { label: 'Date', get: p => R.fmtDate(p.date) }, { label: 'Engineer', get: p => R.engName(p.engineerId) },
          { label: 'Customer', get: p => p.customer }, { label: 'Project', get: p => R.projLabel(p.projectId) }, { label: 'Note', get: p => p.note || '—' },
          { label: 'Amount', get: p => p.amount, render: p => R.fmtAED(p.amount), cls: 'num' }
        ],
        rows: () => R.state.ctx.payments.filter(p => R.inRange(p.date, R.state.ctx.from, R.state.ctx.to)).sort((a, b) => b.date - a.date),
        empty: 'No customer cash payments in this period.',
        onRow: p => R.go(`project.html?id=${p.projectId}`)
      });
      const nt = $('[data-newtr]', $('#transfersTable'));
      if (nt) nt.addEventListener('click', () => R.transferForm());
      const np = $('[data-newpay]', $('#paymentsTable'));
      if (np) np.addEventListener('click', () => R.paymentForm());
      if (tab === 'cashinhand') R.focusSection($('#cashTable'));
      if (tab === 'transfers') R.focusSection($('#transfersTable'));
      if (R.params.get('new') && R.can('expenses.create')) R.expenseForm();
    },
    render() {
      R.renderKpis($('#kpis'), ['cashInHand', 'cashExpense', 'cardExpense', 'transfers']);
      table.draw();
      cash.draw();
      transfers.draw();
      payments.draw();
    }
  });
})();
