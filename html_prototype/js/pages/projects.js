/* Projects page (projects.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon;
  let awaiting, table;

  /* awarded quotations without a project + projects still waiting for the PO */
  function awaitingRows() {
    const c = R.state.ctx;
    const noProject = c.leads.filter(l => l.stage === 'awarded' && !l.projectId).map(l => ({ kind: 'award', lead: l, quote: l.quote, date: l.awardDate }));
    const noPO = c.allProjects.filter(p => p.status === 'popending').map(p => ({ kind: 'po', project: p, lead: API.lead(p.leadId), quote: API.quote(p.quoteId), date: p.createdAt }));
    return noProject.concat(noPO).sort((a, b) => b.date - a.date);
  }

  R.page({
    id: 'projects',
    permission: 'projects.view',
    title: 'Projects',
    sub: 'Create projects from awarded quotations, record the PO and follow each project to closure.',
    actions: [{ label: 'Create Project', icon: 'briefcase', perm: 'projects.create', run: () => R.projectForm() }],
    mount() {
      awaiting = R.dataTable($('#activationTable'), {
        title: 'Awaiting Project / PO', icon: 'clock', exportName: 'Awaiting project or PO', compact: true, pageSize: 10,
        intro: '<p class="lock-note" style="margin-bottom:10px">Awarded → Create Project → PO received → Open. The Accountant creates the project and records the PO.</p>',
        columns: [
          { label: 'Stage', get: r => r.kind === 'award' ? 'Awarded — no project' : 'PO Pending', render: r => r.kind === 'award' ? R.pill('Awarded', 'primary') : R.pill('PO Pending', 'amber') },
          { label: 'Quote / Project', get: r => r.kind === 'po' ? r.project.projectNumber : r.quote.number, cls: 'strong' },
          { label: 'Client', get: r => r.lead.name },
          { label: 'Subject', get: r => r.lead.subject },
          { label: 'Branch', get: r => R.branchOf(r.lead.branchId).short },
          { label: 'Value', get: r => r.quote.amount, render: r => R.fmtAED(r.quote.amount), cls: 'num' },
          { label: 'Since', get: r => R.fmtDate(r.date) },
          {
            label: 'Action', cls: 'ta-c',
            render: r => !R.can(r.kind === 'award' ? 'projects.create' : 'projects.open') ? '<span style="color:var(--muted)">Accountant only</span>'
              : r.kind === 'award' ? `<button class="btn btn-soft btn-sm" data-act="create">${icon('briefcase', 'ic sm')}Create Project</button>`
                : `<button class="btn btn-soft btn-sm" data-act="po">${icon('file-plus', 'ic sm')}Add PO</button>`
          }
        ],
        rows: awaitingRows,
        empty: 'Nothing is waiting — every awarded quotation has an open project.',
        onRow: r => R.go(r.kind === 'po' ? `project.html?id=${r.project.id}` : `quote.html?id=${r.quote.id}`),
        onAction: (act, r) => (act === 'create' ? R.projectForm(r.lead.id) : R.poForm(r.project.id))
      });
      table = R.dataTable($('#projectsTable'), {
        title: 'All Projects', icon: 'clipboard', exportName: 'Projects', print: true,
        columns: R.COLS.projects.concat([{ label: 'Action', cls: 'ta-c', render: () => `<button class="act view" data-act="view" title="Open" aria-label="Open">${icon('eye')}</button>` }]),
        tabs: [{ id: 'all', label: 'All', filter: () => true }].concat(API.projectStatuses.map(s => ({ id: s.id, label: s.name, filter: p => p.status === s.id }))),
        tab: R.params.get('tab'),
        onTab: t => R.setParam('tab', t === 'all' ? null : t),
        rows: () => R.state.ctx.projects.filter(p => p.createdAt <= R.state.ctx.to).sort((a, b) => b.createdAt - a.createdAt),
        onRow: p => R.go(`project.html?id=${p.id}`),
        onAction: (act, p) => R.go(`project.html?id=${p.id}`)
      });
      if (R.params.get('tab') === 'activation') R.focusSection($('#activationTable'));
    },
    render() {
      R.renderKpis($('#kpis'), ['activeProjects', 'awaitingActivation', 'completedProjects', 'invoiced']);
      awaiting.draw();
      table.draw();
    }
  });
})();
