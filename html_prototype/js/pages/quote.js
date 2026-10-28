/* Quotation editor / detail page (quote.html?id=… or quote.html?lead=…) */
(function () {
  'use strict';
  const R = window.RM, API = R.API, $ = R.$, $$ = R.$$, esc = R.esc, icon = R.icon;

  let quote = API.quote(R.params.get('id'));
  const lead = quote ? API.lead(quote.leadId) : API.lead(R.params.get('lead'));

  /* a lead with an open quotation always edits that quotation */
  if (!quote && lead && lead.quote && R.DRAFTISH.includes(lead.quote.status)) { location.replace(`quote.html?id=${lead.quote.id}`); return; }

  const STEPS = ['draft', 'pending', 'approved', 'sent', 'awarded'];
  const blankItem = () => ({ desc: '', unit: 'lump sum', qty: 1, price: '' });
  const snapshot = q => ({
    date: q.date, validity: q.validity, output: q.output, items: q.items.map(i => Object.assign({}, i)),
    discountType: q.discountType, discount: q.discount, taxRate: q.taxRate, techDesc: q.techDesc, terms: q.terms, notes: q.notes, attachments: (q.attachments || []).slice()
  });
  let doc = quote ? snapshot(quote) : lead ? {
    date: R.TODAY, validity: 30, output: 'tax', items: [blankItem()], discountType: 'amt', discount: 0, taxRate: .05,
    techDesc: API.TECH[lead.department], terms: API.DEFAULT_TERMS, notes: '', attachments: []
  } : null;

  const editable = () => R.can(quote ? 'quotes.edit' : 'quotes.create') && (!quote || ['draft', 'rejected'].includes(quote.status)) && R.AUTH.inScope(lead, R.me);
  const payload = () => ({
    date: doc.date, validity: +doc.validity, output: doc.output, discountType: doc.discountType, discount: +doc.discount || 0, taxRate: +doc.taxRate,
    techDesc: doc.techDesc, terms: doc.terms, notes: doc.notes, attachments: doc.attachments.slice(),
    items: doc.items.map(i => Object.assign({ desc: String(i.desc || '').trim(), unit: i.unit || '', qty: +i.qty || 0, price: +i.price || 0 }, photosOf(i).length ? { photos: photosOf(i) } : {}))
  });

  /* line-item photos: shrunk to a small JPEG and kept under their own storage key,
     so the quote (and its change journal) only carries the photo id */
  const MAX_PHOTOS = 3;
  const photoSrc = id => (id ? R.store.get('qphoto.' + id, '') : '');
  /* older items kept a single `photo` id */
  const photosOf = i => (i.photos || (i.photo ? [i.photo] : [])).filter(photoSrc);
  function viewPhoto(src) {
    R.modal({ title: 'Item photo', size: 'lg', noFocus: true, body: `<img class="photo-view" src="${src}" alt="Item photo">` });
  }
  function readPhoto(file) {
    return new Promise((resolve, reject) => {
      if (!/^image\//.test(file.type)) return reject(new Error('Choose an image file'));
      const img = new Image(), url = URL.createObjectURL(file);
      img.onload = () => {
        const s = Math.min(1, 640 / Math.max(img.width, img.height));
        const c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        const g = c.getContext('2d');
        g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
        g.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        resolve(c.toDataURL('image/jpeg', .72));
      };
      img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('The image could not be read')); };
      img.src = url;
    });
  }
  function photoCell(it) {
    const list = photosOf(it);
    return list.map((id, n) => `<span class="ph"><img src="${photoSrc(id)}" alt="Item photo ${n + 1}" title="Click to enlarge" data-photo-view>${editable() ? `<button type="button" data-photo-remove="${n}" title="Remove photo" aria-label="Remove photo">${icon('x', 'ic xs')}</button>` : ''}</span>`).join('')
      + (editable() && list.length < MAX_PHOTOS ? `<label class="ph-add" title="Add up to ${MAX_PHOTOS} photos">${icon('image', 'ic sm')}${list.length ? 'Add' : 'Add photo'}<input type="file" accept="image/*" multiple data-photo data-plain></label>` : '');
  }

  /* ---------- auto-save ---------- */
  let saveTimer = null, dirty = false, lastSaved = quote ? quote.updatedAt : null;
  function setAuto(s) {
    const el = $('#autosave');
    if (!el) return;
    if (!editable()) { el.innerHTML = `${icon('lock', 'ic sm')}Read-only — ${esc(R.quoteStatus(quote ? quote.status : 'draft').name)}`; el.className = 'autosave'; return; }
    const sameDay = lastSaved && R.startOfDay(lastSaved).getTime() === R.startOfDay(API.now()).getTime();
    const last = lastSaved ? ` · Last saved ${sameDay ? R.fmtTime(lastSaved) : R.fmtDate(lastSaved)}` : '';
    el.className = 'autosave ' + s;
    el.innerHTML = s === 'saving' ? '<span class="spin"></span>Saving…'
      : s === 'dirty' ? `${icon('edit', 'ic sm')}Unsaved changes${last}`
        : s === 'error' ? `${icon('alert', 'ic sm')}Not saved — check the highlighted fields`
          : lastSaved ? `${icon('check-circle', 'ic sm')}Saved${last}` : `${icon('info', 'ic sm')}Auto-save is on — the draft is created when you add the first line item`;
  }
  function markDirty() {
    dirty = true;
    setAuto('dirty');
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => save(false), 1200);
  }
  function save(manual) {
    clearTimeout(saveTimer);
    saveTimer = null;
    if (!editable()) return !!quote;
    if (!quote && !doc.items.some(i => String(i.desc || '').trim())) {
      if (manual) R.toast('Add at least one line item description before saving.', 'error');
      setAuto('');
      return false;
    }
    setAuto('saving');
    const r = R.act(() => (quote ? API.updateQuote(quote.id, payload()) : API.createQuote(lead.id, payload())));
    if (!r) { setAuto('error'); return false; }
    if (!quote) {
      quote = r;
      R.setParam('lead', null);
      R.setParam('id', quote.id);
      rememberTab();
      renderTabs();
    }
    dirty = false;
    lastSaved = new Date(quote.updatedAt);
    setTimeout(() => setAuto('saved'), 300);
    if (manual) R.toast('Saved successfully');
    return true;
  }
  window.addEventListener('pagehide', () => { if (dirty) save(false); });
  window.addEventListener('beforeunload', () => { if (dirty) save(false); });

  /* ---------- workspace tabs (keep several quotations open) ---------- */
  function rememberTab() {
    if (!quote) return;
    const list = R.store.get('openQuotes', []).filter(id => id !== quote.id && API.quote(id));
    list.unshift(quote.id);
    R.store.set('openQuotes', list.slice(0, 6));
  }
  function renderTabs() {
    const host = $('#wsTabs');
    const ids = R.store.get('openQuotes', []).filter(id => API.quote(id));
    host.hidden = ids.length < 1;
    host.innerHTML = `<span class="ws-label">${icon('file-text', 'ic sm')}Open quotations</span>` + ids.map(id => {
      const q = API.quote(id), l = API.lead(q.leadId);
      return `<span class="ws-tab${quote && q.id === quote.id ? ' active' : ''}"><a href="quote.html?id=${q.id}">${esc(q.number)} · ${esc(l.name)}</a><button type="button" data-close-tab="${q.id}" aria-label="Close ${esc(q.number)}">${icon('x', 'ic xs')}</button></span>`;
    }).join('') + `<button type="button" class="btn btn-ghost btn-sm ws-min" id="wsMin">${icon('chevron-down', 'ic sm')}Minimize</button>`;
  }

  /* ---------- rendering ---------- */
  const itemTotal = i => (+i.qty || 0) * (+i.price || 0);
  function renderItems() {
    const body = $('#itemsBody'), tpl = $('#tplLineItem');
    body.innerHTML = '';
    doc.items.forEach((it, i) => {
      const frag = tpl.content.cloneNode(true);
      const tr = frag.firstElementChild, photoRow = $('.li-photo-row', frag);
      tr.dataset.i = photoRow.dataset.i = i;
      $('.li-no', tr).textContent = i + 1;
      $$('[data-k]', tr).forEach(inp => { inp.value = it[inp.dataset.k] == null ? '' : it[inp.dataset.k]; inp.disabled = !editable(); });
      $('.li-total', tr).textContent = R.fmtAED2(itemTotal(it));
      $('.li-photo', photoRow).innerHTML = photoCell(it);
      photoRow.hidden = !$('.li-photo', photoRow).innerHTML;
      $('[data-remove]', tr).hidden = !editable();
      body.appendChild(frag);
    });
    if (!doc.items.length) body.innerHTML = '<tr><td colspan="7" class="empty">No line items yet.</td></tr>';
  }
  function renderTotals() {
    const t = API.quoteTotals(payload());
    const f = $('#qTotals');
    $('[data-f=subtotal]', f).textContent = R.fmtAED2(t.subtotal);
    $('[data-f=discount]', f).textContent = '− ' + R.fmtAED2(t.discount);
    $('[data-f=taxable]', f).textContent = R.fmtAED2(t.taxable);
    $('[data-f=tax]', f).textContent = R.fmtAED2(t.tax);
    $('[data-f=total]', f).textContent = R.fmtAED2(t.total);
    return t;
  }
  function renderAttachments() {
    const list = $('#attList'), tpl = $('#tplAttachment');
    list.innerHTML = '';
    doc.attachments.forEach((n, i) => {
      const li = tpl.content.firstElementChild.cloneNode(true);
      $('[data-f=name]', li).textContent = n;
      const b = $('[data-remove-att]', li);
      b.dataset.i = i;
      b.hidden = !editable();
      list.appendChild(li);
    });
    if (!doc.attachments.length) list.innerHTML = '<li class="muted">No attachments</li>';
    $('#attField').hidden = !editable();
  }
  function fillForm() {
    const b = R.branchOf(lead.branchId);
    const kv = $('#qLeadKv');
    const set = (k, v) => { $(`[data-f=${k}]`, kv).textContent = v; };
    set('number', quote ? quote.number : 'Assigned on first save');
    set('leadNumber', lead.leadNumber);
    set('company', lead.company);
    set('contact', [lead.contact, lead.phone, lead.email].filter(Boolean).join(' · ') || '—');
    set('subject', lead.subject);
    set('branch', `${b.name} (${b.code})`);
    set('format', b.quoteFormat);
    set('engineer', R.engName(lead.engineerId));
    $('#leadLink').href = `lead.html?id=${lead.id}`;

    const meta = $('#qMeta');
    meta.elements.date.value = R.toISO(new Date(doc.date));
    meta.elements.validity.value = String(doc.validity);
    meta.elements.output.value = doc.output;
    const text = $('#qText');
    ['techDesc', 'terms', 'notes'].forEach(k => { text.elements[k].value = doc[k] || ''; });
    const tot = $('#qTotals');
    tot.elements.discount.value = doc.discount || '';
    tot.elements.discountType.value = doc.discountType;
    tot.elements.taxRate.value = String(+doc.taxRate);
    $$('input, select, textarea', meta).concat($$('textarea', text), $$('input, select', tot)).forEach(el => { el.disabled = !editable(); });
    $('#addItem').hidden = $('#addCatalog').hidden = !editable();
    $('#itemsHint').hidden = !editable();
    renderItems();
    renderTotals();
    renderAttachments();
  }

  function renderStatus() {
    const st = quote ? quote.status : 'draft';
    const idx = STEPS.indexOf(st === 'rejected' ? 'draft' : st);
    $$('#qStepper li').forEach((li, i) => {
      li.className = i < idx ? 'done' : i === idx ? (st === 'rejected' ? 'current warn' : 'current') : '';
      $('span', li).innerHTML = i < idx ? icon('check', 'ic xs') : String(i + 1);
    });
    const banner = $('#qBanner');
    let msg = '', tone = '';
    if (!quote) msg = 'New quotation — details are taken from the lead. Add line items; the draft saves automatically.';
    else if (st === 'rejected') { msg = `Returned for revision: ${quote.rejectReason}. Edit the quotation and submit it again.`; tone = 'bad'; }
    else if (st === 'pending') msg = R.can('quotes.approve') ? 'This quotation is waiting for your approval.' : `Submitted ${R.fmtDateTime(quote.submittedAt)} — waiting for approval by the ${API.settings.quoteApprover === 'admin' ? 'Super Admin' : 'Estimation team'}.`;
    else if (st === 'approved') msg = `Approved by ${quote.approvedBy} on ${R.fmtDate(quote.approvedAt)} — ready to send to the customer.`;
    else if (st === 'sent') msg = `Sent to ${lead.company} on ${R.fmtDate(quote.sentDate)}. Mark it as awarded when the customer confirms.`;
    else if (st === 'awarded') { msg = lead.projectId ? `Awarded on ${R.fmtDate(quote.awardDate)} — project ${R.projLabel(lead.projectId)} created.` : `Awarded on ${R.fmtDate(quote.awardDate)} — the project can now be created.`; tone = 'ok'; }
    else if (!editable()) msg = 'You can view this quotation but your role cannot edit it.';
    if (quote && quote.clonedFrom && st === 'draft') msg += ` (cloned from ${quote.clonedFrom})`;
    let extra = '';
    if (quote && quote.editReason && ['draft', 'pending'].includes(st)) {
      const rv = quote.revision || {};
      msg = `Reopened after ${rv.afterSend ? 'sending to the customer' : 'approval'}${rv.by ? ` by ${rv.by}` : ''} — changes required: ${quote.editReason}. ${st === 'draft' ? 'Make the changes and submit for approval again.' : msg}`;
      tone = 'bad';
      extra = `<small class="rev-meta">Assigned to <b>${esc(rv.assignee || R.engName(quote.engineerId || lead.engineerId))}</b>${rv.from ? ` · Revised from ${esc(rv.from)}` : ''}${rv.files && rv.files.length ? ` · Revised scope: ${rv.files.map(f => `<span class="rev-file">${icon('file-text', 'ic xs')}${esc(f)}</span>`).join(' ')}` : ''}</small>`;
    }
    banner.hidden = !msg;
    banner.className = 'info-box ' + tone;
    banner.innerHTML = `${icon(tone === 'bad' ? 'alert' : tone === 'ok' ? 'check-circle' : 'info')}<span>${esc(msg)}${extra}</span>`;
  }

  function actionList() {
    const st = quote ? quote.status : 'new';
    const a = [];
    if (editable()) a.push({ label: 'Save Draft', icon: 'bookmark', cls: 'btn-ghost', run: () => save(true) });
    a.push({ label: 'Preview', icon: 'eye', cls: 'btn-ghost', run: preview });
    if (quote && R.can('quotes.create')) a.push({ label: 'Clone', icon: 'file-plus', cls: 'btn-ghost', run: cloneDialog });
    if (editable() && R.can('quotes.submit')) a.push({ label: 'Submit for Approval', icon: 'send', run: submit });
    if (st === 'pending' && R.can('quotes.approve')) {
      a.push({ label: 'Reject', icon: 'x', cls: 'btn-ghost', run: reject });
      a.push({ label: 'Approve', icon: 'check', cls: 'btn-success', run: approve });
    }
    if (['approved', 'sent'].includes(st) && R.can('quotes.edit') && R.AUTH.inScope(lead, R.me)) a.push({ label: 'Edit', icon: 'edit', cls: 'btn-ghost', run: reopen });
    if (st === 'approved' && R.can('quotes.send')) a.push({ label: 'Send Quotation', icon: 'send', run: send });
    if (st === 'sent' && R.can('quotes.award')) a.push({ label: 'Award', icon: 'award', run: () => R.awardQuote(quote) });
    if (st === 'awarded') {
      if (lead.projectId) a.push({ label: 'Open Project', icon: 'briefcase', run: () => R.go(`project.html?id=${lead.projectId}`) });
      else if (R.can('projects.create')) a.push({ label: 'Create Project', icon: 'briefcase', run: () => R.projectForm(lead.id) });
    }
    return a;
  }
  function renderHead() {
    const head = $('#pageHead');
    $('h1', head).textContent = quote ? `Quotation ${quote.number}` : 'New Quotation';
    $('p', head).textContent = `${lead.name} · ${lead.subject}`;
    const acts = actionList();
    const box = $('.page-actions', head);
    box.innerHTML = (quote ? R.quotePill(quote) : R.pill('New draft', 'amber')) + acts.map((x, i) => `<button class="btn ${x.cls || 'btn-primary'}" data-qa="${i}">${icon(x.icon)}${esc(x.label)}</button>`).join('');
    $$('[data-qa]', box).forEach(b => b.addEventListener('click', () => acts[+b.dataset.qa].run()));
  }

  /* ---------- workflow actions ---------- */
  function validateItems() {
    let ok = !!doc.items.length;
    $$('#itemsBody tr[data-i]:not(.li-photo-row)').forEach(tr => {
      const it = doc.items[+tr.dataset.i];
      const bad = { desc: !String(it.desc || '').trim(), qty: !(+it.qty > 0), price: !(+it.price > 0) };
      Object.keys(bad).forEach(k => { const inp = $(`[data-k=${k}]`, tr); inp.classList.toggle('invalid', bad[k]); if (bad[k]) ok = false; });
    });
    if (!doc.items.length) R.toast('Add at least one line item.', 'error');
    else if (!ok) R.toast('Complete the highlighted line items (description, quantity and unit price).', 'error');
    return ok;
  }
  function submit() {
    if (!validateItems()) return;
    if (!save(false)) return;
    const t = API.quoteTotals(payload());
    R.confirmDialog({ title: 'Submit for approval?', text: `<b>${esc(quote.number)}</b> · ${esc(lead.name)}<br>Grand total <b>${R.fmtAED2(t.total)}</b> (incl. VAT ${R.fmtAED2(t.tax)}).<br>The approver is notified and the quotation becomes read-only until it is approved or returned.`, ok: 'Submit for Approval' })
      .then(ok => ok && R.act(() => API.submitQuote(quote.id), `${quote.number} submitted for approval — approver notified`));
  }
  function approve() {
    R.confirmDialog({ title: `Approve ${quote.number}?`, text: `Grand total <b>${R.fmtAED2(quote.total)}</b> for ${esc(lead.company)}.<br>Once approved the quotation can be sent to the customer.`, ok: 'Approve' })
      .then(ok => ok && R.act(() => API.approveQuote(quote.id), `${quote.number} approved`));
  }
  function reject() {
    R.promptDialog({ title: `Return ${quote.number} for revision?`, label: 'Reason', placeholder: 'e.g. Increase margin on concrete works', ok: 'Request Revision', danger: true })
      .then(r => r && R.act(() => API.rejectQuote(quote.id, r), `${quote.number} returned for revision`, 'info'));
  }
  /* approved → draft: why, the revised scope, and who makes the changes (e.g. when the engineer is on leave) */
  function reopen() {
    const engs = API.engineers.filter(e => e.branchId === lead.branchId);
    const sent = quote.status === 'sent';
    const nextNo = `${quote.baseNumber || quote.number}_R${(quote.revNo || 0) + 1}`;
    /* approvers / estimation (no engineer login) may make the changes themselves */
    const assignOpts = (R.me.engineerId ? [] : [{ v: '', t: `Myself (${R.me.name}) — edit directly` }])
      .concat(engs.map(e => ({ v: e.id, t: e.id === lead.engineerId ? `${e.name} (current engineer)` : e.name })));
    const m = R.modal({
      title: `Edit ${quote.number}?`, sub: sent ? `Revision after sending: the quotation becomes ${nextNo}, dated today, and moves back to Draft. Submit it for approval again, then send the revised quotation.` : 'The quotation moves back to Draft. After editing, submit it for approval again.', size: 'md',
      body: `<form class="form-grid" id="reopenForm" novalidate>
        ${R.field('Reason for edit & changes required', '<textarea name="reason" rows="4" required maxlength="500" placeholder="Note"></textarea>', { full: 1, req: 1, star: 1 })}
        ${R.field('Revised scope documents', '<input name="files" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.dwg,.xlsx,.docx">', { full: 1, hint: 'Shared with the assigned user (file names are stored in the demo)' })}
        ${R.field('Assign to', R.selectCtl('engineerId', assignOpts, R.me.engineerId ? lead.engineerId : ''), { full: 1, star: 1, hint: `Edit it yourself, or pick an engineer from ${R.branchOf(lead.branchId).name} (e.g. if the current one is on leave)` })}
      </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="reopenForm">${icon('edit')}Move to Draft</button>`
    });
    $('#reopenForm', m.el).addEventListener('submit', e => {
      e.preventDefault();
      const fm = e.target;
      if (!R.validate(fm)) return;
      const eid = R.val(fm, 'engineerId');
      const data = { reason: R.val(fm, 'reason').trim(), files: R.fileNames(fm.elements.files), engineerId: eid || null, self: !eid };
      const moved = eid && eid !== lead.engineerId;
      if (R.act(() => API.reopenQuote(quote.id, data), () => `${quote.number} moved to draft${moved ? ` and assigned to ${R.engName(eid)}` : eid ? '' : ' — you can edit it now'}`, 'info')) {
        m.close();
        if (!editable()) R.toast(`${R.engName(eid)} now makes the changes — this quotation is read-only for you.`, 'info');
      }
    });
  }
  const SHARE_MODES = [
    { v: 'Email', label: 'Send to', ph: 'customer@company.com', email: 1 },
    { v: 'Portal Submission', label: 'Portal name / reference', ph: 'e.g. Ariba — RFQ 4471' },
    { v: 'Tender', label: 'Tender reference', ph: 'e.g. Tender no. T-2026-118' },
    { v: 'Physical Submission', label: 'Submitted to', ph: 'e.g. Procurement Dept. — receipt no.' }
  ];
  function send() {
    const m = R.modal({
      title: 'Send Quotation', sub: 'Review before sending to the customer', size: 'md',
      body: `<dl class="kv"><dt>Customer</dt><dd>${esc(lead.company)}</dd><dt>Project</dt><dd>${esc(lead.subject)}</dd><dt>Quote Number</dt><dd>${esc(quote.number)}</dd>
        <dt>Amount</dt><dd>${R.fmtAED2(quote.taxable)}</dd><dt>Tax (VAT ${(quote.taxRate * 100).toFixed(0)}%)</dt><dd>${R.fmtAED2(quote.tax)}</dd><dt>Grand Total</dt><dd><b>${R.fmtAED2(quote.total)}</b></dd></dl>
        <form class="form-grid" id="sendForm" style="margin-top:14px" novalidate>
          ${R.field('Mode of Share', R.selectCtl('mode', SHARE_MODES.map(s => ({ v: s.v, t: s.v })), SHARE_MODES[0].v))}
          ${R.field('Output', R.selectCtl('output', [{ v: 'tax', t: 'Total + Tax' }, { v: 'notax', t: 'Total without Tax' }, { v: 'tech', t: 'Technical (no pricing)' }], quote.output))}
          <label class="field full" data-to><span class="field-label"></span><input name="to"></label>
          <label class="field full" data-msg><span class="field-label">Message</span><textarea name="msg" rows="3">Dear ${esc(lead.contact || 'Sir/Madam')},\n\nPlease find attached our quotation ${esc(quote.number)} for ${esc(lead.subject)}.\n\nRegards,\n${esc(R.me.name)}</textarea></label>
          <p class="hint full">Demo: nothing is sent; the quotation status changes to Sent and the share mode is recorded.</p>
        </form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="sendForm">${icon('send')}Confirm Send</button>`
    });
    const fm = $('#sendForm', m.el), toInp = fm.elements.to;
    /* the "send to" field follows the chosen mode */
    const sync = () => {
      const s = SHARE_MODES.find(x => x.v === fm.elements.mode.value);
      $('[data-to] .field-label', fm).innerHTML = `${esc(s.label)} <b>*</b>`;
      toInp.type = s.email ? 'email' : 'text';
      toInp.placeholder = s.ph;
      toInp.required = true;
      toInp.value = s.email ? (lead.email || '') : '';
      $('[data-msg]', fm).hidden = !s.email;
      $$('.field-error', fm).forEach(x => x.remove());
      $$('.field.invalid', fm).forEach(x => x.classList.remove('invalid'));
    };
    fm.elements.mode.addEventListener('change', sync);
    sync();
    fm.addEventListener('submit', e => {
      e.preventDefault();
      if (!R.validate(fm)) return;
      const mode = fm.elements.mode.value;
      if (R.act(() => API.sendQuote(quote.id, { mode, to: toInp.value.trim() }), `Quotation marked as sent — ${mode}`)) m.close();
    });
  }
  function cloneDialog() {
    if (dirty) save(false);
    const targets = API.leads.filter(l => l.id === lead.id || (l.stage === 'new' && R.state.ctx.inScope(l))).slice(-60).reverse();
    const m = R.modal({
      title: `Clone ${quote.number}`, sub: 'Creates a new draft with the same line items. The original quotation is not changed.', size: 'md',
      body: `<form class="form-grid" id="cloneForm">${R.field('Create the copy for', R.selectCtl('leadId', [{ v: lead.id, t: `Same lead — ${lead.leadNumber} · ${lead.name}` }].concat(targets.filter(l => l.id !== lead.id).map(l => ({ v: l.id, t: `${l.leadNumber} · ${l.name} — ${l.subject}` }))), lead.id), { full: 1 })}</form>`,
      foot: `<button class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit" form="cloneForm">${icon('file-plus')}Clone Quotation</button>`
    });
    $('#cloneForm', m.el).addEventListener('submit', e => {
      e.preventDefault();
      const q = R.act(() => API.cloneQuote(quote.id, R.val(e.target, 'leadId')));
      if (!q) return;
      R.flash(`${q.number} created from ${quote.number}`);
      R.go(`quote.html?id=${q.id}`);
    });
  }

  /* ---------- preview / print / download ---------- */
  /* company letterhead format (QUOTE FORMAT HO & BR1); styles travel with the HTML so print/download match the preview */
  const QDOC_CSS = `.qdoc{font:12px Inter,Arial,sans-serif;color:#111;background:#fff;max-width:820px;margin:0 auto;padding:24px 28px}
    .qdoc table{width:100%;border-collapse:collapse}.qdoc td,.qdoc th{border:1px solid #444;padding:5px 6px;vertical-align:middle}
    .qd-head{display:flex;justify-content:space-between;align-items:center;gap:16px;border-bottom:1.5px solid #333;padding-bottom:10px}
    .qd-head img{width:150px;height:auto}.qd-co{text-align:right;line-height:1.45}.qd-co .ar{color:#941C3C;font-size:17px;font-weight:700;direction:rtl}
    .qd-co .en{color:#941C3C;font-size:15px;font-weight:600}.qd-co small{display:block;font-size:12px;color:#222}
    .qd-title{text-align:center;font-size:20px;font-weight:700;letter-spacing:.5px;margin:18px 0 10px}
    .qd-info td{font-size:11px;width:25%}.qd-info th{background:#F2F2F2;text-align:left;font-size:11px}.qd-info td.k{font-weight:700}
    .qd-proj{border:1px solid #444;margin:10px 0;padding:6px 6px 22px;font-weight:700;font-size:11px}
    .qd-items th{background:#F2F2F2;font-size:11px;text-align:center}.qd-items td{font-size:11px}.qd-items .c{text-align:center}.qd-items .r{text-align:right;font-weight:700;white-space:nowrap}
    .qd-items td.d{width:40%;white-space:pre-line}.qd-phs{display:flex;gap:6px;margin-top:6px;white-space:normal}.qd-ph{display:block;cursor:zoom-in;width:31%;max-width:110px;height:80px;object-fit:cover;border:1px solid #ccc}.qd-items .sum td{background:#DDF3E3;font-weight:700}.qd-items .grand td{background:#C9EDD3;font-weight:700}.qd-items .words td{background:#DDF3E3;text-align:center;font-weight:700}
    .qd-box{margin-top:12px}.qd-box th{background:#DCE6F1;text-align:left;font-size:11px}.qd-box td{font-size:11px;line-height:1.55}.qd-box p{margin:0;white-space:pre-line}
    .qd-box .red{color:#C00000;font-weight:700}.qd-box td.k{font-weight:700;width:50%}
    .qd-sec h4{margin:12px 0 4px;font-size:11px}.qd-sec p{margin:0;white-space:pre-line;font-size:11px}
    .qd-foot{margin-top:28px;background:#941C3C;color:#fff;text-align:center;border-radius:999px;padding:7px 12px;font-size:12.5px}
    @media print{.qdoc{padding:0}.qd-items tr{break-inside:avoid}}`;
  const ONES = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
  const TENS = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
  function words(n) {
    if (n < 20) return ONES[n];
    if (n < 100) return TENS[Math.floor(n / 10)] + (n % 10 ? ' ' + ONES[n % 10] : '');
    if (n < 1000) return ONES[Math.floor(n / 100)] + ' HUNDRED' + (n % 100 ? ' AND ' + words(n % 100) : '');
    for (const [v, w] of [[1e9, 'BILLION'], [1e6, 'MILLION'], [1e3, 'THOUSAND']]) if (n >= v) return words(Math.floor(n / v)) + ' ' + w + (n % v ? (n % v < 100 ? ' AND ' : ' ') + words(n % v) : '');
  }
  function amountWords(amt) {
    const fils = Math.round(amt * 100) % 100, aed = Math.floor(Math.round(amt * 100) / 100);
    return `UAE DIRHAMS ${aed ? words(aed) : 'ZERO'}${fils ? ` AND ${words(fils)} FILS` : ''} ONLY`;
  }
  function quoteDocHTML(mode) {
    const q = Object.assign({}, quote || {}, payload(), API.quoteTotals(payload()));
    const priced = mode !== 'tech';
    const eng = API.engineer(lead.engineerId) || {};
    const num = v => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const logo = new URL('images/rich-mount-logo.png', location.href).href;
    const cols = priced ? 6 : 4;
    const rows = q.items.map((i, n) => `<tr><td class="c">${n + 1}</td><td class="d">${esc(i.desc)}${photosOf(i).length ? `<span class="qd-phs">${photosOf(i).map(id => `<img class="qd-ph" src="${photoSrc(id)}" alt="">`).join('')}</span>` : ''}</td><td class="c">${i.qty}</td><td class="c">${esc(i.unit)}</td>${priced ? `<td class="c">${i.price ? num(i.price) : ''}</td><td class="r">${num(i.qty * i.price)}</td>` : ''}</tr>`).join('');
    const sumRow = (label, v, cls) => `<tr class="${cls || 'sum'}"><td colspan="4" style="text-align:right">${label}</td><td class="c">AED</td><td class="r">${v}</td></tr>`;
    const total = mode === 'tax' ? q.total : q.taxable;
    const totals = !priced ? '' : (q.discount || mode === 'tax' ? sumRow('SUB TOTAL', num(q.subtotal)) : '')
      + (q.discount ? sumRow('DISCOUNT', '− ' + num(q.discount)) : '')
      + (mode === 'tax' ? sumRow(`VAT ${(q.taxRate * 100).toFixed(0)}%`, num(q.tax)) : '')
      + sumRow(mode === 'tax' ? 'TOTAL AMOUNT (INCL. VAT)' : 'TOTAL AMOUNT', num(total), 'grand')
      + `<tr class="words"><td colspan="6">${amountWords(total)}</td></tr>`;
    return `<style>${QDOC_CSS}</style><div class="qdoc">
      <div class="qd-head"><img src="${logo}" alt="Rich Mount Contracting &amp; Interiors">
        <div class="qd-co"><div class="ar">ريتش ماونت للمقاولات والداخلية ذ م م</div><div class="en">Rich Mount Contracting &amp; Interiors L.L.C</div>
          <small>Tel: 042950285 | PO Box 97921 | Dubai, UAE</small><small>www.rich-mounts.com | info@rich-mounts.com</small></div></div>
      <div class="qd-title">${mode === 'tech' ? 'TECHNICAL QUOTATION' : 'QUOTATION'}</div>
      <table class="qd-info"><tr><th colspan="2">CLIENT DETAILS:</th><th colspan="2">QUOTE DETAILS:</th></tr>
        <tr><td class="k">Company Name:</td><td>${esc(lead.company || lead.name)}</td><td class="k">Date:</td><td>${esc(R.fmtDate(new Date(q.date)))}</td></tr>
        <tr><td class="k">Address:</td><td>${esc(lead.address || '—')}</td><td class="k">Quote Number:</td><td>${esc(quote ? quote.number : 'DRAFT')}</td></tr>
        <tr><td class="k">PR Number:</td><td>${esc(lead.rfqNumber || 'N/A')}</td><td class="k">Site Visited By:</td><td>${esc((eng.name || '—').toUpperCase())}</td></tr></table>
      <div class="qd-proj">PROJECT: ${esc(lead.subject)}</div>
      <table class="qd-items"><thead><tr><th>Sl. No.</th><th>Description</th><th>Qty</th><th>Unit</th>${priced ? '<th>Rate</th><th>Price</th>' : ''}</tr></thead>
        <tbody>${rows || `<tr><td colspan="${cols}" class="c">No items</td></tr>`}${totals}</tbody></table>
      ${mode === 'tech' && q.techDesc ? `<div class="qd-sec"><h4>TECHNICAL DESCRIPTION:</h4><p>${esc(q.techDesc)}</p></div>` : ''}
      <table class="qd-box"><tr><th>TERMS AND CONDITIONS:</th></tr><tr><td><p>${esc(q.terms || '—')}</p></td></tr></table>
      ${priced ? `<table class="qd-box"><tr><th>PAYMENT TERMS:</th></tr><tr><td><p><span class="red">1. 50% Advance against security cheque and balance after completion of work.</span>
2. All invoices will be charged ${(q.taxRate * 100).toFixed(0)}% VAT as per UAE law.
3. Payment Cheque or Bank Payee to: <b>RICH MOUNT CONTRACTING &amp; INTERIORS L.L.C.</b></p></td></tr></table>` : ''}
      <table class="qd-box"><tr><th colspan="2">POINT OF CONTACT</th></tr>
        <tr><td class="k">Engineer Name:</td><td>${esc(eng.name || '—')}</td></tr>
        <tr><td class="k">Position:</td><td>Project Engineer</td></tr>
        <tr><td class="k">Email:</td><td>${esc(eng.email || '—')}</td></tr></table>
      <div class="qd-foot">Office No. 05, Al Wasl Building, Street 47, Al Khor 2. Dubai UAE</div></div>`;
  }
  function preview() {
    let mode = doc.output;
    const m = R.modal({
      title: quote ? `Preview · ${quote.number}` : 'Preview · New Quotation', size: 'lg', noFocus: true,
      body: `<div class="seg preview-seg">${[['tax', 'Total + Tax'], ['notax', 'Without Tax'], ['tech', 'Technical']].map(o => `<label><input type="radio" name="pmode" value="${o[0]}"${o[0] === mode ? ' checked' : ''}><span>${o[1]}</span></label>`).join('')}</div><div id="docHost"></div>`,
      foot: `<button class="btn btn-ghost" data-share>${icon('send')}Share link</button><button class="btn btn-ghost" data-dl>${icon('download')}Download</button><button class="btn btn-primary" data-print>${icon('printer')}Print</button>`
    });
    const draw = () => { $('#docHost', m.el).innerHTML = quoteDocHTML(mode); };
    draw();
    m.el.addEventListener('change', e => { if (e.target.name === 'pmode') { mode = e.target.value; draw(); } });
    $('#docHost', m.el).addEventListener('click', e => { if (e.target.matches('.qd-ph')) viewPhoto(e.target.src); });
    const full = () => `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"><style>${R.DOC_CSS}</style>${quoteDocHTML(mode)}`;
    const name = quote ? quote.number : 'quotation-draft';
    $('[data-print]', m.el).addEventListener('click', () => R.printHTML(name, full(), R.DOC_CSS));
    $('[data-dl]', m.el).addEventListener('click', () => { R.downloadFile(`${name}.html`, `<!doctype html><meta charset="utf-8"><title>${name}</title>${full()}`, 'text/html'); R.toast(`${name} downloaded`); });
    $('[data-share]', m.el).addEventListener('click', () => {
      const url = location.href.split('?')[0] + (quote ? `?id=${quote.id}` : `?lead=${lead.id}`);
      (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => R.toast('Link copied to clipboard'), () => R.toast(url, 'info'));
    });
  }

  /* ---------- events ---------- */
  function bind() {
    $('#itemsBody').addEventListener('input', e => {
      const tr = e.target.closest('tr[data-i]'), k = e.target.dataset.k;
      if (!tr || !k) return;
      const it = doc.items[+tr.dataset.i];
      it[k] = e.target.value;
      e.target.classList.remove('invalid');
      $('.li-total', tr).textContent = R.fmtAED2(itemTotal(it));
      renderTotals();
      markDirty();
    });
    $('#itemsBody').addEventListener('change', e => {
      if (!e.target.matches('[data-photo]') || !e.target.files.length) return;
      const it = doc.items[+e.target.closest('tr[data-i]').dataset.i];
      const room = MAX_PHOTOS - photosOf(it).length;
      const files = [...e.target.files];
      if (files.length > room) R.toast(`Only ${MAX_PHOTOS} photos per item — the first ${room} were added.`, 'info');
      Promise.all(files.slice(0, room).map(readPhoto)).then(srcs => {
        const ids = srcs.map(src => {
          const id = 'p' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
          R.store.set('qphoto.' + id, src);
          return id;
        });
        it.photos = photosOf(it).concat(ids);
        delete it.photo;
        renderItems(); markDirty();
      }, err => R.toast(err.message, 'error'));
    });
    $('#itemsBody').addEventListener('click', e => {
      const pv = e.target.closest('[data-photo-view]');
      if (pv) { viewPhoto(pv.src); return; }
      const pr = e.target.closest('[data-photo-remove]');
      if (pr) {
        const it = doc.items[+pr.closest('tr[data-i]').dataset.i];
        it.photos = photosOf(it).filter((_, n) => n !== +pr.dataset.photoRemove);
        delete it.photo;
        renderItems(); markDirty();
        return;
      }
      const b = e.target.closest('[data-remove]');
      if (!b) return;
      doc.items.splice(+b.closest('tr').dataset.i, 1);
      renderItems(); renderTotals(); markDirty();
    });
    $('#addItem').addEventListener('click', () => {
      doc.items.push(blankItem());
      renderItems(); renderTotals(); markDirty();
      $(`#itemsBody tr[data-i="${doc.items.length - 1}"] .li-desc`).focus();
    });
    $('#addCatalog').addEventListener('click', e => {
      const own = API.CATALOG[lead.department], others = Object.keys(API.CATALOG).filter(k => k !== lead.department);
      R.menu(e.currentTarget, [{ header: R.deptName(lead.department) }].concat(own.map(c => ({ label: c[0], hint: c[1], run: () => addCatalogItem(c) })))
        .concat([{ divider: true }, { header: 'Other trades' }]).concat(others.flatMap(k => API.CATALOG[k].slice(0, 2).map(c => ({ label: c[0], hint: c[1], run: () => addCatalogItem(c) })))), { align: 'right', width: 280 });
    });
    function addCatalogItem(c) {
      const empty = doc.items.findIndex(i => !String(i.desc || '').trim());
      const item = { desc: c[0], unit: c[1], qty: 1, price: '' };
      if (empty > -1) doc.items[empty] = item; else doc.items.push(item);
      renderItems(); renderTotals(); markDirty();
      const idx = empty > -1 ? empty : doc.items.length - 1;
      $(`#itemsBody tr[data-i="${idx}"] .li-price`).focus();
    }
    $('#qMeta').addEventListener('change', e => {
      const n = e.target.name;
      if (n === 'date' && e.target.value) doc.date = R.parseISO(e.target.value);
      else if (n === 'validity') doc.validity = +e.target.value;
      else if (n === 'output') doc.output = e.target.value;
      markDirty();
    });
    $('#qText').addEventListener('input', e => { doc[e.target.name] = e.target.value; markDirty(); });
    $('#qTotals').addEventListener('input', e => {
      const n = e.target.name;
      if (n === 'discount') doc.discount = e.target.value;
      if (n === 'discountType') doc.discountType = e.target.value;
      if (n === 'taxRate') doc.taxRate = +e.target.value;
      renderTotals(); markDirty();
    });
    $('#qTotals').addEventListener('change', e => { if (e.target.tagName === 'SELECT') e.target.dispatchEvent(new Event('input', { bubbles: true })); });
    $('#attInput').addEventListener('change', e => {
      doc.attachments = doc.attachments.concat(R.fileNames(e.target));
      e.target.value = '';
      renderAttachments(); markDirty();
    });
    $('#attList').addEventListener('click', e => {
      const b = e.target.closest('[data-remove-att]');
      if (!b) return;
      doc.attachments.splice(+b.dataset.i, 1);
      renderAttachments(); markDirty();
    });
    $('#wsTabs').addEventListener('click', e => {
      const c = e.target.closest('[data-close-tab]');
      if (c) {
        R.store.set('openQuotes', R.store.get('openQuotes', []).filter(id => id !== c.dataset.closeTab));
        if (quote && c.dataset.closeTab === quote.id) { if (dirty) save(false); R.go('quotations.html'); return; }
        renderTabs();
        return;
      }
      if (e.target.closest('#wsMin')) {
        if (dirty) save(false);
        R.toast('Quotation minimized — reopen it from the tab bar', 'info');
        setTimeout(() => { if (history.length > 1) history.back(); else R.go('quotations.html'); }, 250);
      }
    });
  }

  let shownKey = '';
  R.page({
    id: 'quotes',
    permission: 'quotes.view',
    title: quote ? `Quotation ${quote.number}` : 'New Quotation',
    sub: lead ? `${lead.name} · ${lead.subject}` : '',
    crumbs: [['Quotations', 'quotations.html'], [quote ? quote.number : 'New Quotation']],
    allow: () => !!lead && R.AUTH.inScope(quote || lead, R.me) && (!!quote || R.AUTH.canSeeLead(lead, R.me)),
    deniedTitle: 'Quotation not available',
    deniedText: 'The quotation was not found, or it is outside your branch / assignment access.',
    mount() {
      if (!quote && !R.can('quotes.create')) { R.toast('Your role cannot create quotations.', 'error'); }
      bind();
      rememberTab();
      renderTabs();
    },
    render() {
      const key = `${editable()}:${quote ? quote.status : 'draft'}`;
      if (key !== shownKey) { if (quote && !dirty) doc = snapshot(quote); fillForm(); shownKey = key; }
      renderHead();
      renderStatus();
      R.renderTimeline($('#qHistory'), quote ? API.eventsFor('quoteId', quote.id) : []);
      if (!saveTimer) setAuto(lastSaved ? 'saved' : '');
    }
  });
})();
