/* Dashboard page (index.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, Charts = window.RMCharts, $ = R.$, $$ = R.$$, esc = R.esc, icon = R.icon;
  const view = { trend: 'monthly', expense: 'year', pipeline: '30', trendStyle: 'area', expenseStyle: 'stacked', recentPage: 0 };
  const RECENT_PAGE_SIZE = 5;

  function buckets(kind, from, to) {
    let out = [];
    if (kind === 'weekly') {
      for (let i = 11; i >= 0; i--) {
        const b = R.endOfDay(R.addDays(to, -7 * i)), a = R.startOfDay(R.addDays(b, -6));
        out.push({ label: `${R.pad(a.getDate())} ${R.MONTHS[a.getMonth()]}`, a, b });
      }
    } else if (kind === 'quarterly') {
      let q = new Date(from.getFullYear(), Math.floor(from.getMonth() / 3) * 3, 1);
      while (q <= to) {
        const qe = R.endOfDay(new Date(q.getFullYear(), q.getMonth() + 3, 0));
        out.push({ label: `Q${Math.floor(q.getMonth() / 3) + 1} '${String(q.getFullYear()).slice(2)}`, a: q < from ? from : q, b: qe > to ? to : qe });
        q = new Date(q.getFullYear(), q.getMonth() + 3, 1);
      }
    } else {
      const multiYear = from.getFullYear() !== to.getFullYear();
      let m = R.startOfMonth(from);
      while (m <= to) {
        const me = R.endOfMonth(m);
        out.push({ label: R.MONTHS[m.getMonth()] + (multiYear ? ` '${String(m.getFullYear()).slice(2)}` : ''), a: m < from ? from : m, b: me > to ? to : me });
        m = R.addMonths(m, 1);
      }
      if (out.length > 12) out = out.slice(-12);
    }
    return out;
  }

  /* draws with the style picked in the card's "Chart style" dropdown */
  function drawChart(el, style, o) {
    if (style === 'area' || style === 'line') Charts.line(el, { ...o, area: style === 'area' });
    else Charts.bars(el, { ...o, grouped: style === 'bar' });
  }

  function renderTrend(c) {
    const bs = buckets(view.trend, c.from, c.to);
    drawChart($('#trendChart'), view.trendStyle, {
      label: 'Leads and quotations trend', height: 172,
      labels: bs.map(b => b.label),
      series: [
        { name: 'Leads', color: '#941C3C', values: bs.map(b => c.leads.filter(l => R.inRange(l.createdAt, b.a, b.b)).length) },
        { name: 'Quotations', color: '#E58AA2', values: bs.map(b => c.quotes.filter(q => R.inRange(q.date, b.a, b.b)).length) }
      ]
    });
  }

  function renderExpenses(c) {
    let bs;
    if (view.expense === '6m') bs = buckets('monthly', R.startOfDay(R.addMonths(R.startOfMonth(c.to), -5)), c.to);
    else if (view.expense === 'quarterly') bs = buckets('quarterly', c.from, c.to);
    else bs = buckets('monthly', c.from, c.to);
    const live = c.expenses.filter(e => e.status !== 'rejected');
    drawChart($('#expenseChart'), view.expenseStyle, {
      label: 'Expense overview', height: 186, prefix: 'AED ', highlightLast: true,
      labels: bs.map(b => b.label),
      series: [
        { name: 'Cash', color: '#941C3C', values: bs.map(b => R.sum(live.filter(e => e.type === 'cash' && R.inRange(e.date, b.a, b.b)), e => e.amount)) },
        { name: 'Card', color: '#EBA3B5', values: bs.map(b => R.sum(live.filter(e => e.type === 'card' && R.inRange(e.date, b.a, b.b)), e => e.amount)) }
      ]
    });
  }

  const PIPE_FOOT = {
    new: { label: 'Add Lead', icon: 'plus', run: () => R.ACTIONS.newLead.run() },
    draft: { label: 'Create Quotation', icon: 'plus', run: () => R.ACTIONS.createQuote.run() },
    sent: { label: 'View Sent Quotations', run: () => R.go('quotations.html?tab=sent') },
    awarded: { label: 'View Awarded', run: () => R.go('quotations.html?tab=awarded') }
  };
  function renderPipeline(c) {
    const a = view.pipeline === 'range' ? c.from : R.startOfDay(R.addDays(c.to, -(+view.pipeline) + 1));
    $('#pipelineCols').innerHTML = API.stages.map(s => {
      const items = c.leads.filter(l => l.stage === s.id && R.inRange(l.stageDate, a, c.to)).sort((x, y) => y.stageDate - x.stageDate);
      const foot = PIPE_FOOT[s.id];
      return `<div class="pl-col pl-${s.id}">
        <div class="pl-head"><span>${s.name}</span><span class="pl-count">${items.length}</span></div>
        <div class="pl-list">${items.length ? items.map(l => `
          <div class="pl-card" data-lead="${l.id}" role="button" tabindex="0" aria-label="${esc(l.name)} – ${esc(l.subject)}">
            <span class="avatar" title="Engineer: ${esc(R.engName(l.engineerId))}">${R.initials(l.name)}</span>
            <div class="pl-info">
              <strong>${esc(l.name)}</strong><span>${esc(l.subject)}</span>
              <div class="pl-meta"><span>${icon('user', 'ic xs')}${esc(R.branchOf(l.branchId).code)}</span><span>${icon('calendar', 'ic xs')}${R.fmtDate(l.stageDate)}</span></div>
            </div>
            <button class="kebab" data-lead-menu="${l.id}" aria-label="More actions for ${esc(l.name)}">${icon('more-vertical')}</button>
          </div>`).join('') : `<div class="pl-empty">Nothing in ${s.name.toLowerCase()} for this period</div>`}
        </div>
        <button class="pl-foot" data-foot="${s.id}">${foot.icon ? icon(foot.icon, 'ic sm') : ''}${foot.label}</button>
      </div>`;
    }).join('');
  }

  function renderRecent(c) {
    const q = $('#recentSearch').value.trim().toLowerCase(), st = $('#recentStatus').value;
    const list = c.leads.filter(l => R.inRange(l.createdAt, c.from, c.to)
      && (!st || API.leadStatus(l) === st)
      && (!q || [l.name, l.company, R.engName(l.engineerId), l.source].some(v => String(v || '').toLowerCase().includes(q))))
      .sort((a, b) => b.createdAt - a.createdAt);
    $('#recentCount').textContent = c.leads.filter(l => R.inRange(l.createdAt, R.startOfDay(R.addDays(c.to, -6)), c.to)).length;
    const pages = Math.max(1, Math.ceil(list.length / RECENT_PAGE_SIZE));
    view.recentPage = Math.min(view.recentPage, pages - 1);
    const start = view.recentPage * RECENT_PAGE_SIZE;
    const rows = list.slice(start, start + RECENT_PAGE_SIZE);
    renderRecentPager(list.length, pages, start, rows.length);
    $('#recentTable').innerHTML = `<thead><tr><th>#</th><th>Lead Name</th><th>Company</th><th>Assigned To</th><th>Source</th><th>Status</th><th>Date</th><th class="ta-c">Action</th></tr></thead>
      <tbody>${rows.length ? rows.map((l, i) => `<tr>
        <td class="rl-no">${start + i + 1}</td><td class="strong rl-name" title="${esc(l.name)}">${esc(l.name)}</td>
        <td class="rl-field" data-label="Company" title="${esc(l.company)}">${esc(l.company)}</td><td class="rl-field" data-label="Assigned To">${esc(R.engName(l.engineerId))}</td>
        <td class="rl-field" data-label="Source">${esc(l.source)}</td><td class="rl-status">${R.leadPill(l)}</td><td class="rl-field" data-label="Date">${R.fmtDate(l.createdAt)}</td>
        <td class="ta-c rl-act"><span class="row-actions">
          <button class="act view" data-view="${l.id}" title="View" aria-label="View ${esc(l.name)}">${icon('eye')}</button>
          <button class="act edit" data-edit="${l.id}" title="Edit" aria-label="Edit ${esc(l.name)}"${R.can('leads.edit') ? '' : ' disabled'}>${icon('edit')}</button>
          <button class="act" data-lead-menu="${l.id}" title="More" aria-label="More actions">${icon('more-vertical')}</button>
        </span></td></tr>`).join('') : `<tr><td class="empty" colspan="8">${q || st ? 'No leads match your search.' : 'No leads in the selected period.'}</td></tr>`}</tbody>`;
  }

  function renderRecentPager(total, pages, start, shown) {
    const pg = $('#recentPager'), cur = view.recentPage;
    pg.hidden = total <= RECENT_PAGE_SIZE;
    if (pg.hidden) return;
    // show at most 5 page numbers around the current page
    const first = Math.max(0, Math.min(cur - 2, pages - 5)), last = Math.min(pages - 1, first + 4);
    let nums = '';
    for (let p = first; p <= last; p++) nums += `<button class="btn btn-ghost btn-sm pager-num${p === cur ? ' active' : ''}" data-page="${p}"${p === cur ? ' aria-current="page"' : ''}>${p + 1}</button>`;
    pg.innerHTML = `<span class="pager-info">Showing ${start + 1}–${start + shown} of ${R.fmtNum(total)}</span>
      <span class="pager-btns">
        <button class="btn btn-ghost btn-sm" data-page="${cur - 1}"${cur === 0 ? ' disabled' : ''} aria-label="Previous page">${icon('chevron-right', 'ic sm flip')}</button>
        ${nums}
        <button class="btn btn-ghost btn-sm" data-page="${cur + 1}"${cur === pages - 1 ? ' disabled' : ''} aria-label="Next page">${icon('chevron-right', 'ic sm')}</button>
      </span>`;
  }

  function upcoming() {
    const start = R.startOfDay(R.TODAY);
    return API.activities.filter(a => !a.done && a.when >= start && R.branchVisible(a.branchId)).sort((a, b) => a.when - b.when);
  }
  function renderActivities() {
    const list = upcoming().slice(0, 4);
    $('#activities').innerHTML = list.length ? list.map(a => {
      const day = Math.round((R.startOfDay(a.when) - R.startOfDay(R.TODAY)) / 864e5);
      const meta = R.ACTIVITY[a.type];
      return `<li class="tl-item" data-activity="${a.id}" tabindex="0" role="button">
        <span class="tl-time">${R.fmtTime(a.when)}${day ? `<small>${day === 1 ? 'Tomorrow' : R.fmtDate(a.when).slice(0, 6)}</small>` : ''}</span>
        <span class="tl-dot"></span>
        <span class="tl-icon">${icon(meta.icon)}</span>
        <span class="tl-body"><strong>${esc(a.title)}</strong><small>${esc(a.sub)}</small></span>
        <span class="tag tag-${a.type}">${meta.label}</span></li>`;
    }).join('') : '<li class="empty-state">No upcoming activities for this branch.</li>';
  }

  R.page({
    id: 'dashboard',
    permission: 'dashboard.view',
    title: 'Dashboard',
    get sub() { return `Welcome back, ${R.me.name.split(' ')[0]}! Here's what's happening across Rich Mount ERP.`; },
    mount() {
      const head = $('#pageHead .page-actions');
      if (R.can('leads.create')) {
        head.innerHTML = `<div class="split-btn"><button class="btn btn-primary btn-lg" id="newLeadBtn">${icon('plus-circle')}New Lead</button><button class="btn btn-primary btn-lg split-caret" id="newLeadMore" aria-label="More create options">${icon('chevron-down')}</button></div>`;
      }

      if ($('#newLeadBtn')) {
        $('#newLeadBtn').addEventListener('click', () => R.ACTIONS.newLead.run());
        $('#newLeadMore').addEventListener('click', e => R.menu(e.currentTarget, [
          { label: 'Quick add lead', icon: 'plus', run: R.ACTIONS.newLead.run },
          { label: 'Create quotation', icon: 'file-plus', run: R.ACTIONS.createQuote.run, disabled: !R.can('quotes.create') },
          { label: 'Open Lead Management', icon: 'users', run: () => R.go('leads.html') }
        ], { align: 'right', width: 220 }));
      }

      $('#trendPeriod').addEventListener('change', e => { view.trend = e.target.value; renderTrend(R.state.ctx); });
      $('#expensePeriod').addEventListener('change', e => { view.expense = e.target.value; renderExpenses(R.state.ctx); });
      $('#trendStyle').addEventListener('change', e => { view.trendStyle = e.target.value; renderTrend(R.state.ctx); });
      $('#expenseStyle').addEventListener('change', e => { view.expenseStyle = e.target.value; renderExpenses(R.state.ctx); });
      $('#pipelinePeriod').addEventListener('change', e => { view.pipeline = e.target.value; renderPipeline(R.state.ctx); });

      $('#pipelineCols').addEventListener('click', e => {
        const k = e.target.closest('[data-lead-menu]');
        if (k) { e.stopPropagation(); R.leadMenu(k, k.dataset.leadMenu); return; }
        const f = e.target.closest('[data-foot]');
        if (f) { PIPE_FOOT[f.dataset.foot].run(); return; }
        const c = e.target.closest('[data-lead]');
        if (c) R.leadDrawer(c.dataset.lead);
      });
      $('#pipelineCols').addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-lead]')) { e.preventDefault(); R.leadDrawer(e.target.dataset.lead); }
      });
      $('#recentSearch').addEventListener('input', () => { view.recentPage = 0; renderRecent(R.state.ctx); });
      $('#recentStatus').addEventListener('change', () => { view.recentPage = 0; renderRecent(R.state.ctx); });
      $('#recentPager').addEventListener('click', e => {
        const b = e.target.closest('[data-page]');
        if (b && !b.disabled) { view.recentPage = +b.dataset.page; renderRecent(R.state.ctx); }
      });
      $('#recentTable').addEventListener('click', e => {
        const v = e.target.closest('[data-view]'), ed = e.target.closest('[data-edit]'), mo = e.target.closest('[data-lead-menu]');
        if (v) R.go(`lead.html?id=${v.dataset.view}`);
        else if (ed) R.leadForm(API.lead(ed.dataset.edit));
        else if (mo) R.leadMenu(mo, mo.dataset.leadMenu);
      });
      $('#activities').addEventListener('click', e => { const a = e.target.closest('[data-activity]'); if (a) R.activityDrawer(a.dataset.activity); });
      $('#activities').addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('[data-activity]')) R.activityDrawer(e.target.dataset.activity); });
      $('#activitiesAll').addEventListener('click', () => R.tableModal({
        title: 'Upcoming Activities', sub: R.scopeLabel(),
        columns: [
          { label: 'When', get: a => `${R.fmtDate(a.when)} ${R.fmtTime(a.when)}` }, { label: 'Activity', get: a => a.title, cls: 'strong' }, { label: 'Details', get: a => a.sub },
          { label: 'Type', get: a => R.ACTIVITY[a.type].label, render: a => `<span class="tag tag-${a.type}">${R.ACTIVITY[a.type].label}</span>` },
          { label: 'Branch', get: a => R.branchOf(a.branchId).short }, { label: 'Owner', get: a => a.owner || '—' }
        ],
        rows: upcoming, onRow: a => R.activityDrawer(a.id)
      }));

      if (location.hash === '#pipeline') R.focusSection($('#pipeline'));
    },
    render(c) {
      R.renderKpis($('#kpis'), R.ROLES[R.me.key].kpis);
      renderTrend(c);
      renderExpenses(c);
      renderPipeline(c);
      renderRecent(c);
      renderActivities();
    },
    resize() { const c = R.state.ctx; renderTrend(c); renderExpenses(c); }
  });
})();
