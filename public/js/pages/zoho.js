/* Zoho Books page (zoho.html) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, icon = R.icon;
  let log;

  R.page({
    id: 'zoho',
    permission: 'zoho.view',
    title: 'Zoho Books',
    sub: 'Two-way sync for petty cash accounts, project creation and status. Demo integration layer — no live Zoho API calls are made.',
    actions: [{ label: 'Sync Queue Now', icon: 'refresh', perm: 'zoho.sync', run: () => R.act(() => API.syncZohoQueue(), 'Pending items are syncing…', 'info') }],
    mount() {
      log = R.dataTable($('#zohoLog'), {
        title: 'Sync Log', icon: 'book', exportName: 'Zoho sync log',
        columns: [
          { label: 'Time', get: z => `${R.fmtDate(z.time)} ${R.fmtTime(z.time)}` },
          { label: 'Entity', get: z => z.entity },
          { label: 'Reference', get: z => z.ref, cls: 'strong' },
          { label: 'Action', get: z => z.action },
          { label: 'Status', get: z => z.status, render: z => z.status === 'failed' ? R.pill('Failed', 'red') : z.status === 'queued' ? R.pill('Queued', 'amber') : z.status === 'syncing' ? R.pill('Syncing…', 'blue') : R.pill('Synced', 'green') },
          { label: 'Message', get: z => z.message },
          { label: 'Retry', cls: 'ta-c', render: z => z.status === 'failed' && R.can('zoho.sync') ? `<button class="btn btn-soft btn-sm" data-act="retry">${icon('rotate-ccw', 'ic sm')}Retry</button>` : '—' }
        ],
        tabs: [
          { id: 'all', label: 'All', filter: () => true },
          { id: 'failed', label: 'Failed', filter: z => z.status === 'failed' },
          { id: 'queued', label: 'Pending Sync', filter: z => z.status === 'queued' || z.status === 'syncing' },
          { id: 'synced', label: 'Synced', filter: z => z.status === 'synced' }
        ],
        rows: () => API.zohoLog.slice().sort((a, b) => b.time - a.time),
        onAction: (act, z) => R.act(() => API.retryZoho(z.id), `${z.ref} queued for retry`, 'info')
      });
    },
    render() {
      const L = API.zohoLog;
      const failed = L.filter(z => z.status === 'failed').length, queued = L.filter(z => z.status === 'queued' || z.status === 'syncing').length;
      const lastSync = L.filter(z => z.status === 'synced').sort((a, b) => b.time - a.time)[0];
      $('#zohoStatus').innerHTML = `<div class="zoho-status"><span class="zs-dot"></span><div><strong>Connected to Zoho Books</strong><small>Rich Mount Contracting LLC · demo connection (mock layer) · last sync ${lastSync ? R.fmtDateTime(lastSync.time) : '—'}</small></div></div>
        <div class="mini-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:0">
          <div><small>Synced</small><strong>${L.filter(z => z.status === 'synced').length}</strong></div>
          <div><small>Pending sync</small><strong>${queued}</strong></div>
          <div class="${failed ? 'bad' : ''}"><small>Failed</small><strong>${failed}</strong></div>
          <div><small>Petty cash accounts</small><strong>${API.engineers.length}</strong></div>
        </div>`;
      log.draw();
    }
  });
})();
