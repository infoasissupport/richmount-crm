/* Reports page (reports.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, $$ = R.$$, icon = R.icon, esc = R.esc;
  let selected = R.params.get('report') || 'leads';
  let preview;

  function reports() {
    const c = R.state.ctx;
    const inP = d => R.inRange(d, c.from, c.to);
    const live = e => e.status !== 'rejected';
    return [
      { id: 'leads', title: 'Leads Report', icon: 'users', desc: 'All leads created in the period', cols: R.COLS.leads, rows: () => c.leads.filter(l => inP(l.createdAt)) },
      { id: 'quotes', title: 'Quotations Report', icon: 'file-text', desc: 'Quotes with stage and value', cols: R.COLS.quotes, rows: () => c.quotes.filter(q => inP(q.date)) },
      { id: 'projects', title: 'Projects Report', icon: 'briefcase', desc: 'Projects with PO and status', cols: R.COLS.projects, rows: () => c.projects.filter(p => p.startDate <= c.to) },
      { id: 'cash', title: 'Total Cash Transaction Report', icon: 'wallet', desc: 'Cash expenses by engineer and project', cols: R.COLS.expenses, rows: () => c.expenses.filter(e => e.type === 'cash' && inP(e.date)) },
      { id: 'card', title: 'Total Card Transaction Report', icon: 'credit-card', desc: 'Card expenses with card reference', cols: R.COLS.expenses, rows: () => c.expenses.filter(e => e.type === 'card' && inP(e.date)) },
      { id: 'transfers', title: 'Internal Cash In & Out Report', icon: 'refresh', desc: 'Transfers to engineers', cols: R.COLS.transfers, rows: () => c.transfers.filter(t => t.status === 'approved' && inP(t.date)) },
      {
        id: 'combined', title: 'Combined Cash In & Cash Out', icon: 'coins', desc: 'Running balance across transfers and cash spend',
        cols: [{ label: 'Date', get: r => R.fmtDate(r.date) }, { label: 'Ref', get: r => r.ref, cls: 'strong' }, { label: 'Engineer', get: r => r.who }, { label: 'Description', get: r => r.desc },
          { label: 'Cash In', get: r => r.cin, render: r => r.cin ? R.fmtAED(r.cin) : '—', cls: 'num' }, { label: 'Cash Out', get: r => r.cout, render: r => r.cout ? R.fmtAED(r.cout) : '—', cls: 'num' },
          { label: 'Balance', get: r => r.bal, render: r => `<b>${R.fmtAED(r.bal)}</b>`, cls: 'num' }],
        /* built from each engineer's cash ledger (opening, transfers, customer payments, cash expenses) */
        rows: () => {
          const all = [];
          c.engineers.forEach(e => API.cashLedger(e.id).forEach(r => all.push(Object.assign({ who: e.name }, r))));
          all.sort((a, b) => a.date - b.date);
          let bal = 0;
          const out = [];
          all.forEach(r => {
            bal += r.amount;
            if (inP(r.date)) out.push({ date: r.date, ref: r.ref, who: r.who, desc: `${r.kind} · ${r.desc}`, cin: r.amount > 0 ? r.amount : 0, cout: r.amount < 0 ? -r.amount : 0, bal });
          });
          return out.reverse();
        }
      },
      {
        id: 'branch', title: 'Branch-wise Performance', icon: 'building-2', desc: 'Leads, awards, spend and billing per branch',
        cols: [{ label: 'Branch', get: b => b.name, cls: 'strong' }, { label: 'Leads', get: b => b.leads, cls: 'num' }, { label: 'Quotes', get: b => b.quotes, cls: 'num' }, { label: 'Awarded', get: b => b.awarded, cls: 'num' },
          { label: 'Expenses', get: b => b.exp, render: b => R.fmtAED(b.exp), cls: 'num' }, { label: 'Invoiced', get: b => b.inv, render: b => R.fmtAED(b.inv), cls: 'num' }],
        rows: () => API.branches.filter(b => R.state.filters.country === 'all' || b.country === R.state.filters.country).map(b => {
          const inS = R.scopeOf(Object.assign({}, R.state.filters, { branch: b.id }));
          return {
            name: b.name,
            leads: API.leads.filter(l => inS(l) && inP(l.createdAt)).length,
            quotes: API.quotes.filter(q => inS(q) && inP(q.date)).length,
            awarded: API.quotes.filter(q => inS(q) && inP(q.awardDate)).length,
            exp: R.sum(API.expenses.filter(e => inS(e) && live(e) && inP(e.date)), e => e.amount),
            inv: R.sum(API.invoices.filter(i => inS(i) && i.status === 'submitted' && inP(i.date)), i => i.amount)
          };
        })
      }
    ];
  }

  function showPreview() {
    const r = reports().find(x => x.id === selected) || reports()[0];
    preview = R.dataTable($('#reportPreview'), {
      title: r.title, icon: r.icon, exportName: r.title, print: true,
      intro: `<p class="lock-note" style="margin-bottom:10px">${esc(R.rangeLabel())}</p>`,
      columns: r.cols, rows: () => (reports().find(x => x.id === r.id)).rows()
    });
  }

  R.page({
    id: 'reports',
    permission: 'reports.view',
    title: 'Reports',
    sub: 'Operational and financial reports. Filter, preview, download, print or share.',
    mount() {
      $('#reportList').addEventListener('click', e => {
        const card = e.target.closest('[data-report]');
        if (!card) return;
        const r = reports().find(x => x.id === card.dataset.report);
        if (e.target.closest('[data-dl]')) { R.downloadCSV(r.title, r.cols, r.rows()); return; }
        if (e.target.closest('[data-print]')) { R.printTable(r.title, r.cols, r.rows()); return; }
        if (e.target.closest('[data-share]')) {
          const url = location.href.split('?')[0] + '?report=' + r.id;
          (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => R.toast('Report link copied to clipboard'), () => R.toast(url, 'info'));
          return;
        }
        selected = r.id;
        R.setParam('report', r.id);
        $$('.report', $('#reportList')).forEach(x => x.classList.toggle('selected', x.dataset.report === selected));
        showPreview();
        R.focusSection($('#reportPreview'));
      });
      showPreview();
    },
    render() {
      $('#reportList').innerHTML = reports().map(r => `<div class="report${r.id === selected ? ' selected' : ''}" data-report="${r.id}" role="button" tabindex="0">
        <div class="report-top"><span class="gs-ic">${icon(r.icon)}</span><div><h4>${esc(r.title)}</h4><p>${esc(r.desc)} · ${R.fmtNum(r.rows().length)} rows</p></div></div>
        <div class="report-actions"><button class="btn btn-ghost btn-sm">${icon('eye', 'ic sm')}Preview</button><button class="btn btn-soft btn-sm" data-dl>${icon('download', 'ic sm')}CSV</button>
        <button class="btn btn-ghost btn-sm" data-print aria-label="Print">${icon('printer', 'ic sm')}</button><button class="btn btn-ghost btn-sm" data-share aria-label="Copy share link">${icon('send', 'ic sm')}</button></div></div>`).join('');
      if (preview) preview.draw();
    }
  });
})();
