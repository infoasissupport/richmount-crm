{{--
    Behaviour for leads.partials.table rows: the ⋮ row menu, the Quick view drawer and the edit buttons
    (edit opens leads.partials.form-modal, which the page must include for users who can edit). Include once per page.
--}}
<div class="drawer-wrap" id="leadDrawer" style="display: none;">
    <aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="leadDrawerTitle">
        <div class="drawer-head">
            <h3 id="leadDrawerTitle">Lead Details</h3>
            <button class="icon-btn" type="button" data-drawer-close aria-label="Close">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>
        <div class="drawer-body">
            <div class="dr-hero">
                <span class="avatar lg" data-f="avatar"></span>
                <div><h4 data-f="name"></h4><p data-f="subject"></p></div>
                <span class="pill" data-f="status"></span>
            </div>
            <ol class="stepper" data-f="stepper"></ol>
            <dl class="kv" data-f="rows"></dl>
        </div>
        <div class="drawer-foot">
            <a class="btn btn-primary" data-f="open">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-eye"/></svg>
                Open lead
            </a>
            <button type="button" class="btn btn-ghost" data-f="edit">
                <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-edit"/></svg>
                Edit lead
            </button>
        </div>
    </aside>
</div>

@push('scripts')
<script>
    // Row "more" menus: .popover is position:fixed, so place it under the button (flip up near the bottom edge)
    let openRowMenu = null;
    function closeRowMenu() {
        if (!openRowMenu) return;
        openRowMenu.menu.hidden = true;
        openRowMenu.btn.setAttribute('aria-expanded', 'false');
        openRowMenu = null;
    }
    function openRowMenuFor(btn) {
        const menu = btn.nextElementSibling;
        menu.hidden = false;
        const r = btn.getBoundingClientRect(), w = menu.offsetWidth, h = menu.offsetHeight;
        let top = r.bottom + 6;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
        menu.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
        menu.style.top = top + 'px';
        btn.setAttribute('aria-expanded', 'true');
        openRowMenu = { btn, menu };
    }

    // Lead Quick view drawer
    const LEAD_STAGES = [['new', 'New'], ['draft', 'Draft'], ['sent', 'Sent'], ['awarded', 'Awarded']];
    const svgIcon = (name, cls) => `<svg class="ic${cls ? ' ' + cls : ''}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-${name}"/></svg>`;
    const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function openLeadDrawer(d) {
        const wrap = document.getElementById('leadDrawer');
        const f = name => wrap.querySelector(`[data-f="${name}"]`);
        const avatar = f('avatar');
        avatar.className = `avatar lg av-${d.tone}`;
        avatar.textContent = d.initials;
        f('name').textContent = d.name;
        f('subject').textContent = d.subject || '';
        f('status').className = `pill pill-${d.statusTone}`;
        f('status').innerHTML = `<i></i>${escHtml(d.status)}`;
        const idx = LEAD_STAGES.findIndex(s => s[0] === d.stage);
        f('stepper').innerHTML = LEAD_STAGES.map((s, i) =>
            `<li class="${i < idx ? 'done' : i === idx ? 'current' : ''}"><span>${i < idx ? svgIcon('check', 'xs') : i + 1}</span>${s[1]}</li>`).join('');
        f('rows').innerHTML = d.rows.map(r => `<dt>${escHtml(r[0])}</dt><dd>${escHtml(r[1])}</dd>`).join('');
        f('open').href = d.url;
        f('edit').hidden = !d.edit;
        f('edit').onclick = d.edit ? () => { closeLeadDrawer(); openEditLeadModal(d.edit); } : null;
        wrap.style.display = 'flex';
        requestAnimationFrame(() => wrap.classList.add('open'));
        wrap.querySelector('[data-drawer-close]').focus();
    }
    function closeLeadDrawer() {
        const wrap = document.getElementById('leadDrawer');
        if (wrap.style.display === 'none') return;
        wrap.classList.remove('open');
        setTimeout(() => { wrap.style.display = 'none'; }, 200);
    }
    document.getElementById('leadDrawer').addEventListener('mousedown', e => { if (e.target.id === 'leadDrawer') closeLeadDrawer(); });
    document.querySelector('#leadDrawer [data-drawer-close]').addEventListener('click', closeLeadDrawer);

    document.addEventListener('click', function (e) {
        const ed = e.target.closest('[data-edit-lead]');
        if (ed) {
            closeRowMenu();
            openEditLeadModal(JSON.parse(ed.dataset.editLead));
            return;
        }
        const qv = e.target.closest('[data-quick-view]');
        if (qv) {
            closeRowMenu();
            openLeadDrawer(JSON.parse(qv.dataset.quickView));
            return;
        }
        const btn = e.target.closest('[data-row-menu]');
        if (btn) {
            const same = openRowMenu && openRowMenu.btn === btn;
            closeRowMenu();
            if (!same) openRowMenuFor(btn);
        } else if (!e.target.closest('.row-menu')) {
            closeRowMenu();
        }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeRowMenu(); closeLeadDrawer(); } });
    // fixed-position menus would drift away from their button, so close them on any scroll or resize
    document.addEventListener('scroll', closeRowMenu, { passive: true, capture: true });
    window.addEventListener('resize', closeRowMenu);
</script>
@endpush
