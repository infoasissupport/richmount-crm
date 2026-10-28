/* Dashboard page (index.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, $$ = R.$$, esc = R.esc, icon = R.icon;
  const view = { pipeline: '30', recentPage: 0 };
  const RECENT_PAGE_SIZE = 5;

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

  const avatar = R.avatar, sourcePill = R.sourcePill;

  function renderRecent(c) {
    const q = $('#recentSearch').value.trim().toLowerCase(), st = $('#recentStatus').value, from = R.periodStart($('#recentPeriod').value, c.to);
    const list = c.leads.filter(l => (!from || R.inRange(l.createdAt, from, c.to))
      && (!st || API.leadStatus(l) === st)
      && (!q || [l.name, l.company, R.engName(l.engineerId), l.source].some(v => String(v || '').toLowerCase().includes(q))))
      .sort((a, b) => b.createdAt - a.createdAt);
    const pages = Math.max(1, Math.ceil(list.length / RECENT_PAGE_SIZE));
    view.recentPage = Math.min(view.recentPage, pages - 1);
    const start = view.recentPage * RECENT_PAGE_SIZE;
    const rows = list.slice(start, start + RECENT_PAGE_SIZE);
    renderRecentPager(list.length, pages, start, rows.length);
    $('#recentTable').innerHTML = `<thead><tr><th>Sl No</th><th>Lead No</th><th>Lead Name</th><th>Company</th><th>Assigned To</th><th>Source</th><th>Status</th><th>Date ${icon('arrow-down', 'ic sm')}</th><th class="ta-c">Action</th></tr></thead>
      <tbody>${rows.length ? rows.map((l, i) => `<tr>
        <td class="rl-no">${start + i + 1}</td><td class="rl-leadno">${esc(l.leadNumber)}</td><td class="strong rl-name" title="${esc(l.name)}"><span class="cell-who">${avatar(l.name)}<span class="cell-text">${esc(l.name)}</span></span></td>
        <td class="rl-field rl-company" data-label="Company" title="${esc(l.company)}"><span class="cell-text">${esc(l.company)}<small>${esc(R.deptName(l.department))}</small></span></td>
        <td class="rl-field rl-eng" data-label="Assigned To"><span class="cell-who">${avatar(R.engName(l.engineerId))}<span class="cell-text">${esc(R.engName(l.engineerId))}<small>Project Engineer</small></span></span></td>
        <td class="rl-field rl-src" data-label="Source">${sourcePill(l.source)}</td><td class="rl-status">${R.leadPill(l)}</td>
        <td class="rl-field rl-date" data-label="Date"><span class="cell-date">${icon('calendar', 'ic sm')}${R.fmtDate(l.createdAt)}</span></td>
        <td class="ta-c rl-act"><span class="row-actions">
          <button class="act view" data-view="${l.id}" title="View" aria-label="View ${esc(l.name)}">${icon('eye')}</button>
          <button class="act edit" data-edit="${l.id}" title="Edit" aria-label="Edit ${esc(l.name)}"${R.can('leads.edit') ? '' : ' disabled'}>${icon('edit')}</button>
          <button class="act" data-lead-menu="${l.id}" title="More" aria-label="More actions">${icon('more-vertical')}</button>
        </span></td></tr>`).join('') : `<tr><td class="empty" colspan="9">${q || st ? 'No leads match your search.' : 'No leads in the selected period.'}</td></tr>`}</tbody>`;
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

  R.page({
    id: 'dashboard',
    permission: 'dashboard.view',
    title: 'Dashboard',
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
      $('#recentPeriod').addEventListener('change', () => { view.recentPage = 0; renderRecent(R.state.ctx); });
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
      if (location.hash === '#pipeline') R.focusSection($('#pipeline'));
    },
    render(c) {
      R.renderKpis($('#kpis'), R.ROLES[R.me.key].kpis);
      renderPipeline(c);
      renderRecent(c);
    }
  });
})();
