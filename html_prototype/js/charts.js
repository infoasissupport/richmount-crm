/* =========================================================
   Rich Mount ERP — lightweight SVG charts (no dependencies)
   RMCharts.line / RMCharts.donut / RMCharts.bars
   line: o.area === false draws plain lines; bars: o.grouped draws side-by-side bars
   ========================================================= */
(function () {
  'use strict';
  let uid = 0;

  const tipEl = () => document.getElementById('chartTip');
  function showTip(html, x, y) {
    const t = tipEl(); if (!t) return;
    t.innerHTML = html;
    t.classList.add('show');
    const r = t.getBoundingClientRect();
    let left = x + 14, top = y - r.height - 12;
    if (left + r.width > window.innerWidth - 8) left = x - r.width - 14;
    if (top < 8) top = y + 16;
    t.style.left = left + 'px';
    t.style.top = top + 'px';
  }
  function hideTip() { const t = tipEl(); if (t) t.classList.remove('show'); }

  function scale(max, ticks) {
    ticks = ticks || 4;
    if (max <= 0) return { max: ticks, step: 1 };
    const raw = max / ticks;
    const mag = Math.pow(10, Math.floor(Math.log10(raw)));
    const n = raw / mag;
    const step = (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * mag;
    return { max: step * ticks, step: step };
  }
  function short(n) {
    if (n >= 1e6) return (n / 1e6).toFixed(n >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(n >= 1e4 ? 0 : 1).replace(/\.0$/, '') + 'K';
    return String(Math.round(n * 10) / 10);
  }
  const fmt = n => Math.round(n).toLocaleString('en-US');

  function yAxis(sc, P, W, y) {
    let s = '';
    for (let v = 0; v <= sc.max + 1e-9; v += sc.step) {
      const yy = y(v).toFixed(1);
      s += `<line x1="${P.l}" x2="${W - P.r}" y1="${yy}" y2="${yy}" class="grid"/>`;
      s += `<text x="${P.l - 8}" y="${(+yy + 3.5).toFixed(1)}" class="ylab" text-anchor="end">${short(v)}</text>`;
    }
    return s;
  }

  /* ---------- line / area ---------- */
  function line(el, o) {
    const W = Math.max(260, el.clientWidth), H = o.height || 170;
    const P = { l: o.prefix ? 40 : 34, r: 14, t: 10, b: 24 };
    const iw = W - P.l - P.r, ih = H - P.t - P.b, n = o.labels.length;
    const sc = scale(Math.max(1, ...o.series.flatMap(s => s.values)));
    const x = i => P.l + (n === 1 ? iw / 2 : (i * iw) / (n - 1));
    const y = v => P.t + ih - (v / sc.max) * ih;
    const id = 'lc' + (++uid);

    let svg = `<svg class="chart-svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" role="img" aria-label="${o.label || 'Trend chart'}"><defs>`;
    o.series.forEach((s, si) => {
      svg += `<linearGradient id="${id}g${si}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s.color}" stop-opacity="${si ? .16 : .2}"/><stop offset="1" stop-color="${s.color}" stop-opacity="0"/></linearGradient>`;
    });
    svg += '</defs>' + yAxis(sc, P, W, y);

    const every = Math.max(1, Math.ceil(n / Math.max(1, Math.floor(iw / 46))));
    o.labels.forEach((l, i) => {
      if (i % every === 0) svg += `<text x="${x(i).toFixed(1)}" y="${H - 6}" class="xlab" text-anchor="middle">${l}</text>`;
    });

    o.series.forEach((s, si) => {
      const pts = s.values.map((v, i) => [x(i), y(v)]);
      const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
      if (o.area !== false) svg += `<path d="${d} L${x(n - 1).toFixed(1)} ${P.t + ih} L${x(0).toFixed(1)} ${P.t + ih} Z" fill="url(#${id}g${si})"/>`;
      svg += `<path d="${d}" fill="none" stroke="${s.color}" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>`;
      pts.forEach(p => { svg += `<circle cx="${p[0].toFixed(1)}" cy="${p[1].toFixed(1)}" r="3" fill="#fff" stroke="${s.color}" stroke-width="1.8"/>`; });
    });
    svg += `<line class="guide" x1="0" x2="0" y1="${P.t}" y2="${P.t + ih}" style="opacity:0"/>`;
    svg += `<rect class="hit" x="${P.l - 6}" y="${P.t}" width="${iw + 12}" height="${ih}" fill="transparent"/></svg>`;
    el.innerHTML = svg;

    const hit = el.querySelector('.hit'), guide = el.querySelector('.guide');
    hit.addEventListener('mousemove', e => {
      const r = hit.getBoundingClientRect();
      const rel = (e.clientX - r.left - 6) / (r.width - 12);
      const i = Math.max(0, Math.min(n - 1, Math.round(rel * (n - 1))));
      guide.setAttribute('x1', x(i)); guide.setAttribute('x2', x(i)); guide.style.opacity = 1;
      showTip(`<b>${o.labels[i]}</b>` + o.series.map(s =>
        `<div><i style="background:${s.color}"></i>${s.name}<span>${o.prefix || ''}${fmt(s.values[i])}</span></div>`).join(''), e.clientX, e.clientY);
    });
    hit.addEventListener('mouseleave', () => { guide.style.opacity = 0; hideTip(); });
  }

  /* ---------- donut ---------- */
  function donut(el, o) {
    const S = o.size || 150, th = o.thickness || 24, r = (S - th) / 2, c = S / 2, C = 2 * Math.PI * r;
    const total = o.segments.reduce((s, x) => s + x.value, 0);
    const nonZero = o.segments.filter(s => s.value).length;
    let off = 0, arcs = '';
    if (!total) arcs = `<circle cx="${c}" cy="${c}" r="${r}" fill="none" stroke="#EEF0F3" stroke-width="${th}"/>`;
    o.segments.forEach((sg, i) => {
      if (!sg.value) return;
      const len = (sg.value / total) * C, gap = nonZero > 1 ? 2 : 0;
      arcs += `<circle class="arc" data-i="${i}" cx="${c}" cy="${c}" r="${r}" fill="none" stroke="${sg.color}" stroke-width="${th}"
        stroke-dasharray="${Math.max(0, len - gap).toFixed(2)} ${C.toFixed(2)}" stroke-dashoffset="${(-off).toFixed(2)}" transform="rotate(-90 ${c} ${c})"/>`;
      off += len;
    });
    el.innerHTML = `<svg class="chart-svg" width="${S}" height="${S}" viewBox="0 0 ${S} ${S}" role="img" aria-label="${o.label || ''} ${total}">${arcs}
      <text x="${c}" y="${c - 1}" text-anchor="middle" class="donut-total">${total}</text>
      <text x="${c}" y="${c + 17}" text-anchor="middle" class="donut-label">${o.label || ''}</text></svg>`;
    el.querySelectorAll('.arc').forEach(a => {
      const sg = o.segments[+a.dataset.i];
      a.addEventListener('mousemove', e => showTip(`<div><i style="background:${sg.color}"></i>${sg.name}<span>${sg.value} · ${Math.round(sg.value / total * 100)}%</span></div>`, e.clientX, e.clientY));
      a.addEventListener('mouseleave', hideTip);
      a.addEventListener('click', () => { hideTip(); if (o.onClick) o.onClick(sg); });
    });
  }

  /* ---------- stacked bars ---------- */
  function bars(el, o) {
    const W = Math.max(240, el.clientWidth), H = o.height || 170;
    const P = { l: 40, r: 8, t: 10, b: 24 };
    const iw = W - P.l - P.r, ih = H - P.t - P.b, n = o.labels.length;
    const totals = o.labels.map((_, i) => o.series.reduce((s, se) => s + se.values[i], 0));
    const sc = scale(Math.max(1, ...(o.grouped ? o.series.flatMap(se => se.values) : totals)));
    const ns = o.series.length, band = iw / Math.max(1, n);
    const bw = o.grouped ? Math.max(4, Math.min(12, band * 0.7 / ns)) : Math.max(6, Math.min(18, band * 0.45));
    const y = v => P.t + ih - (v / sc.max) * ih;

    let svg = `<svg class="chart-svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" role="img" aria-label="${o.label || 'Bar chart'}">` + yAxis(sc, P, W, y);
    const every = Math.max(1, Math.ceil(n / Math.max(1, Math.floor(iw / 40))));
    for (let i = 0; i < n; i++) {
      const cx = P.l + band * i + band / 2;
      const strong = !o.highlightLast || i === n - 1;
      let base = P.t + ih;
      svg += `<rect class="bar-hit" data-i="${i}" x="${(P.l + band * i).toFixed(1)}" y="${P.t}" width="${band.toFixed(1)}" height="${ih}" fill="transparent"/>`;
      o.series.forEach((se, si) => {
        const h = (se.values[i] / sc.max) * ih;
        if (h <= 0) return;
        let bx = cx - bw / 2;
        if (o.grouped) { bx = cx - (ns * bw + (ns - 1) * 2) / 2 + si * (bw + 2); base = P.t + ih - h; }
        else base -= h;
        svg += `<rect x="${bx.toFixed(1)}" y="${base.toFixed(1)}" width="${bw.toFixed(1)}" height="${Math.max(1, h - 1).toFixed(1)}" rx="2" fill="${se.color}" opacity="${strong ? 1 : .62}" pointer-events="none"/>`;
      });
      if (i % every === 0) svg += `<text x="${cx.toFixed(1)}" y="${H - 6}" class="xlab" text-anchor="middle">${o.labels[i]}</text>`;
    }
    svg += '</svg>';
    el.innerHTML = svg;
    el.querySelectorAll('.bar-hit').forEach(b => {
      const i = +b.dataset.i;
      b.addEventListener('mousemove', e => showTip(`<b>${o.labels[i]}</b>` + o.series.map(se =>
        `<div><i style="background:${se.color}"></i>${se.name}<span>${o.prefix || ''}${fmt(se.values[i])}</span></div>`).join('') +
        `<div style="border-top:1px solid #333;margin-top:4px;padding-top:4px">Total<span>${o.prefix || ''}${fmt(totals[i])}</span></div>`, e.clientX, e.clientY));
      b.addEventListener('mouseleave', hideTip);
    });
  }

  window.RMCharts = { line, donut, bars, hideTip };
})();
