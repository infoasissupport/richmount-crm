/**
 * Rich Mount ERP - Sidebar Navigation & Flyout Submenu Controller
 * Flyout sub-menu opens ONLY on click.
 */
(function () {
    let flyout = null;

    function closeFlyout() {
        if (!flyout) return;
        flyout.item.classList.remove('flyout-open');
        flyout.item.setAttribute('aria-expanded', 'false');
        if (flyout.el && flyout.el.parentNode) {
            flyout.el.remove();
        }
        flyout = null;
    }

    function openFlyout(item) {
        closeFlyout();
        const group = item.closest('.nav-group');
        if (!group) return;
        const subLinks = Array.from(group.querySelectorAll('.nav-subitem'));
        if (!subLinks.length) return;

        const label = item.querySelector('.nav-label')?.textContent.trim() || '';
        const iconUse = item.querySelector('.nav-ic use');
        const iconHref = iconUse ? iconUse.getAttribute('href') : '';

        const el = document.createElement('div');
        el.className = 'nav-flyout';
        el.setAttribute('role', 'menu');
        el.innerHTML = '<div class="nf-head">' +
            (iconHref ? '<svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><use href="' + iconHref + '"/></svg>' : '') +
            '<span>' + label.toUpperCase() + '</span>' +
            '</div>' +
            subLinks.map(function (a) {
                const isActive = a.classList.contains('active') ? ' active' : '';
                return '<a class="nf-link' + isActive + '" role="menuitem" href="' + a.getAttribute('href') + '"><i></i>' + a.textContent + '</a>';
            }).join('');

        document.body.appendChild(el);

        const r = item.getBoundingClientRect();
        const sidebar = document.querySelector('#sidebar .sidebar-inner') || document.querySelector('#sidebar');
        const sideRect = sidebar ? sidebar.getBoundingClientRect() : { right: 240 };
        const top = Math.max(8, Math.min(r.top - 6, window.innerHeight - el.offsetHeight - 8));

        el.style.left = (sideRect.right + 10) + 'px';
        el.style.top = top + 'px';
        const arrowTop = Math.max(12, Math.min(r.top + r.height / 2 - top - 6, el.offsetHeight - 24));
        el.style.setProperty('--arrow', arrowTop + 'px');

        item.classList.add('flyout-open');
        item.setAttribute('aria-expanded', 'true');
        flyout = { el: el, item: item };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const nav = document.getElementById('nav');
        if (!nav) return;

        // Open sub-menu ONLY when clicked
        nav.addEventListener('click', function (e) {
            const item = e.target.closest('.nav-item[data-nav]');
            if (!item) return;

            e.preventDefault();

            // Desktop: toggle flyout beside sidebar
            if (window.innerWidth >= 1200) {
                if (flyout && flyout.item === item) {
                    closeFlyout();
                } else {
                    openFlyout(item);
                }
                return;
            }

            // Mobile: toggle inline accordion submenu
            const group = item.closest('.nav-group');
            const isOpen = group.classList.contains('open');
            document.querySelectorAll('#nav .nav-group.open').forEach(function (g) {
                if (g !== group) {
                    g.classList.remove('open');
                    const b = g.querySelector('.nav-item');
                    if (b) b.setAttribute('aria-expanded', 'false');
                }
            });
            group.classList.toggle('open', !isOpen);
            item.setAttribute('aria-expanded', String(!isOpen));
        });

        // Close flyout when clicking anywhere outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.nav-flyout') && !e.target.closest('.nav-item[data-nav]')) {
                closeFlyout();
            }
        });

        // Close flyout on scroll
        nav.addEventListener('scroll', closeFlyout, { passive: true });
        window.addEventListener('scroll', closeFlyout, { passive: true });

        // Sidebar search filter
        const menuSearch = document.getElementById('menuSearch');
        if (menuSearch) {
            menuSearch.addEventListener('input', function (e) {
                closeFlyout();
                const q = e.target.value.trim().toLowerCase();
                nav.classList.toggle('searching', !!q);
                let any = false;
                document.querySelectorAll('#nav .nav-group').forEach(function (g) {
                    const label = g.querySelector('.nav-label')?.textContent.toLowerCase() || '';
                    const subs = g.querySelectorAll('.nav-subitem');
                    let mSub = false;
                    subs.forEach(function (s) {
                        const m = s.textContent.toLowerCase().includes(q);
                        s.style.display = m ? 'block' : 'none';
                        if (m) mSub = true;
                    });
                    const match = label.includes(q) || mSub;
                    g.style.display = match ? 'block' : 'none';
                    if (match) any = true;
                    if (q && mSub) g.classList.add('open');
                });
            });
        }
    });
})();
