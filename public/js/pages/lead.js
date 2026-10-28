/* Lead detail page (lead.html?id=…) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, $$ = R.$$, esc = R.esc, icon = R.icon;
  const id = R.params.get('id');
  let quotesTable;

  const fill = (host, k, v) => { const el = $(`[data-f=${k}]`, host); if (el) el.textContent = v == null || v === '' ? '—' : v; };
  function fileList(host, names) {
    host.innerHTML = '';
    const tpl = $('#tplFile');
    if (!names || !names.length) { host.innerHTML = '<li class="muted">No attachments</li>'; return; }
    names.forEach(n => { const li = tpl.content.firstElementChild.cloneNode(true); $('[data-f=name]', li).textContent = n; host.appendChild(li); });
  }

  function actions(l) {
    const a = [];
    const ns = R.nextStep(l);
    if (R.can('leads.edit')) a.push({ label: 'Edit', icon: 'edit', cls: 'btn-ghost', run: () => R.leadForm(l) });
    if (R.can('leads.assign')) a.push({ label: 'Assign', icon: 'user', cls: 'btn-ghost', run: () => R.assignForm(l.id) });
    if (R.can('leads.delete') && !l.quoteId) a.push({ label: 'Delete', icon: 'x', cls: 'btn-ghost', run: () => R.deleteLead(l, () => { R.flash(`${l.leadNumber} deleted`, 'info'); R.go('leads.html'); }) });
    if (ns) a.push(Object.assign({ cls: 'btn-primary' }, ns));
    return a;
  }

  R.page({
    id: 'leads',
    permission: 'leads.view',
    title: 'Lead',
    crumbs: [['Lead Management', 'leads.html'], [API.lead(id) ? API.lead(id).leadNumber : 'Lead']],
    allow: () => { const l = API.lead(id); return !!l && R.AUTH.canSeeLead(l, R.me); },
    deniedTitle: 'Lead not available',
    deniedText: 'This lead was not found, or it is outside your branch / assignment access.',
    mount() {
      quotesTable = R.dataTable($('#leadQuotes'), {
        title: 'Quotations', icon: 'file-text', exportName: 'Lead quotations', compact: true, pageSize: 10,
        toolbar: R.can('quotes.create') ? `<button class="btn btn-primary btn-sm" data-newq>${icon('file-plus', 'ic sm')}Create Quotation</button>` : '',
        columns: [
          { label: 'Quote No', get: q => q.number, cls: 'strong' },
          { label: 'Date', get: q => R.fmtDate(q.date) },
          { label: 'Total', get: q => q.total, render: q => R.fmtAED(q.total), cls: 'num' },
          { label: 'Status', get: q => R.quoteStatus(q.status).name, render: R.quotePill },
          { label: '', cls: 'ta-c', render: () => `<button class="act view" data-act="open" title="Open">${icon('eye')}</button>` }
        ],
        rows: () => API.quotesForLead(id).slice().sort((a, b) => b.date - a.date),
        empty: 'No quotations yet — create one from this lead.',
        onRow: q => R.go(`quote.html?id=${q.id}`),
        onAction: (act, q) => R.go(`quote.html?id=${q.id}`)
      });
      const nb = $('[data-newq]', $('#leadQuotes'));
      if (nb) nb.addEventListener('click', () => R.quoteForm(id));
    },
    render() {
      const l = API.lead(id);
      if (!l) { R.go('leads.html'); return; }
      const head = $('#pageHead');
      $('h1', head).textContent = `${l.leadNumber} · ${l.name}`;
      $('p', head).textContent = l.subject;
      const acts = actions(l);
      const box = $('.page-actions', head);
      box.innerHTML = R.leadPill(l) + acts.map((x, i) => `<button class="btn ${x.cls}" data-la="${i}">${icon(x.icon)}${esc(x.label)}</button>`).join('');
      $$('[data-la]', box).forEach(b => b.addEventListener('click', () => acts[+b.dataset.la].run()));

      const order = ['new', 'draft', 'sent', 'awarded', 'project'];
      const idx = l.projectId ? 4 : order.indexOf(l.stage);
      $$('#leadStepper li').forEach((li, i) => {
        li.className = i < idx ? 'done' : i === idx ? 'current' : '';
        $('span', li).innerHTML = i < idx ? icon('check', 'ic xs') : String(i + 1);
      });

      const b = R.branchOf(l.branchId), kv = $('#leadKv');
      fill(kv, 'leadNumber', l.leadNumber);
      fill(kv, 'leadDate', R.fmtDate(l.leadDate || l.createdAt));
      fill(kv, 'company', l.company);
      fill(kv, 'rfqNumber', l.rfqNumber);
      fill(kv, 'subject', l.subject);
      fill(kv, 'department', R.deptName(l.department));
      fill(kv, 'branch', `${b.name} (${b.code}) · ${b.quoteFormat}`);
      fill(kv, 'engineer', R.engName(l.engineerId));
      fill(kv, 'source', l.source);
      fill(kv, 'estValue', l.estValue ? R.fmtAED(l.estValue) : '');
      $('#leadStatus').innerHTML = R.leadPill(l);
      const ck = $('#contactKv');
      fill(ck, 'contact', l.contact);
      fill(ck, 'phone', l.phone);
      fill(ck, 'email', l.email);
      $('#leadNotes').textContent = l.notes || 'No notes.';
      fileList($('#leadAtt'), l.attachments);

      quotesTable.draw();
      R.renderTimeline($('#leadTimeline'), API.eventsFor('leadId', id));
    }
  });
})();
