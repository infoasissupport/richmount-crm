/* Project detail page (project.html?id=…) — full lifecycle */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, $$ = R.$$, esc = R.esc, icon = R.icon;
  const id = R.params.get('id');
  const STEPS = ['awarded', 'popending', 'open', 'inprogress', 'completed', 'invoicepending', 'closed'];
  let expTable, invTable;

  const setF = (host, k, v) => { const el = $(`[data-f=${k}]`, host); if (el) el.textContent = v == null || v === '' ? '—' : v; };

  function actions(p) {
    const a = [], st = p.status;
    if (!API.hasPO(p) && st !== 'closed' && R.can('projects.open')) a.push({ label: 'Add PO', icon: 'file-plus', cls: st === 'popending' ? 'btn-primary' : 'btn-ghost', run: () => R.poForm(id) });
    if (st === 'open' && R.can('projects.edit')) a.push({ label: 'Start Work', icon: 'hard-hat', cls: 'btn-primary', run: () => R.act(() => API.setProjectStatus(id, 'inprogress'), `${p.projectNumber} is now In Progress`) });
    if (st === 'inprogress' && R.can('projects.edit')) {
      a.push({ label: 'Put On Hold', icon: 'clock', cls: 'btn-ghost', run: () => R.promptDialog({ title: 'Put project on hold?', label: 'Reason', ok: 'Put On Hold' }).then(r => r && R.act(() => API.setProjectStatus(id, 'onhold', r), `${p.projectNumber} put on hold`, 'info')) });
      a.push({ label: 'Complete Work', icon: 'check-circle', cls: 'btn-primary', run: () => R.completionForm(id) });
    }
    if (st === 'onhold' && R.can('projects.edit')) a.push({ label: 'Resume Work', icon: 'refresh', cls: 'btn-primary', run: () => R.act(() => API.setProjectStatus(id, 'inprogress', 'resumed'), `${p.projectNumber} resumed`) });
    if (R.can('expenses.create')) a.push(R.OPEN_STATUSES.includes(st)
      ? { label: 'Add Expense', icon: 'wallet', cls: 'btn-ghost', run: () => R.expenseForm(id) }
      : { label: 'Add Expense', icon: 'wallet', cls: 'btn-ghost', disabled: true, title: p.status === 'popending' ? 'Expense entry is available after the project is officially opened.' : 'Expenses can only be recorded while the project is open or in progress.' });
    if (R.OPEN_STATUSES.includes(st) && R.can('payments.create')) a.push({ label: 'Customer Payment', icon: 'coins', cls: 'btn-ghost', run: () => R.paymentForm() });
    const noPO = API.hasPO(p) ? {} : { disabled: true, title: 'Record the client PO before raising an invoice.' };
    if (st === 'inprogress' && R.can('invoices.create')) a.push(Object.assign({ label: 'Progress Invoice', icon: 'invoice', cls: 'btn-ghost', run: () => R.invoiceForm(id) }, noPO));
    if (st === 'completed' && R.can('invoices.create')) a.push(Object.assign({ label: 'Create Final Invoice', icon: 'invoice', cls: 'btn-primary', run: () => R.invoiceForm(id) }, noPO));
    if (st === 'invoicepending') {
      const draft = API.invoices.find(i => i.projectId === id && i.final && i.status === 'draft');
      if (draft) a.push({ label: R.can('invoices.submit') ? 'Review & Close Project' : 'View Final Invoice', icon: 'invoice', cls: 'btn-primary', run: () => R.invoicePreview(draft.id) });
    }
    return a;
  }

  function banner(p) {
    const msgs = {
      popending: ['Project created — waiting for the client PO. The Accountant records the PO to open the project.', ''],
      open: ['Project is open. Expenses can now be recorded. Start work when the site mobilises.', ''],
      inprogress: ['Work in progress. Submit the work completion report when the works are handed over.', ''],
      onhold: ['Project is on hold. Expenses are blocked until work resumes.', 'bad'],
      completed: ['Work completed — the Accountant must raise the final invoice.', ''],
      invoicepending: ['Final invoice created — submit it to close the project.', ''],
      closed: [`Project closed on ${R.fmtDate(p.closedAt)} after the final invoice.`, 'ok']
    };
    let m = msgs[p.status];
    if (m && p.emailApproval && !API.hasPO(p) &&p.status !== 'closed') {
      const a = p.emailApproval;
      m = [`${m[0]} Work was approved by email${a.by ? ` from ${a.by}` : ''} on ${R.fmtDate(a.date)} — the client PO is still awaited. Invoices can be raised once the PO is recorded.`, m[1]];
    }
    const el = $('#projBanner');
    el.hidden = !m;
    if (m) { el.className = 'info-box ' + m[1]; el.innerHTML = `${icon(m[1] === 'ok' ? 'check-circle' : m[1] === 'bad' ? 'alert' : 'info')}<span>${esc(m[0])}</span>`; }
  }

  R.page({
    id: 'projects',
    permission: 'projects.view',
    title: 'Project',
    crumbs: [['Projects', 'projects.html'], [API.project(id) ? API.project(id).projectNumber : 'Project']],
    allow: () => { const p = API.project(id); return !!p && R.AUTH.inScope(p, R.me); },
    deniedTitle: 'Project not available',
    deniedText: 'This project was not found, or it is outside your branch / assignment access.',
    mount() {
      expTable = R.dataTable($('#projExpenses'), {
        title: 'Project Expenses', icon: 'receipt', exportName: 'Project expenses', compact: true, pageSize: 15,
        toolbar: R.can('expenses.create') && R.OPEN_STATUSES.includes(API.project(id).status) ? `<button class="btn btn-primary btn-sm" data-newexp>${icon('plus', 'ic sm')}Add Expense</button>` : '',
        columns: R.COLS.expenses.filter(c => c.label !== 'Project').concat([{ label: 'Description', get: e => e.description || '—' }]).concat(R.can('expenses.approve') ? [{
          label: 'Action', cls: 'ta-c', render: e => e.status === 'pending' ? `<span class="row-actions"><button class="btn btn-success btn-sm" data-act="approve">Approve</button><button class="btn btn-ghost btn-sm" data-act="reject">Reject</button><button class="act edit" data-act="edit" title="Edit" aria-label="Edit">${icon('edit')}</button></span>` : '—'
        }] : []),
        tabs: [{ id: 'all', label: 'All', filter: () => true }, { id: 'pending', label: 'Pending', filter: e => e.status === 'pending' }, { id: 'cleared', label: 'Approved / Cleared', filter: e => e.status === 'approved' }, { id: 'rejected', label: 'Rejected', filter: e => e.status === 'rejected' }],
        rows: () => API.expenses.filter(e => e.projectId === id).slice().sort((a, b) => b.date - a.date),
        empty: 'No expenses recorded for this project yet.',
        onAction: (act, e) => (act === 'approve' ? R.approveExpense(e) : act === 'reject' ? R.rejectExpense(e) : R.editExpenseForm(e.id))
      });
      const ne = $('[data-newexp]', $('#projExpenses'));
      if (ne) ne.addEventListener('click', () => R.expenseForm(id));
      invTable = R.dataTable($('#projInvoices'), {
        title: 'Invoices', icon: 'invoice', exportName: 'Project invoices', compact: true, pageSize: 10,
        columns: R.COLS.invoices.filter(c => !['Project', 'Quote', 'VAT'].includes(c.label)).concat([{ label: '', cls: 'ta-c', render: () => `<button class="act view" data-act="view" title="Preview">${icon('eye')}</button>` }]),
        rows: () => API.invoices.filter(i => i.projectId === id).slice().sort((a, b) => b.date - a.date),
        empty: 'No invoices yet.',
        onRow: i => R.invoicePreview(i.id),
        onAction: (act, i) => R.invoicePreview(i.id)
      });
    },
    render() {
      const p = API.project(id);
      const lead = API.lead(p.leadId), q = API.quote(p.quoteId);
      const head = $('#pageHead');
      $('h1', head).textContent = `${p.projectNumber} · ${p.name}`;
      $('p', head).textContent = `${lead.company} · ${R.branchOf(p.branchId).name}`;
      const acts = actions(p);
      const box = $('.page-actions', head);
      box.innerHTML = R.projectPill(p) + acts.map((x, i) => `<button class="btn ${x.cls}" data-pa2="${i}"${x.disabled ? ` disabled title="${esc(x.title)}"` : ''}>${icon(x.icon)}${esc(x.label)}</button>`).join('');
      $$('[data-pa2]', box).forEach(b => b.addEventListener('click', () => acts[+b.dataset.pa2].run()));

      const st = p.status === 'onhold' ? 'inprogress' : p.status;
      const idx = STEPS.indexOf(st);
      $$('#projStepper li').forEach((li, i) => {
        li.className = i < idx ? 'done' : i === idx ? (p.status === 'onhold' ? 'current warn' : 'current') : '';
        $('span', li).innerHTML = i < idx || (i === idx && st === 'closed') ? icon('check', 'ic xs') : String(i + 1);
      });
      banner(p);

      const ex = API.expenses.filter(e => e.projectId === id && e.status !== 'rejected');
      const inv = API.invoices.filter(i => i.projectId === id);
      const invoiced = R.sum(inv.filter(i => i.status === 'submitted'), i => i.amount);
      const stats = $('#projStats');
      setF(stats, 'value', R.fmtAEDCompact(p.poValue || p.value));
      setF(stats, 'valueSub', p.poNumber ? `PO ${p.poNumber}` : API.hasPO(p) ? 'PO received' : p.emailApproval ? 'Email approval · PO awaited' : 'PO pending');
      setF(stats, 'expenses', R.fmtAED(R.sum(ex, e => e.amount)));
      setF(stats, 'expensesSub', `${ex.length} transactions`);
      setF(stats, 'invoiced', R.fmtAEDCompact(invoiced));
      setF(stats, 'invoicedSub', `${Math.min(100, Math.round(invoiced / (p.value || 1) * 100))}% of contract value`);
      setF(stats, 'pending', String(ex.filter(e => e.status === 'pending').length));

      const kv = $('#projKv');
      setF(kv, 'projectNumber', p.projectNumber);
      setF(kv, 'customer', `${lead.company}${lead.contact ? ' · ' + lead.contact : ''}`);
      const qa = $('[data-f=quote]', kv); qa.textContent = q ? `${q.number} · ${R.fmtAED2(q.total)}` : p.quoteNumber; qa.href = q ? `quote.html?id=${q.id}` : '#';
      const la = $('[data-f=lead]', kv); la.textContent = `${lead.leadNumber} · ${lead.name}`; la.href = `lead.html?id=${lead.id}`;
      setF(kv, 'branch', `${R.branchOf(p.branchId).name} (${R.branchOf(p.branchId).code})`);
      setF(kv, 'engineer', R.engName(p.engineerId));
      setF(kv, 'rfq', p.rfqNumber || lead.rfqNumber || '—');
      setF(kv, 'po', API.hasPO(p) ? `${p.poNumber || 'No PO number'} · ${R.fmtDate(p.poDate)} · ${R.fmtAED(p.poValue || p.value)}` : p.emailApproval ? `Awaited — work approved by email on ${R.fmtDate(p.emailApproval.date)}` : 'Not received yet');
      setF(kv, 'start', R.fmtDate(p.startDate));
      setF(kv, 'completed', p.completedAt ? R.fmtDate(p.completedAt) : '');
      setF(kv, 'closed', p.closedAt ? R.fmtDate(p.closedAt) : '');
      const zl = API.zohoLog.filter(z => z.target && z.target.kind === 'project' && z.target.id === id).pop();
      $('[data-f=zoho]', kv).innerHTML = !zl ? R.pill('Synced', 'green') : zl.status === 'syncing' ? R.pill('Syncing…', 'blue') : zl.status === 'failed' ? R.pill('Sync failed', 'red') : zl.status === 'queued' ? R.pill('Queued', 'amber') : R.pill(`Synced · ${R.fmtTime(zl.time)}`, 'green');
      $('#projStatus').innerHTML = R.projectPill(p);

      $('#projDesc').textContent = p.description || 'No description added.';
      $('#completionWrap').hidden = !p.completionNotes;
      $('#projCompletion').textContent = p.completionNotes || '';
      const docs = $('#projDocs'), tpl = $('#tplDoc');
      docs.innerHTML = '';
      if (!p.documents.length) docs.innerHTML = '<li class="muted">No documents</li>';
      p.documents.forEach(d => { const li = tpl.content.firstElementChild.cloneNode(true); $('[data-f=name]', li).textContent = d.name; $('[data-f=kind]', li).textContent = d.kind; docs.appendChild(li); });

      expTable.draw();
      invTable.draw();
      R.renderTimeline($('#projTimeline'), API.eventsFor('projectId', id));
    }
  });
})();
