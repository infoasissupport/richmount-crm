/* =========================================================
   Rich Mount ERP — demo data layer (single source of truth)

   Every page reads and writes through window.RM_API. When the
   Laravel API is ready, these functions become HTTP calls and the
   UI does not change.

   Models:  Lead · Quote (with line items) · Project · Expense ·
            Transfer · Payment · Invoice · Event (activity log) ·
            Notification · ZohoSync
   ========================================================= */
(function () {
  'use strict';

  /* ---------- deterministic random: same seed data every load ---------- */
  function mulberry32(a) {
    return function () {
      a |= 0; a = (a + 0x6D2B79F5) | 0;
      let t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  const rnd = mulberry32(20260924);
  const pick = a => a[Math.floor(rnd() * a.length)];
  const randInt = (a, b) => Math.floor(a + rnd() * (b - a + 1));
  const weighted = items => {
    let u = rnd() * items.reduce((s, i) => s + i.weight, 0);
    for (const i of items) { if ((u -= i.weight) <= 0) return i; }
    return items[items.length - 1];
  };
  const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
  const minDate = (a, b) => (a < b ? a : b);
  const pad = (n, l = 2) => String(n).padStart(l, '0');
  const sum = (arr, f) => arr.reduce((s, x) => s + f(x), 0);
  const round2 = n => Math.round(n * 100) / 100;
  const DAY = 864e5;

  /* Demo "today". Times of day come from the real clock; while the saved
     journal is replayed the clock is pinned to when each action happened. */
  const TODAY = new Date(2026, 9, 6);
  let clock = null;
  function now() {
    if (clock) return new Date(clock);
    const r = new Date();
    return new Date(TODAY.getFullYear(), TODAY.getMonth(), TODAY.getDate(), r.getHours(), r.getMinutes(), r.getSeconds());
  }
  const AUTH = window.RM_AUTH;
  /* the user performing actions: the logged-in demo user (see js/auth.js) */
  let actor = AUTH.session();

  /* =======================================================
     Master data
     ======================================================= */
  const countries = [{ id: 'UAE', name: 'UAE' }, { id: 'OMN', name: 'Oman' }];
  const branches = [
    { id: 'dxb', name: 'Dubai Branch', short: 'Dubai', code: 'Branch 1', country: 'UAE', quoteFormat: 'Quote Format 1', weight: .4 },
    { id: 'auh', name: 'Abu Dhabi Branch', short: 'Abu Dhabi', code: 'Branch 2', country: 'UAE', quoteFormat: 'Quote Format 2', weight: .27 },
    { id: 'shj', name: 'Sharjah Branch', short: 'Sharjah', code: 'Branch 3', country: 'UAE', quoteFormat: 'Quote Format 3', weight: .18 },
    { id: 'mct', name: 'Muscat Branch', short: 'Muscat', code: 'Branch 4', country: 'OMN', quoteFormat: 'Quote Format 4', weight: .15 }
  ];
  const departments = [
    { id: 'construction', name: 'Construction', weight: .4 },
    { id: 'interior', name: 'Interior & Fit-out', weight: .28 },
    { id: 'renovation', name: 'Renovation', weight: .18 },
    { id: 'mep', name: 'MEP Works', weight: .14 }
  ];
  const engineers = [
    { id: 'e1', name: 'Ali Khan', branchId: 'dxb', email: 'ali.khan@richmount.ae' },
    { id: 'e2', name: 'Ramesh Nair', branchId: 'dxb', email: 'ramesh.nair@richmount.ae' },
    { id: 'e3', name: 'Faisal Ahmed', branchId: 'auh', email: 'faisal.ahmed@richmount.ae' },
    { id: 'e4', name: 'Priya Menon', branchId: 'auh', email: 'priya.menon@richmount.ae' },
    { id: 'e5', name: 'Sarah Thomas', branchId: 'shj', email: 'sarah.thomas@richmount.ae' },
    { id: 'e6', name: 'Omar Haddad', branchId: 'mct', email: 'omar.haddad@richmount.ae' }
  ];
  engineers.forEach(e => { e.pettyCash = 'Petty Cash – ' + e.name; e.openingCash = 5000; });

  const users = [
    { name: 'John Doe', role: 'Super Admin', branch: 'All Branches', email: 'john.doe@richmount.ae' },
    { name: 'Aisha Rahman', role: 'Estimation User', branch: 'Dubai Branch', email: 'aisha.rahman@richmount.ae' },
    { name: 'Meera Joseph', role: 'Accountant', branch: 'All Branches', email: 'meera.joseph@richmount.ae' }
  ].concat(engineers.map(e => ({ name: e.name, role: 'Project Engineer', branch: branches.find(b => b.id === e.branchId).name, email: e.email })));

  const sources = ['Website', 'Referral', 'Exhibition', 'Email', 'Phone', 'Social Media'];
  const vendors = ['ACE Hardware', 'Dragon Mart Supplies', 'ENOC Fuel', 'Danube Building Materials', 'Al Masaood Tools',
    'Emirates Steel Trading', 'Union Paints', 'Gulf Electricals', 'City Plumbing Supplies', 'ABC Trading'];
  /* the seed picks from this list — keep it unchanged so the sample data stays the same */
  const seedCategories = ['Materials', 'Fuel', 'Tools', 'Transport', 'Labour', 'Site Supplies', 'Meals', 'Permits'];
  /* vehicle expenses are booked against a vehicle, never against a project */
  const vehicleCategories = ['Fuel', 'Vehicle Maintenance'];
  const categories = seedCategories.concat('Vehicle Maintenance');
  const isVehicleCategory = c => vehicleCategories.includes(c);
  const vehicles = ['DXB A 12345', 'DXB B 23871', 'AUH 5 61207', 'SHJ 3 44120'];
  const paymentMethods = [{ id: 'cash', name: 'Cash (petty cash)' }, { id: 'card', name: 'Company Card' }];
  /* company cards offered when an expense is paid by card */
  const companyCards = ['Visa •••• 4821', 'Visa •••• 5307', 'Mastercard •••• 6142', 'Mastercard •••• 7790'];

  const stages = [
    { id: 'new', name: 'New Leads', short: 'Lead' },
    { id: 'draft', name: 'Draft Quotations', short: 'Draft' },
    { id: 'sent', name: 'Sent Quotations', short: 'Sent' },
    { id: 'awarded', name: 'Awarded', short: 'Awarded' }
  ];
  const quoteStatuses = [
    { id: 'draft', name: 'Draft', tone: 'amber' },
    { id: 'pending', name: 'Pending Approval', tone: 'purple' },
    { id: 'approved', name: 'Approved', tone: 'blue' },
    { id: 'rejected', name: 'Revision Required', tone: 'red' },
    { id: 'sent', name: 'Sent', tone: 'pink' },
    { id: 'awarded', name: 'Awarded', tone: 'primary' }
  ];
  const projectStatuses = [
    { id: 'popending', name: 'PO Pending', color: '#C9A227' },
    { id: 'open', name: 'Open', color: '#8E1B3A' },
    { id: 'inprogress', name: 'In Progress', color: '#E8819B' },
    { id: 'onhold', name: 'On Hold', color: '#F2A93B' },
    { id: 'completed', name: 'Work Completed', color: '#6A3EBF' },
    { id: 'invoicepending', name: 'Invoice Pending', color: '#2B69CF' },
    { id: 'closed', name: 'Closed', color: '#2E9E5B' }
  ];

  const firstNames = ['Ahmed', 'Fatima', 'Rashid', 'Mariam', 'Khalid', 'Noura', 'Imran', 'Sara', 'Yousef', 'Layla', 'Hamad', 'Reem'];
  const lastNames = ['Al Mansoori', 'Hassan', 'Khan', 'Al Suwaidi', 'Nair', 'Al Hashimi', 'Qureshi', 'Mathew', 'Al Falasi', 'Saeed'];
  const clients = [
    ['Al Futtaim Group', 'Al Futtaim Trading'], ['Emirates Build', 'Emirates Build LLC'], ['Gulf Infrastructure', 'Gulf Infra'],
    ['Desert Tech', 'Desert Tech LLC'], ['Modern Contracts', 'Modern Contracts'], ['ABC Constructions', 'ABC Constructions LLC'],
    ['Skyline Builders', 'Skyline Builders'], ['Green Valley Homes', 'Green Valley Real Estate'], ['Urban Developers', 'Urban Developers FZE'],
    ['Royal Residency', 'Royal Residency Hotels'], ['Maple Builders', 'Maple Builders'], ['Classic Villas', 'Classic Villas LLC'],
    ['Dream Homes', 'Dream Homes Realty'], ['Palm Vista Properties', 'Palm Vista'], ['Marina Heights', 'Marina Heights Owners'],
    ['Oasis Developments', 'Oasis Dev Co.'], ['Falcon Logistics', 'Falcon Logistics LLC'], ['Crescent Hospitality', 'Crescent Hotels'],
    ['Najm Retail', 'Najm Retail Group'], ['Sahara Steel', 'Sahara Steel Industries'], ['Blue Lagoon Resorts', 'Blue Lagoon'],
    ['Atlas Facilities', 'Atlas FM']
  ];
  const SUBJECTS = {
    construction: ['Residential Project', 'Commercial Building', 'Villas Construction', 'Apartment Project', 'Full Construction', 'Warehouse Build'],
    interior: ['Interior Work', 'Interior & Design', 'Office Fit-out', 'Retail Fit-out', 'Hotel Lobby Fit-out'],
    renovation: ['Renovation Project', 'Villa Refurbishment', 'Façade Upgrade', 'Kitchen Remodel'],
    mep: ['HVAC Installation', 'MEP Package', 'Electrical Upgrade', 'Plumbing Works']
  };
  const CATALOG = {
    construction: [['Site preparation & excavation', 'lump sum'], ['Reinforced concrete works', 'm³'], ['Blockwork & plastering', 'm²'], ['Structural steel supply & erection', 'ton'], ['Waterproofing membrane', 'm²'], ['MEP first fix', 'lump sum']],
    interior: [['Gypsum ceiling works', 'm²'], ['Joinery & fitted furniture', 'lump sum'], ['Porcelain floor tiling', 'm²'], ['Painting & decoration', 'm²'], ['Glass partitions', 'm²'], ['Lighting fixtures', 'nos']],
    renovation: [['Demolition & strip-out', 'lump sum'], ['Wall repairs & re-plastering', 'm²'], ['Façade cleaning & coating', 'm²'], ['Kitchen cabinets replacement', 'set'], ['Bathroom refurbishment', 'nos']],
    mep: [['Split AC units supply & install', 'nos'], ['Ducting & insulation', 'm²'], ['DB & cabling works', 'lump sum'], ['Plumbing pipework', 'm'], ['Fire alarm devices', 'nos'], ['Testing & commissioning', 'lump sum']]
  };
  const TECH = {
    construction: 'Works executed as per approved drawings, Dubai Municipality regulations and the agreed specification. Concrete grade C40, rebar to BS 4449.',
    interior: 'Fit-out executed as per approved design intent drawings and material board. All materials to be fire-rated where required by Civil Defence.',
    renovation: 'Renovation works executed in occupied premises with dust control. Existing services protected and reinstated on completion.',
    mep: 'MEP installation as per approved shop drawings, DEWA / Civil Defence requirements, with testing and commissioning certificates.'
  };
  const DEFAULT_TERMS = '1. Prices are in AED and valid for the period stated.\n2. 30% advance; balance against monthly progress invoices.\n3. VAT charged at 5% where applicable.\n4. Variations will be quoted separately.\n5. Completion period to be agreed on award.';
  const QUOTE_RANGE = { construction: [180000, 950000], interior: [45000, 320000], renovation: [30000, 220000], mep: [25000, 260000] };
  const EXPENSE_DESC = {
    Materials: 'Site materials purchase', Fuel: 'Fuel for site vehicle', Tools: 'Hand tools and consumables', Transport: 'Material transport',
    Labour: 'Additional daily labour', 'Site Supplies': 'Site consumables', Meals: 'Site team meals', Permits: 'Municipality permit fee',
    'Vehicle Maintenance': 'Vehicle service and repairs'
  };

  /* =======================================================
     Collections
     ======================================================= */
  const leads = [], quotes = [], projects = [], expenses = [], invoices = [], transfers = [], payments = [], events = [];
  let leadSeq = 0, quoteSeq = 0, projSeq = 0, expSeq = 0, invSeq = 0, trSeq = 0, paySeq = 0, evSeq = 0, nSeq = 100, zSeq = 0;
  const quoteById = new Map();
  const settings = { quoteApprover: 'estimation' };
  AUTH.setDynamicPermissions(role => (role === settings.quoteApprover ? ['quotes.approve'] : []));

  function logEvent(at, text, kind, refs, by) {
    events.push(Object.assign({ id: 'V' + (++evSeq), at: new Date(at), text, kind, by: by || actor.name }, refs || {}));
  }

  /* ---------- quote maths ---------- */
  function quoteTotals(q) {
    const subtotal = round2(sum(q.items || [], i => (+i.qty || 0) * (+i.price || 0)));
    const discount = round2(Math.min(subtotal, q.discountType === 'pct' ? subtotal * (+q.discount || 0) / 100 : (+q.discount || 0)));
    const taxable = round2(subtotal - discount);
    const tax = round2(taxable * (+q.taxRate || 0));
    return { subtotal, discount, taxable, tax, total: round2(taxable + tax) };
  }
  function recalc(q) {
    const t = quoteTotals(q);
    q.subtotal = t.subtotal; q.discountAmount = t.discount; q.taxable = t.taxable; q.tax = t.tax; q.total = t.total;
    q.amount = t.taxable;
    return q;
  }
  function makeItems(dept, target) {
    /* seeded Fisher–Yates shuffle (sort() with a random comparator is engine-dependent) */
    const deck = CATALOG[dept].slice();
    for (let i = deck.length - 1; i > 0; i--) { const j = Math.floor(rnd() * (i + 1)); const t = deck[i]; deck[i] = deck[j]; deck[j] = t; }
    const pool = deck.slice(0, randInt(2, 4));
    const w = pool.map(() => .4 + rnd());
    const ws = w.reduce((a, b) => a + b, 0);
    return pool.map((p, i) => {
      const unit = p[1];
      const qty = unit === 'lump sum' ? 1 : unit === 'ton' ? randInt(4, 60) : (unit === 'nos' || unit === 'set') ? randInt(2, 30) : randInt(20, 400);
      return { desc: p[0], unit, qty, price: Math.max(1, Math.round(target * (w[i] / ws) / qty)) };
    });
  }

  function attachQuoteGetter(lead) {
    Object.defineProperty(lead, 'quote', { get() { return lead.quoteId ? quoteById.get(lead.quoteId) || null : null; }, enumerable: false, configurable: true });
  }
  function syncStage(lead, at) {
    const q = lead.quote;
    const st = !q ? 'new' : q.status === 'sent' ? 'sent' : q.status === 'awarded' ? 'awarded' : 'draft';
    if (st !== lead.stage) { lead.stage = st; lead.stageDate = new Date(at || now()); }
    lead.awardDate = q && q.awardDate ? q.awardDate : null;
    if (q) lead.lost = false;
  }

  function newQuote(lead, date, data) {
    const seq = ++quoteSeq;
    const q = Object.assign({
      id: 'Q' + seq, number: `QT-${date.getFullYear()}-${pad(seq, 4)}`,
      leadId: lead.id, branchId: lead.branchId, engineerId: lead.engineerId, department: lead.department,
      status: 'draft', date: new Date(date), validity: 30, output: 'tax',
      items: [], discountType: 'amt', discount: 0, taxRate: .05,
      techDesc: TECH[lead.department], terms: DEFAULT_TERMS, notes: '', attachments: [],
      submittedAt: null, approvedAt: null, approvedBy: null, rejectReason: '', sentDate: null, awardDate: null,
      clonedFrom: null, createdAt: new Date(date), updatedAt: new Date(date)
    }, data || {});
    recalc(q);
    quotes.push(q);
    quoteById.set(q.id, q);
    lead.quoteId = q.id;
    return q;
  }

  /* =======================================================
     Seed generation
     ======================================================= */
  function contactFor(company) {
    const f = pick(firstNames), l = pick(lastNames);
    const dom = company.toLowerCase().replace(/[^a-z]+/g, '').slice(0, 14) + '.ae';
    return { contact: `${f} ${l}`, phone: `+971 5${randInt(0, 8)} ${randInt(100, 999)} ${randInt(1000, 9999)}`, email: `${f.toLowerCase()}.${l.toLowerCase().replace(/\s+/g, '')}@${dom}` };
  }

  function makeLead(date) {
    const br = weighted(branches);
    const eng = pick(engineers.filter(e => e.branchId === br.id));
    const dept = weighted(departments);
    const cl = pick(clients);
    const r = QUOTE_RANGE[dept.id];
    const lead = Object.assign({
      id: 'L' + (++leadSeq), leadNumber: `LD-${date.getFullYear()}-${pad(leadSeq, 4)}`,
      name: cl[0], company: cl[1], subject: pick(SUBJECTS[dept.id]),
      department: dept.id, branchId: br.id, engineerId: eng.id, source: pick(sources),
      estValue: Math.round((r[0] + rnd() * (r[1] - r[0])) / 1000) * 1000,
      createdAt: date, leadDate: date, stage: 'new', stageDate: date, quoteId: null, awardDate: null, projectId: null,
      lost: false, notes: '', attachments: []
    }, contactFor(cl[1]));
    attachQuoteGetter(lead);
    leads.push(lead);
    logEvent(date, 'Lead created', 'lead', { leadId: lead.id }, 'Aisha Rahman');
    logEvent(date, `Assigned to ${eng.name}`, 'lead', { leadId: lead.id }, 'Aisha Rahman');

    const age = (TODAY - date) / DAY, u = rnd();
    let target;
    if (age < 10) target = u < .7 ? 'new' : 'draft';
    else if (age < 35) target = u < .35 ? 'new' : u < .72 ? 'draft' : 'sent';
    else target = u < .2 ? 'new' : u < .38 ? 'draft' : u < .68 ? 'sent' : 'awarded';

    if (target !== 'new') {
      const qd = minDate(addDays(date, randInt(1, 6)), TODAY);
      const q = newQuote(lead, qd, { items: makeItems(dept.id, lead.estValue * (.85 + rnd() * .3)) });
      logEvent(qd, `Quotation ${q.number} created`, 'quote', { leadId: lead.id, quoteId: q.id }, eng.name);
      if (target === 'draft') {
        const v = rnd();
        if (v < .25) {
          q.status = 'pending'; q.submittedAt = minDate(addDays(qd, 1), TODAY);
          logEvent(q.submittedAt, `${q.number} submitted for approval`, 'quote', { leadId: lead.id, quoteId: q.id }, eng.name);
        } else if (v < .4) {
          q.status = 'approved'; q.submittedAt = minDate(addDays(qd, 1), TODAY); q.approvedAt = q.submittedAt; q.approvedBy = 'Aisha Rahman';
          logEvent(q.approvedAt, `${q.number} approved`, 'quote', { leadId: lead.id, quoteId: q.id }, 'Aisha Rahman');
        }
      } else {
        q.status = 'sent';
        q.submittedAt = q.date; q.approvedAt = q.date; q.approvedBy = 'Aisha Rahman';
        q.sentDate = minDate(addDays(qd, randInt(1, 8)), TODAY);
        logEvent(q.approvedAt, `${q.number} approved`, 'quote', { leadId: lead.id, quoteId: q.id }, 'Aisha Rahman');
        logEvent(q.sentDate, `${q.number} sent to ${lead.company}`, 'quote', { leadId: lead.id, quoteId: q.id }, eng.name);
        if (target === 'awarded') {
          q.status = 'awarded';
          q.awardDate = minDate(addDays(q.sentDate, randInt(6, 28)), TODAY);
          logEvent(q.awardDate, `${q.number} awarded`, 'quote', { leadId: lead.id, quoteId: q.id }, 'Aisha Rahman');
        }
      }
      syncStage(lead, q.awardDate || q.sentDate || q.date);
    }
    if (lead.stage === 'new' && age > 60) lead.lost = true;
  }

  for (let d = new Date(2024, 0, 1); d <= TODAY; d = addDays(d, 1)) {
    const season = 1 + 0.22 * Math.sin(((d.getMonth() + 3) / 12) * Math.PI * 2);
    const rate = (d.getFullYear() === 2026 ? 1.3 : d.getFullYear() === 2025 ? 1.12 : 1) * season * (d.getDay() === 5 ? 0.35 : 1);
    const n = Math.floor(rate + rnd());
    for (let k = 0; k < n; k++) makeLead(new Date(d));
  }

  function makeProject(lead, created, data) {
    const q = lead.quote;
    const p = Object.assign({
      id: 'P' + (++projSeq),
      projectNumber: `PRJ-${created.getFullYear()}-${pad(projSeq, 3)}`,
      name: `${lead.name} – ${lead.subject}`,
      leadId: lead.id, quoteId: q.id, quoteNumber: q.number,
      poNumber: null, poDate: null, poValue: q.amount,
      branchId: lead.branchId, engineerId: lead.engineerId, department: lead.department,
      value: q.amount, status: 'popending', createdAt: new Date(created), startDate: new Date(created),
      endDate: null, completedAt: null, closedAt: null, description: '', documents: [], completionNotes: '', zoho: 'synced'
    }, data || {});
    lead.projectId = p.id;
    projects.push(p);
    return p;
  }

  leads.filter(l => l.stage === 'awarded').forEach(l => {
    const created = addDays(l.awardDate, randInt(2, 6));
    if (created > TODAY) return; // awarded, project not created yet
    const p = makeProject(l, created);
    const engN = engineers.find(e => e.id === l.engineerId).name;
    logEvent(created, `Project ${p.projectNumber} created`, 'project', { leadId: l.id, projectId: p.id }, 'Meera Joseph');
    const poDate = addDays(created, randInt(1, 8));
    if (poDate > TODAY) return; // still waiting for the client PO
    p.poNumber = `PO-${poDate.getFullYear()}-${randInt(1000, 9999)}`;
    p.poDate = poDate; p.startDate = poDate; p.status = 'open';
    p.documents.push({ name: `${p.poNumber}.pdf`, kind: 'Purchase Order', at: poDate });
    logEvent(poDate, `PO ${p.poNumber} received — project opened`, 'project', { leadId: l.id, projectId: p.id }, 'Meera Joseph');
    const run = (TODAY - poDate) / DAY, dur = randInt(50, 170);
    if (run < 10) return;
    p.status = 'inprogress';
    logEvent(addDays(poDate, 3), 'Status changed to In Progress', 'project', { projectId: p.id }, engN);
    if (run > dur) {
      if (rnd() < .1) { p.status = 'onhold'; return; }
      p.completedAt = addDays(poDate, dur); p.endDate = p.completedAt;
      p.status = 'completed';
      p.completionNotes = 'All works completed and handed over to the client.';
      logEvent(p.completedAt, 'Work completion report submitted', 'project', { projectId: p.id }, engN);
    } else if (rnd() < .1) p.status = 'onhold';
  });

  projects.forEach(p => {
    if (!p.poNumber) return;
    let stop = p.endDate || TODAY;
    if (p.status === 'onhold') stop = minDate(addDays(p.startDate, 40), TODAY);
    for (let d = addDays(p.startDate, randInt(1, 6)); d <= stop; d = addDays(d, randInt(5, 13))) {
      const type = rnd() < .58 ? 'cash' : 'card';
      const age = (TODAY - d) / DAY;
      const category = pick(seedCategories);
      const status = age < 14 ? (rnd() < .6 ? 'pending' : 'approved') : (rnd() < .03 ? 'rejected' : 'approved');
      const vehicle = isVehicleCategory(category);
      expenses.push({
        id: 'X' + (++expSeq), ref: `EXP-${pad(expSeq, 5)}`,
        projectId: vehicle ? null : p.id, vehicle: vehicle ? vehicles[Number(p.engineerId.slice(1)) % vehicles.length] : null,
        branchId: p.branchId, engineerId: p.engineerId, department: vehicle ? null : p.department,
        type, card: type === 'card' ? companyCards[Number(p.engineerId.slice(1)) % companyCards.length] : null,
        amount: type === 'cash' ? randInt(120, 2400) : randInt(300, 4200),
        date: new Date(d), vendor: pick(vendors), category, description: EXPENSE_DESC[category],
        status, rejectReason: status === 'rejected' ? 'Receipt not legible' : '',
        receipt: 'receipt-' + expSeq + '.jpg', zoho: status === 'approved' ? 'synced' : 'not-sent', createdAt: new Date(d)
      });
    }
  });

  function seedInvoice(p, date, share, final, status) {
    date = minDate(date, TODAY);
    const amount = Math.round(p.value * share);
    const inv = {
      id: 'I' + (++invSeq), number: `INV-${date.getFullYear()}-${pad(invSeq, 4)}`,
      projectId: p.id, quoteNumber: p.quoteNumber, branchId: p.branchId, engineerId: p.engineerId, department: p.department,
      date, amount, taxRate: .05, tax: round2(amount * .05), total: round2(amount * 1.05), final: !!final, status: status || 'submitted', notes: ''
    };
    invoices.push(inv);
    return inv;
  }
  projects.forEach(p => {
    if (!['inprogress', 'onhold', 'completed'].includes(p.status)) return;
    const m1 = addDays(p.startDate, 30);
    if (m1 <= TODAY && (!p.endDate || m1 < p.endDate)) seedInvoice(p, m1, .3);
    const m2 = addDays(p.startDate, 90);
    if (m2 <= TODAY && (!p.endDate || m2 < p.endDate) && p.status !== 'onhold') seedInvoice(p, m2, .4);
    if (p.status === 'completed') {
      const age = (TODAY - p.completedAt) / DAY;
      if (age < 8) return; // work completed, invoice not raised yet
      const remaining = p.value - sum(invoices.filter(i => i.projectId === p.id), i => i.amount);
      const inv = seedInvoice(p, addDays(p.completedAt, randInt(2, 6)), remaining / p.value, true, age < 16 ? 'draft' : 'submitted');
      logEvent(inv.date, `Final invoice ${inv.number} created`, 'invoice', { projectId: p.id }, 'Meera Joseph');
      if (inv.status === 'draft') p.status = 'invoicepending';
      else {
        p.status = 'closed'; p.closedAt = inv.date;
        logEvent(inv.date, 'Final invoice submitted — project closed', 'project', { projectId: p.id }, 'Meera Joseph');
      }
    }
  });

  /* customer payments collected in cash by engineers */
  projects.filter(p => p.poNumber).forEach(p => {
    if (rnd() < .3) {
      const d = minDate(addDays(p.startDate, randInt(10, 60)), TODAY);
      payments.push({
        id: 'C' + (++paySeq), ref: `RCV-${pad(paySeq, 4)}`, engineerId: p.engineerId, branchId: p.branchId, projectId: p.id,
        customer: leads.find(l => l.id === p.leadId).company, amount: randInt(10, 60) * 100, date: d, method: 'Cash', note: 'Site cash collection'
      });
    }
  });

  /* monthly petty cash top-ups from the main account */
  engineers.forEach(e => {
    for (let m = new Date(2024, 0, 1); m <= TODAY; m = new Date(m.getFullYear(), m.getMonth() + 1, 1)) {
      const mEnd = new Date(m.getFullYear(), m.getMonth() + 1, 0);
      const spent = sum(expenses.filter(x => x.engineerId === e.id && x.type === 'cash' && x.status !== 'rejected' && x.date >= m && x.date <= mEnd), x => x.amount);
      const received = sum(payments.filter(x => x.engineerId === e.id && x.date >= m && x.date <= mEnd), x => x.amount);
      const amt = Math.ceil(Math.max(0, spent * (1.02 + rnd() * .1) - received * .6) / 500) * 500;
      if (!amt) continue;
      const date = minDate(new Date(m.getFullYear(), m.getMonth(), randInt(1, 4)), TODAY);
      transfers.push({
        id: 'T' + (++trSeq), ref: `TRF-${m.getFullYear()}-${pad(trSeq, 4)}`,
        fromType: 'main', fromEngineerId: null, toType: 'engineer', toEngineerId: e.id,
        engineerId: e.id, branchId: e.branchId, amount: amt, date, note: 'Monthly petty cash top-up',
        status: 'approved', createdBy: 'Meera Joseph', approvedBy: 'Meera Joseph', rejectReason: ''
      });
    }
  });
  [['e2', 8500, 'Petty cash request – Business Bay site'], ['e5', 3000, 'Petty cash request – Sharjah warehouse']].forEach(r => {
    const e = engineers.find(x => x.id === r[0]);
    transfers.push({
      id: 'T' + (++trSeq), ref: `TRF-${TODAY.getFullYear()}-${pad(trSeq, 4)}`,
      fromType: 'main', fromEngineerId: null, toType: 'engineer', toEngineerId: e.id,
      engineerId: e.id, branchId: e.branchId, amount: r[1], date: addDays(TODAY, -1), note: r[2],
      status: 'pending', createdBy: e.name, approvedBy: null, rejectReason: ''
    });
  });

  /* ---------- calendar, notifications, zoho ---------- */
  const at = (day, h, m) => { const d = new Date(TODAY); d.setDate(d.getDate() + day); d.setHours(h, m, 0, 0); return d; };
  const activities = [
    { id: 'A1', when: at(0, 9, 30), title: 'Meeting with Al Futtaim', sub: 'Discussion on project scope', type: 'meeting', branchId: 'dxb', owner: 'Ali Khan' },
    { id: 'A2', when: at(0, 11, 0), title: 'Review Quotation #QT-2026-0158', sub: 'Dubai Mall Extension', type: 'review', branchId: 'dxb', owner: 'Aisha Rahman' },
    { id: 'A3', when: at(0, 14, 0), title: 'Site Visit – Business Bay', sub: 'With Project Team', type: 'visit', branchId: 'dxb', owner: 'Ramesh Nair' },
    { id: 'A4', when: at(0, 16, 0), title: 'Approve Expenses', sub: 'Pending for approval', type: 'approval', branchId: 'dxb', owner: 'Meera Joseph' },
    { id: 'A5', when: at(0, 10, 0), title: 'Client call – Gulf Infrastructure', sub: 'Quote clarification', type: 'meeting', branchId: 'auh', owner: 'Faisal Ahmed' },
    { id: 'A6', when: at(0, 15, 30), title: 'Site Visit – Sharjah Warehouse', sub: 'Progress inspection', type: 'visit', branchId: 'shj', owner: 'Sarah Thomas' },
    { id: 'A7', when: at(1, 9, 0), title: 'Abu Dhabi Tower kick-off', sub: 'PO handover with client', type: 'meeting', branchId: 'auh', owner: 'Priya Menon' },
    { id: 'A8', when: at(1, 12, 0), title: 'Review Quotation #QT-2026-0164', sub: 'Ajman Residence', type: 'review', branchId: 'shj', owner: 'Aisha Rahman' },
    { id: 'A9', when: at(1, 15, 0), title: 'Approve Transfer request', sub: 'AED 8,500 to Ramesh Nair', type: 'approval', branchId: 'dxb', owner: 'Meera Joseph' },
    { id: 'A10', when: at(2, 11, 0), title: 'Site Visit – Ras Al Khaimah Port', sub: 'Snag list walkthrough', type: 'visit', branchId: 'dxb', owner: 'Ali Khan' },
    { id: 'A11', when: at(2, 10, 0), title: 'Muscat Marina fit-out review', sub: 'Design sign-off', type: 'review', branchId: 'mct', owner: 'Omar Haddad' }
  ];

  const ago = mins => new Date(at(0, 12, 0).getTime() - mins * 60000);
  const ALL = ['admin', 'estimation', 'engineer', 'accountant'];
  const notifications = [
    { id: 'N1', type: 'lead', title: 'New lead assigned', text: 'Al Futtaim Group assigned to Ali Khan', time: ago(5), unread: true, roles: ['admin', 'engineer', 'estimation'], link: 'leads.html' },
    { id: 'N2', type: 'quote', title: 'Quotations awaiting approval', text: 'Draft quotations are pending your approval', time: ago(32), unread: true, roles: ['admin', 'estimation'], link: 'quotations.html?tab=pending' },
    { id: 'N3', type: 'expense', title: 'Expenses awaiting approval', text: 'Cash and card expenses pending review', time: ago(60), unread: true, roles: ['admin', 'accountant'], link: 'finance.html' },
    { id: 'N4', type: 'transfer', title: 'Transfer awaiting approval', text: 'AED 8,500 to Ramesh Nair', time: ago(120), unread: true, roles: ['admin', 'accountant'], link: 'finance.html?tab=transfers' },
    { id: 'N5', type: 'zoho', title: 'Zoho sync failed', text: 'Project status sync – retry required', time: ago(180), unread: true, roles: ['admin', 'accountant'], link: 'zoho.html' },
    { id: 'N6', type: 'project', title: 'Project opened', text: 'Classic Villas – Full Construction', time: ago(1440), unread: false, roles: ALL, link: 'projects.html' }
  ];

  const zohoLog = projects.slice(-9).map((p, i) => ({
    id: 'Z' + (++zSeq), entity: 'Project', ref: p.projectNumber, action: i % 3 ? 'Status sync' : 'Project create',
    time: new Date(at(0, 12, 0).getTime() - (9 - i) * 47 * 60000),
    status: i === 5 ? 'failed' : i === 8 ? 'queued' : 'synced',
    message: i === 5 ? 'Zoho Books API timeout (504)' : i === 8 ? 'Waiting in queue' : 'OK',
    target: { kind: 'project', id: p.id }
  })).concat(engineers.slice(0, 3).map(e => ({
    id: 'Z' + (++zSeq), entity: 'Petty Cash', ref: e.pettyCash, action: 'Balance sync', time: ago(20), status: 'synced', message: 'OK', target: null
  })));

  /* =======================================================
     Read helpers
     ======================================================= */
  const byId = (arr, id) => arr.find(x => x.id === id);
  const engName = id => (byId(engineers, id) || { name: '—' }).name;

  function cashLedger(engineerId, upTo) {
    const e = byId(engineers, engineerId);
    if (!e) return [];
    const rows = [{ date: new Date(2024, 0, 1), ref: 'OPENING', kind: 'Opening cash', desc: 'Opening petty cash balance', amount: e.openingCash }];
    transfers.filter(t => t.status === 'approved' && t.toEngineerId === engineerId).forEach(t => rows.push({ date: t.date, ref: t.ref, kind: 'Transfer in', desc: t.note || 'Transfer received', amount: t.amount }));
    transfers.filter(t => t.status === 'approved' && t.fromEngineerId === engineerId).forEach(t => rows.push({ date: t.date, ref: t.ref, kind: 'Transfer out', desc: t.note || 'Transfer sent', amount: -t.amount }));
    payments.filter(p => p.engineerId === engineerId).forEach(p => rows.push({ date: p.date, ref: p.ref, kind: 'Customer payment', desc: `${p.customer}${p.note ? ' · ' + p.note : ''}`, amount: p.amount }));
    expenses.filter(x => x.engineerId === engineerId && x.type === 'cash' && x.status !== 'rejected').forEach(x => rows.push({ date: x.date, ref: x.ref, kind: 'Cash expense', desc: `${x.vendor} · ${x.category}`, amount: -x.amount }));
    rows.sort((a, b) => a.date - b.date);
    let bal = 0;
    rows.forEach(r => { bal += r.amount; r.balance = bal; });
    return upTo ? rows.filter(r => r.date <= upTo) : rows;
  }
  const cashBalance = (engineerId, upTo) => { const l = cashLedger(engineerId, upTo); return l.length ? l[l.length - 1].balance : 0; };

  /* =======================================================
     Mutations (each one is journaled and replayed on load)
     ======================================================= */
  const listeners = new Set();
  const emit = () => listeners.forEach(fn => fn());
  const fail = msg => { throw new Error(msg); };
  const aed = n => 'AED ' + (+n).toLocaleString('en-US', { maximumFractionDigits: 2 });

  function notify(n) {
    notifications.unshift(Object.assign({ id: 'N' + (++nSeq), time: now(), unread: true, roles: ALL }, n));
  }
  /* mock Zoho Books queue — no external calls are made */
  function zohoQueue(entity, ref, action, target) {
    zohoLog.push({ id: 'Z' + (++zSeq), entity, ref, action, time: now(), status: 'syncing', message: 'Syncing with Zoho Books…', target: target || null });
  }
  const leadOf = q => byId(leads, q.leadId);

  /* the PO number is optional — a recorded PO date means the PO has been received */
  const hasPO = p => !!(p.poNumber || p.poDate);
  const poLabel = p => p.poNumber ? `PO ${p.poNumber}` : 'PO';
  function applyPO(p, d, t) {
    p.poNumber = d.poNumber || null; p.poDate = d.poDate ? new Date(d.poDate) : t; p.poValue = +d.poValue || p.value;
    if (d.poDocument) p.documents.push({ name: d.poDocument, kind: 'Purchase Order', at: t });
    /* opened earlier on an email approval — the PO only unlocks invoicing, the status stays as it is */
    if (p.status !== 'popending') {
      logEvent(t, `${poLabel(p)} received — invoicing unlocked`, 'project', { leadId: p.leadId, projectId: p.id });
      zohoQueue('Project', p.projectNumber, 'PO update', { kind: 'project', id: p.id });
      return;
    }
    p.startDate = d.startDate ? new Date(d.startDate) : p.poDate;
    p.status = 'open';
    logEvent(t, `${poLabel(p)} received — project opened`, 'project', { leadId: p.leadId, projectId: p.id });
    zohoQueue('Project', p.projectNumber, 'Status sync: Open', { kind: 'project', id: p.id });
  }
  /* work granted by client email before the PO: the project opens, invoices wait for the PO */
  function applyEmailApproval(p, a, t) {
    p.emailApproval = { date: a.date ? new Date(a.date) : t, by: a.by || '', document: a.document || null };
    p.startDate = p.emailApproval.date;
    if (a.document) p.documents.push({ name: a.document, kind: 'Email approval', at: t });
    p.status = 'open';
    logEvent(t, `Work approved by email${p.emailApproval.by ? ` from ${p.emailApproval.by}` : ''} — project opened, PO awaited`, 'project', { leadId: p.leadId, projectId: p.id });
    zohoQueue('Project', p.projectNumber, 'Status sync: Open', { kind: 'project', id: p.id });
  }

  const API = {
    TODAY, countries, branches, departments, engineers, users, sources, vendors, categories, vehicleCategories, isVehicleCategory, vehicles, paymentMethods, companyCards,
    stages, quoteStatuses, projectStatuses, settings, CATALOG, DEFAULT_TERMS, TECH,
    leads, quotes, projects, expenses, invoices, transfers, payments, events, activities, notifications, zohoLog,

    now, quoteTotals, cashLedger, cashBalance, hasPO,
    branch: id => byId(branches, id),
    engineer: id => byId(engineers, id),
    department: id => byId(departments, id),
    lead: id => byId(leads, id),
    quote: id => quoteById.get(id) || null,
    project: id => byId(projects, id),
    expense: id => byId(expenses, id),
    transfer: id => byId(transfers, id),
    invoice: id => byId(invoices, id),
    quotesForLead: id => quotes.filter(q => q.leadId === id),
    eventsFor: (key, id) => events.filter(e => e[key] === id).sort((a, b) => b.at - a.at),
    nextLeadNumber: () => `LD-${TODAY.getFullYear()}-${pad(leadSeq + 1, 4)}`,

    leadStatus(l) {
      if (l.stage === 'awarded') return 'Won';
      if (l.stage === 'sent') return 'Negotiation';
      if (l.stage === 'draft') return 'Proposal';
      if (l.lost) return 'Lost';
      return (TODAY - l.createdAt) / DAY < 4 ? 'New' : 'Contacted';
    },
    canApproveQuote: role => AUTH.permissionsFor(role).has('quotes.approve'),

    setActor(user) { actor = user; },
    onChange(fn) { listeners.add(fn); return () => listeners.delete(fn); },

    /* ---------- leads ---------- */
    createLead(d) {
      const t = now();
      const l = Object.assign({
        id: 'L' + (++leadSeq), leadNumber: `LD-${TODAY.getFullYear()}-${pad(leadSeq, 4)}`,
        createdAt: t, stage: 'new', stageDate: t, quoteId: null, awardDate: null, projectId: null, lost: false, attachments: []
      }, d);
      l.leadDate = d.leadDate ? new Date(d.leadDate) : t;
      attachQuoteGetter(l);
      leads.push(l);
      logEvent(t, 'Lead created', 'lead', { leadId: l.id });
      logEvent(t, `Assigned to ${engName(l.engineerId)}`, 'lead', { leadId: l.id });
      notify({ type: 'lead', title: 'New lead created', text: `${l.leadNumber} · ${l.name} assigned to ${engName(l.engineerId)}`, roles: ['admin', 'estimation', 'engineer'], link: `lead.html?id=${l.id}` });
      emit();
      return l;
    },
    updateLead(id, d) {
      const l = byId(leads, id);
      const reassigned = d.engineerId && d.engineerId !== l.engineerId;
      Object.assign(l, d);
      if (d.leadDate) l.leadDate = new Date(d.leadDate);
      logEvent(now(), reassigned ? `Lead updated and assigned to ${engName(l.engineerId)}` : 'Lead details updated', 'lead', { leadId: id });
      emit();
    },
    deleteLead(id) {
      const l = byId(leads, id);
      if (!l) return;
      if (l.quoteId) fail('Leads with quotations cannot be deleted');
      leads.splice(leads.indexOf(l), 1);
      notify({ type: 'lead', title: 'Lead deleted', text: `${l.leadNumber} · ${l.name}`, roles: ['admin', 'estimation'] });
      emit();
    },
    assignEngineer(id, engineerId) {
      const l = byId(leads, id);
      l.engineerId = engineerId;
      if (l.quote && ['draft', 'rejected'].includes(l.quote.status)) l.quote.engineerId = engineerId;
      logEvent(now(), `Assigned to ${engName(engineerId)}`, 'lead', { leadId: id });
      notify({ type: 'lead', title: 'Lead assigned', text: `${l.leadNumber} · ${l.name} → ${engName(engineerId)}`, roles: ['engineer', 'admin', 'estimation'], link: `lead.html?id=${id}` });
      emit();
    },

    /* ---------- quotations ---------- */
    createQuote(leadId, data) {
      const l = byId(leads, leadId);
      if (l.quote && !['awarded'].includes(l.quote.status) && l.quote.status !== 'sent' && !(data && data.force)) fail('This lead already has an open quotation');
      const t = now();
      const q = newQuote(l, t, data);
      syncStage(l, t);
      logEvent(t, `Quotation ${q.number} created as draft`, 'quote', { leadId, quoteId: q.id });
      notify({ type: 'quote', title: 'Quotation saved as draft', text: `${q.number} · ${l.name}`, roles: ['admin', 'estimation', 'engineer'], link: `quote.html?id=${q.id}` });
      emit();
      return q;
    },
    updateQuote(id, data) {
      const q = quoteById.get(id);
      if (!['draft', 'rejected'].includes(q.status)) fail('Only draft quotations can be edited');
      Object.assign(q, data);
      if (q.status === 'rejected') q.status = 'draft';
      q.updatedAt = now();
      recalc(q);
      syncStage(leadOf(q));
      emit();
      return q;
    },
    submitQuote(id) {
      const q = quoteById.get(id);
      recalc(q);
      if (!q.items.length || q.items.some(i => !String(i.desc).trim() || !(+i.qty > 0) || !(+i.price > 0)) || !(q.total > 0)) fail('Complete all line items before submitting');
      q.status = 'pending'; q.submittedAt = now(); q.rejectReason = '';
      logEvent(q.submittedAt, `${q.number} submitted for approval`, 'quote', { leadId: q.leadId, quoteId: id });
      const approver = settings.quoteApprover === 'admin' ? ['admin'] : ['admin', 'estimation'];
      notify({ type: 'quote', title: 'Quotation awaiting your approval', text: `${q.number} · ${aed(q.total)}`, roles: approver, link: `quote.html?id=${id}` });
      emit();
    },
    approveQuote(id) {
      const q = quoteById.get(id);
      if (q.status !== 'pending') fail('Only quotations pending approval can be approved');
      q.status = 'approved'; q.approvedAt = now(); q.approvedBy = actor.name; q.editReason = null;
      logEvent(q.approvedAt, `${q.number} approved`, 'quote', { leadId: q.leadId, quoteId: id });
      notify({ type: 'quote', title: 'Quotation approved', text: `${q.number} is ready to send`, roles: ['engineer', 'estimation', 'admin'], link: `quote.html?id=${id}` });
      emit();
    },
    rejectQuote(id, reason) {
      const q = quoteById.get(id);
      q.status = 'rejected'; q.rejectReason = reason;
      logEvent(now(), `${q.number} returned for revision: ${reason}`, 'quote', { leadId: q.leadId, quoteId: id });
      notify({ type: 'quote', title: 'Quotation needs revision', text: `${q.number}: ${reason}`, roles: ['engineer', 'estimation', 'admin'], link: `quote.html?id=${id}` });
      emit();
    },
    /* an approved quotation goes back to draft for changes; it must be approved again before sending */
    /* data: { reason, files: [names], engineerId } — older journal entries pass the reason as a string */
    reopenQuote(id, data) {
      const d = typeof data === 'string' ? { reason: data } : (data || {});
      const q = quoteById.get(id), l = leadOf(q);
      if (!['approved', 'sent'].includes(q.status)) fail('Only approved or sent quotations can be reopened for editing');
      const wasSent = q.status === 'sent';
      if (!String(d.reason || '').trim()) fail('Enter the reason for editing');
      const eng = d.engineerId && byId(engineers, d.engineerId);
      if (d.engineerId && (!eng || eng.branchId !== l.branchId)) fail('Assign the revision to an engineer from the same branch');
      const files = (d.files || []).slice();
      q.status = 'draft'; q.approvedAt = null; q.approvedBy = null; q.updatedAt = now(); q.editReason = d.reason;
      q.revision = { by: actor.name, at: q.updatedAt, files, afterSend: wasSent };
      /* a revision after sending gets a new number (QT-2026-0805_R1, _R2 …) and today's date */
      if (wasSent) {
        const old = q.number;
        q.baseNumber = q.baseNumber || q.number;
        q.revNo = (q.revNo || 0) + 1;
        q.number = `${q.baseNumber}_R${q.revNo}`;
        q.date = q.updatedAt;
        q.sentDate = null;
        q.revision.from = old;
        logEvent(q.updatedAt, `${old} revised — new number ${q.number}`, 'quote', { leadId: q.leadId, quoteId: id });
      }
      if (files.length) q.attachments = (q.attachments || []).concat(files);
      const reassigned = eng && eng.id !== l.engineerId;
      if (reassigned) { l.engineerId = eng.id; q.engineerId = eng.id; }
      q.revision.assignee = d.self ? actor.name : engName(l.engineerId);
      syncStage(l);
      logEvent(q.updatedAt, `${q.number} moved to draft for editing${d.self ? ` by ${actor.name} (editing directly)` : ''}: ${d.reason}${files.length ? ` · revised scope: ${files.join(', ')}` : ''}`, 'quote', { leadId: q.leadId, quoteId: id });
      if (reassigned) logEvent(q.updatedAt, `Revision assigned to ${eng.name}`, 'quote', { leadId: q.leadId, quoteId: id });
      notify({ type: 'quote', title: 'Quotation revision assigned', text: `${q.number} · ${q.revision.assignee}: ${d.reason}`, roles: ['engineer', 'estimation', 'admin'], link: `quote.html?id=${id}` });
      emit();
    },
    sendQuote(id, share) {
      const q = quoteById.get(id);
      if (q.status !== 'approved') fail('Only approved quotations can be sent');
      q.status = 'sent'; q.sentDate = now();
      if (share) { q.shareMode = share.mode; q.shareTo = share.to; }
      const l = leadOf(q);
      l.quoteId = q.id;
      syncStage(l, q.sentDate);
      logEvent(q.sentDate, `${q.number} sent to ${l.company}${share && share.mode ? ` via ${share.mode}${share.to ? ` (${share.to})` : ''}` : ''}`, 'quote', { leadId: q.leadId, quoteId: id });
      notify({ type: 'quote', title: 'Quotation sent successfully', text: `${q.number} sent to ${l.company}`, roles: ['admin', 'estimation', 'engineer'], link: `quote.html?id=${id}` });
      emit();
    },
    awardQuote(id) {
      const q = quoteById.get(id);
      if (q.status !== 'sent') fail('Only sent quotations can be awarded');
      q.status = 'awarded'; q.awardDate = now();
      const l = leadOf(q);
      l.quoteId = q.id;
      syncStage(l, q.awardDate);
      logEvent(q.awardDate, `${q.number} awarded`, 'quote', { leadId: q.leadId, quoteId: id });
      notify({ type: 'quote', title: 'Quotation awarded', text: `${q.number} · ${l.name} — create the project and collect the PO`, roles: ALL, link: `quote.html?id=${id}` });
      emit();
    },
    cloneQuote(id, targetLeadId) {
      const src = quoteById.get(id);
      const l = byId(leads, targetLeadId || src.leadId);
      const t = now();
      const q = newQuote(l, t, {
        items: src.items.map(i => Object.assign({}, i)), discountType: src.discountType, discount: src.discount, taxRate: src.taxRate,
        techDesc: src.techDesc, terms: src.terms, notes: src.notes, output: src.output, validity: src.validity, clonedFrom: src.number
      });
      if (l.id === src.leadId && ['sent', 'awarded'].includes(src.status)) l.quoteId = src.id; // keep the live quote on the lead
      syncStage(l, t);
      logEvent(t, `${q.number} cloned from ${src.number}`, 'quote', { leadId: l.id, quoteId: q.id });
      notify({ type: 'quote', title: 'Quotation cloned', text: `${q.number} created from ${src.number}`, roles: ['admin', 'estimation', 'engineer'], link: `quote.html?id=${q.id}` });
      emit();
      return q;
    },

    /* ---------- projects ---------- */
    createProject(leadId, d) {
      const l = byId(leads, leadId);
      if (!l.quote || l.quote.status !== 'awarded') fail('The quotation must be awarded first');
      if (l.projectId) fail('A project already exists for this lead');
      const t = now();
      const p = makeProject(l, t, { name: d.name || `${l.name} – ${l.subject}`, description: d.description || '', startDate: d.startDate ? new Date(d.startDate) : t });
      p.rfqNumber = d.rfqNumber || l.rfqNumber || '';
      (d.documents || []).forEach(n => p.documents.push({ name: n, kind: 'Project document', at: t }));
      logEvent(t, `Project ${p.projectNumber} created`, 'project', { leadId, projectId: p.id });
      zohoQueue('Project', p.projectNumber, 'Project create', { kind: 'project', id: p.id });
      if (d.poNumber || d.poDate) applyPO(p, d, t);
      else if (d.emailApproval) applyEmailApproval(p, d.emailApproval, t);
      notify({ type: 'project', title: 'Project created', text: `${p.projectNumber} · ${p.name}${hasPO(p) ? '' : p.emailApproval ? ' — approved by email, PO awaited' : ' — PO pending'}`, roles: ALL, link: `project.html?id=${p.id}` });
      emit();
      return p;
    },
    addPO(projectId, d) {
      const p = byId(projects, projectId);
      if (hasPO(p)) fail('PO already recorded');
      const pending = p.status === 'popending';
      applyPO(p, d, now());
      notify(pending
        ? { type: 'project', title: 'Project opened', text: `${p.projectNumber} · ${poLabel(p)}`, roles: ALL, link: `project.html?id=${p.id}` }
        : { type: 'project', title: 'PO received', text: `${p.projectNumber} · ${poLabel(p)} — invoicing unlocked`, roles: ['accountant', 'admin'], link: `project.html?id=${p.id}` });
      emit();
    },
    setProjectStatus(id, status, note) {
      const p = byId(projects, id);
      const t = now();
      const label = projectStatuses.find(s => s.id === status).name;
      p.status = status;
      logEvent(t, `Status changed to ${label}${note ? ' — ' + note : ''}`, 'project', { leadId: p.leadId, projectId: id });
      zohoQueue('Project', p.projectNumber, `Status sync: ${label}`, { kind: 'project', id });
      emit();
    },
    completeWork(id, d) {
      const p = byId(projects, id);
      if (p.status !== 'inprogress') fail('Only projects in progress can be completed');
      const t = now();
      p.status = 'completed'; p.completedAt = d.date ? new Date(d.date) : t; p.endDate = p.completedAt; p.completionNotes = d.notes || '';
      (d.documents || []).forEach(n => p.documents.push({ name: n, kind: 'Completion document', at: t }));
      logEvent(t, 'Work completion report submitted', 'project', { leadId: p.leadId, projectId: id });
      zohoQueue('Project', p.projectNumber, 'Status sync: Work Completed', { kind: 'project', id });
      notify({ type: 'project', title: 'Invoice required', text: `${p.projectNumber} work completed — raise the final invoice`, roles: ['accountant', 'admin'], link: `project.html?id=${id}` });
      emit();
    },

    /* ---------- expenses ---------- */
    addVehicle(number) {
      const n = String(number || '').trim().toUpperCase().replace(/\s+/g, ' ');
      if (!n) fail('Enter the vehicle number');
      if (vehicles.some(v => v.replace(/\s/g, '') === n.replace(/\s/g, ''))) fail(`Vehicle ${n} already exists`);
      vehicles.push(n);
      emit();
      return n;
    },
    addExpense(d) {
      const vehicle = isVehicleCategory(d.category);
      let owner;
      if (vehicle) {
        if (d.projectId) fail('Vehicle expenses cannot be added to a project');
        if (!vehicles.includes(d.vehicle)) fail('Choose the vehicle');
        owner = byId(engineers, d.engineerId);
        if (!owner) fail('Choose the engineer');
        owner = { branchId: owner.branchId, engineerId: owner.id, department: null };
      } else {
        const p = byId(projects, d.projectId);
        if (!p) fail('Choose the project');
        if (!['open', 'inprogress'].includes(p.status)) fail('Expenses can only be recorded against open projects');
        owner = { branchId: p.branchId, engineerId: p.engineerId, department: p.department, leadId: p.leadId };
      }
      const t = now();
      const e = Object.assign({
        id: 'X' + (++expSeq), ref: `EXP-${pad(expSeq, 5)}`,
        branchId: owner.branchId, engineerId: owner.engineerId, department: owner.department,
        status: 'pending', zoho: 'not-sent', rejectReason: '', createdAt: t
      }, d, vehicle ? { projectId: null } : { vehicle: null });
      e.date = new Date(d.date || t);
      expenses.push(e);
      logEvent(t, `Expense ${e.ref} submitted — ${aed(e.amount)} (${e.vendor})${vehicle ? ` · vehicle ${e.vehicle}` : ''}`, 'expense', { leadId: owner.leadId, projectId: e.projectId, expenseId: e.id });
      notify({ type: 'expense', title: 'Expense submitted', text: `${e.ref} · ${aed(e.amount)} awaiting approval`, roles: ['accountant', 'admin'], link: 'finance.html' });
      emit();
      return e;
    },
    /* accountant corrections before approval — the project / vehicle grouping can't change */
    updateExpense(id, d) {
      const e = byId(expenses, id);
      if (e.status !== 'pending') fail('Only pending expenses can be edited');
      if (d.category && isVehicleCategory(d.category) !== isVehicleCategory(e.category)) fail('Vehicle and project expense types cannot be swapped — reject and re-submit instead');
      if (isVehicleCategory(e.category) && d.vehicle && !vehicles.includes(d.vehicle)) fail('Choose the vehicle');
      if (d.type === 'card' && !companyCards.includes(d.card)) fail('Choose the card');
      if (d.amount != null && !(+d.amount > 0)) fail('Enter an amount greater than 0');
      ['category', 'vehicle', 'type', 'vendor', 'refNo', 'description'].forEach(k => { if (d[k] != null) e[k] = d[k]; });
      if (d.amount != null) e.amount = Math.round(+d.amount * 100) / 100;
      if (d.date) e.date = new Date(d.date);
      if (d.type) e.card = d.type === 'card' ? d.card : null;
      logEvent(now(), `Expense ${e.ref} edited by ${actor.name} — ${aed(e.amount)} (${e.vendor})`, 'expense', { projectId: e.projectId, expenseId: id });
      emit();
    },
    /* d.vendor: the Zoho Books vendor the accountant matched the entered name to (a new name is created as a vendor) */
    approveExpense(id, d) {
      const e = byId(expenses, id);
      if (e.status !== 'pending') fail('Only pending expenses can be approved');
      const vendor = String((d && d.vendor) || '').trim();
      if (vendor) {
        if (!vendors.some(v => v.toLowerCase() === vendor.toLowerCase())) vendors.push(vendor);
        const matched = vendors.find(v => v.toLowerCase() === vendor.toLowerCase());
        if (matched !== e.vendor) { e.enteredVendor = e.vendor; e.vendor = matched; }
      }
      e.status = 'approved'; e.zoho = 'syncing'; e.approvedBy = actor.name; e.approvedAt = now();
      logEvent(e.approvedAt, `Expense ${e.ref} approved — posting to Zoho Books${e.enteredVendor ? ` (vendor "${e.enteredVendor}" matched to ${e.vendor})` : ''}`, 'expense', { projectId: e.projectId, expenseId: id });
      zohoQueue('Expense', e.ref, 'Post cleared transaction', { kind: 'expense', id });
      notify({ type: 'expense', title: 'Expense approved', text: `${e.ref} · ${aed(e.amount)}`, roles: ['engineer', 'admin'], link: e.projectId ? `project.html?id=${e.projectId}` : 'expenses.html' });
      emit();
    },
    rejectExpense(id, reason) {
      const e = byId(expenses, id);
      e.status = 'rejected'; e.rejectReason = reason;
      logEvent(now(), `Expense ${e.ref} rejected: ${reason}`, 'expense', { projectId: e.projectId, expenseId: id });
      notify({ type: 'expense', title: 'Expense rejected', text: `${e.ref}: ${reason}`, roles: ['engineer', 'admin'], link: e.projectId ? `project.html?id=${e.projectId}` : 'expenses.html' });
      emit();
    },

    /* ---------- cash: transfers & customer payments ---------- */
    createTransfer(d) {
      const t = now();
      const toEng = d.toType === 'engineer' ? byId(engineers, d.toEngineerId) : null;
      const fromEng = d.fromType === 'engineer' ? byId(engineers, d.fromEngineerId) : null;
      const owner = fromEng || toEng;
      if (!owner) fail('Choose an engineer');
      if (fromEng && toEng && fromEng.id === toEng.id) fail('Choose a different recipient');
      if (fromEng && +d.amount > cashBalance(fromEng.id)) fail(`Amount exceeds ${fromEng.name}'s cash in hand`);
      const tr = {
        id: 'T' + (++trSeq), ref: `TRF-${TODAY.getFullYear()}-${pad(trSeq, 4)}`,
        fromType: d.fromType, fromEngineerId: fromEng ? fromEng.id : null, toType: d.toType, toEngineerId: toEng ? toEng.id : null,
        engineerId: owner.id, branchId: owner.branchId, amount: +d.amount, date: new Date(d.date || t), note: d.note || '',
        status: 'pending', createdBy: actor.name, approvedBy: null, rejectReason: ''
      };
      transfers.push(tr);
      logEvent(t, `Transfer ${tr.ref} submitted — ${aed(tr.amount)}`, 'transfer', { transferId: tr.id });
      notify({ type: 'transfer', title: 'Transfer awaiting approval', text: `${tr.ref} · ${aed(tr.amount)}`, roles: ['accountant', 'admin'], link: 'finance.html?tab=transfers' });
      emit();
      return tr;
    },
    updateTransfer(id, d) {
      const tr = byId(transfers, id);
      if (tr.status !== 'pending') fail('Only pending transfers can be edited');
      /* older journal entries only carry amount + note — keep the existing route and date then */
      if (d.fromType) {
        const toEng = d.toType === 'engineer' ? byId(engineers, d.toEngineerId) : null;
        const fromEng = d.fromType === 'engineer' ? byId(engineers, d.fromEngineerId) : null;
        const owner = fromEng || toEng;
        if (!owner) fail('Choose an engineer');
        if (fromEng && toEng && fromEng.id === toEng.id) fail('Choose a different recipient');
        if (fromEng && +d.amount > cashBalance(fromEng.id)) fail(`Amount exceeds ${fromEng.name}'s cash in hand`);
        Object.assign(tr, { fromType: d.fromType, fromEngineerId: fromEng ? fromEng.id : null, toType: d.toType, toEngineerId: toEng ? toEng.id : null, engineerId: owner.id, branchId: owner.branchId });
      }
      if (!(+d.amount > 0)) fail('Enter an amount greater than 0');
      tr.amount = +d.amount; tr.note = d.note;
      if (d.date) tr.date = new Date(d.date);
      logEvent(now(), `Transfer ${tr.ref} edited by ${actor.name}`, 'transfer', { transferId: id });
      emit();
    },
    approveTransfer(id) {
      const tr = byId(transfers, id);
      if (tr.fromEngineerId && tr.amount > cashBalance(tr.fromEngineerId)) fail('Insufficient cash in hand for this transfer');
      tr.status = 'approved'; tr.approvedBy = actor.name; tr.approvedAt = now();
      logEvent(tr.approvedAt, `Transfer ${tr.ref} approved — cash in hand updated`, 'transfer', { transferId: id });
      zohoQueue('Petty Cash', tr.ref, 'Transfer posted', { kind: 'transfer', id });
      notify({ type: 'transfer', title: 'Transfer approved', text: `${tr.ref} · ${aed(tr.amount)} — cash in hand updated`, roles: ['engineer', 'admin'], link: 'expenses.html?tab=cashinhand' });
      emit();
    },
    rejectTransfer(id, reason) {
      const tr = byId(transfers, id);
      tr.status = 'rejected'; tr.rejectReason = reason;
      logEvent(now(), `Transfer ${tr.ref} rejected: ${reason}`, 'transfer', { transferId: id });
      notify({ type: 'transfer', title: 'Transfer rejected', text: `${tr.ref}: ${reason}`, roles: ['engineer', 'admin'], link: 'expenses.html?tab=transfers' });
      emit();
    },
    recordPayment(d) {
      const t = now();
      const p = byId(projects, d.projectId);
      const lead = byId(leads, p.leadId);
      const pay = {
        id: 'C' + (++paySeq), ref: `RCV-${pad(paySeq, 4)}`, engineerId: d.engineerId || p.engineerId, branchId: p.branchId, projectId: p.id,
        customer: lead.company, amount: +d.amount, date: new Date(d.date || t), method: 'Cash', note: d.note || ''
      };
      payments.push(pay);
      logEvent(t, `Customer payment ${pay.ref} received in cash — ${aed(pay.amount)}`, 'payment', { leadId: p.leadId, projectId: p.id });
      notify({ type: 'transfer', title: 'Customer payment received by engineer', text: `${pay.ref} · ${engName(pay.engineerId)} · ${aed(pay.amount)}`, roles: ['accountant', 'admin'], link: 'expenses.html?tab=cashinhand' });
      emit();
      return pay;
    },

    /* ---------- invoices ---------- */
    createInvoice(d) {
      const p = byId(projects, d.projectId);
      if (!hasPO(p)) fail('Record the client PO before raising an invoice');
      if (d.final && p.status !== 'completed') fail('Final invoices need the work to be completed first');
      const t = now();
      const amount = Math.round(+d.amount);
      const taxRate = d.taxRate == null ? .05 : +d.taxRate;
      const inv = {
        id: 'I' + (++invSeq), number: `INV-${TODAY.getFullYear()}-${pad(invSeq, 4)}`,
        projectId: p.id, quoteNumber: p.quoteNumber, branchId: p.branchId, engineerId: p.engineerId, department: p.department,
        date: new Date(d.date || t), amount, taxRate, tax: round2(amount * taxRate), total: round2(amount * (1 + taxRate)),
        final: !!d.final, status: d.final ? 'draft' : 'submitted', notes: d.notes || ''
      };
      invoices.push(inv);
      if (inv.final) {
        p.status = 'invoicepending';
        logEvent(t, `Final invoice ${inv.number} created — invoice pending`, 'invoice', { leadId: p.leadId, projectId: p.id });
      } else {
        logEvent(t, `Progress invoice ${inv.number} submitted`, 'invoice', { leadId: p.leadId, projectId: p.id });
        zohoQueue('Invoice', inv.number, 'Invoice create', { kind: 'invoice', id: inv.id });
      }
      notify({ type: 'invoice', title: 'Invoice created', text: `${inv.number} · ${aed(inv.total)}`, roles: ['accountant', 'admin'], link: `project.html?id=${p.id}` });
      emit();
      return inv;
    },
    submitInvoice(id) {
      const inv = byId(invoices, id);
      const p = byId(projects, inv.projectId);
      const t = now();
      inv.status = 'submitted'; inv.submittedAt = t;
      zohoQueue('Invoice', inv.number, 'Invoice create', { kind: 'invoice', id });
      logEvent(t, `Invoice ${inv.number} submitted`, 'invoice', { leadId: p.leadId, projectId: p.id });
      if (inv.final) {
        p.status = 'closed'; p.closedAt = t;
        logEvent(t, 'Project closed', 'project', { leadId: p.leadId, projectId: p.id });
        zohoQueue('Project', p.projectNumber, 'Status sync: Closed', { kind: 'project', id: p.id });
        notify({ type: 'project', title: 'Project closed', text: `${p.projectNumber} closed after final invoice ${inv.number}`, roles: ALL, link: `project.html?id=${p.id}` });
      }
      emit();
    },

    /* ---------- zoho (mock integration layer) ---------- */
    processZoho() {
      let changed = false;
      zohoLog.forEach(z => {
        if (z.status !== 'syncing') return;
        z.status = 'synced'; z.message = 'OK'; z.time = now(); changed = true;
        if (z.target && z.target.kind === 'expense') { const e = byId(expenses, z.target.id); if (e) e.zoho = 'synced'; }
      });
      if (changed) emit();
    },
    retryZoho(id) {
      const z = byId(zohoLog, id);
      z.status = 'syncing'; z.message = 'Retrying…'; z.time = now();
      emit();
    },
    syncZohoQueue() {
      zohoLog.forEach(z => { if (z.status === 'queued') { z.status = 'syncing'; z.message = 'Syncing…'; z.time = now(); } });
      emit();
    },

    /* ---------- misc ---------- */
    markRead(id) { const n = byId(notifications, id); if (n) n.unread = false; emit(); },
    markAllRead(role) { notifications.forEach(n => { if (!role || role === 'admin' || n.roles.includes(role)) n.unread = false; }); emit(); },
    completeActivity(id) { const a = byId(activities, id); if (a) a.done = true; emit(); },
    setSetting(key, value) { settings[key] = value; emit(); }
  };

  /* =======================================================
     Persistence: the seed is deterministic, so only user actions are
     stored (journal in localStorage) and replayed on every page load
     with the original user and time.
     ======================================================= */
  const JOURNAL_KEY = 'rm.journal.v2';
  const MUTATIONS = ['createLead', 'updateLead', 'deleteLead', 'assignEngineer', 'createQuote', 'updateQuote', 'submitQuote', 'approveQuote',
    'rejectQuote', 'reopenQuote', 'sendQuote', 'awardQuote', 'cloneQuote', 'createProject', 'addPO', 'setProjectStatus', 'completeWork', 'addVehicle', 'addExpense',
    'updateExpense', 'approveExpense', 'rejectExpense', 'createTransfer', 'updateTransfer', 'approveTransfer', 'rejectTransfer', 'recordPayment',
    'createInvoice', 'submitInvoice', 'processZoho', 'retryZoho', 'syncZohoQueue', 'markRead', 'markAllRead', 'completeActivity', 'setSetting'];
  const ISO = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/;
  let journal = [];
  try {
    AUTH.storage.del('rm.journal');
    journal = JSON.parse(AUTH.storage.get(JOURNAL_KEY) || '[]', (k, v) => (typeof v === 'string' && ISO.test(v) ? new Date(v) : v));
  } catch (e) { journal = []; }
  const save = () => AUTH.storage.set(JOURNAL_KEY, JSON.stringify(journal));

  /* which permission each write needs, and which record it touches */
  const REQUIRES = {
    createLead: 'leads.create', updateLead: 'leads.edit', deleteLead: 'leads.delete', assignEngineer: 'leads.assign',
    createQuote: 'quotes.create', updateQuote: 'quotes.edit', submitQuote: 'quotes.submit', approveQuote: 'quotes.approve',
    rejectQuote: 'quotes.approve', reopenQuote: 'quotes.edit', sendQuote: 'quotes.send', awardQuote: 'quotes.award', cloneQuote: 'quotes.create',
    createProject: 'projects.create', addPO: 'projects.open', setProjectStatus: 'projects.edit', completeWork: 'projects.edit',
    addVehicle: 'expenses.create', addExpense: 'expenses.create', updateExpense: 'expenses.approve', approveExpense: 'expenses.approve', rejectExpense: 'expenses.approve',
    createTransfer: 'transfers.create', updateTransfer: 'transfers.edit', approveTransfer: 'transfers.approve', rejectTransfer: 'transfers.approve',
    recordPayment: 'payments.create', createInvoice: 'invoices.create', submitInvoice: 'invoices.submit',
    retryZoho: 'zoho.sync', syncZohoQueue: 'zoho.sync', setSetting: 'settings.edit'
  };
  const engRecord = id => { const e = byId(engineers, id); return e && { branchId: e.branchId, engineerId: e.id }; };
  const TOUCHES = {
    createLead: a => a[0], updateLead: a => byId(leads, a[0]), deleteLead: a => byId(leads, a[0]), assignEngineer: a => byId(leads, a[0]),
    createQuote: a => byId(leads, a[0]), updateQuote: a => quoteById.get(a[0]), submitQuote: a => quoteById.get(a[0]), approveQuote: a => quoteById.get(a[0]),
    rejectQuote: a => quoteById.get(a[0]), reopenQuote: a => quoteById.get(a[0]), sendQuote: a => quoteById.get(a[0]), awardQuote: a => quoteById.get(a[0]),
    cloneQuote: a => (a[1] ? byId(leads, a[1]) : quoteById.get(a[0])),
    createProject: a => byId(leads, a[0]), addPO: a => byId(projects, a[0]), setProjectStatus: a => byId(projects, a[0]), completeWork: a => byId(projects, a[0]),
    addExpense: a => (a[0].projectId ? byId(projects, a[0].projectId) : engRecord(a[0].engineerId)), updateExpense: a => byId(expenses, a[0]), approveExpense: a => byId(expenses, a[0]), rejectExpense: a => byId(expenses, a[0]),
    createTransfer: a => engRecord(a[0].fromType === 'engineer' ? a[0].fromEngineerId : a[0].toEngineerId),
    updateTransfer: a => byId(transfers, a[0]), approveTransfer: a => byId(transfers, a[0]), rejectTransfer: a => byId(transfers, a[0]),
    recordPayment: a => byId(projects, a[0].projectId), createInvoice: a => byId(projects, a[0].projectId), submitInvoice: a => byId(invoices, a[0])
  };
  function authorize(name, args) {
    const perm = REQUIRES[name];
    if (perm && !AUTH.can(perm, actor)) fail(`${actor.roleLabel || actor.role} does not have permission: ${AUTH.PERMISSIONS[perm] || perm}`);
    const rec = TOUCHES[name] && TOUCHES[name](args);
    if (TOUCHES[name] && !rec) fail('Record not found');
    if (rec && !AUTH.inScope(rec, actor)) fail('This record is outside your branch / assignment access');
  }

  let replaying = false;
  MUTATIONS.forEach(name => {
    const fn = API[name];
    API[name] = function () {
      const args = Array.prototype.slice.call(arguments);
      const stamp = now();
      authorize(name, args);
      const result = fn.apply(API, args);
      if (!replaying) {
        const last = journal[journal.length - 1];
        /* auto-save sends many updates: keep only the latest per quote */
        const who = { key: actor.key, name: actor.name, role: actor.role };
        if (name === 'updateQuote' && last && last[0] === 'updateQuote' && last[1][0] === args[0]) journal[journal.length - 1] = [name, args, who, stamp];
        else journal.push([name, args, who, stamp]);
        save();
      }
      return result;
    };
  });
  const liveActor = actor;
  replaying = true;
  journal.forEach(entry => {
    const who = entry[2] && AUTH.userByKey(entry[2].key || entry[2].role);
    actor = who ? Object.assign({}, who, { permissions: AUTH.permissionsFor(who.role) }) : liveActor;
    clock = entry[3] || null;
    try { API[entry[0]].apply(API, entry[1]); } catch (e) { /* skip entries that no longer apply */ }
  });
  replaying = false;
  clock = null;
  actor = liveActor;

  API.resetDemo = function () { AUTH.storage.del(JOURNAL_KEY); };

  window.RM_API = API;
})();
