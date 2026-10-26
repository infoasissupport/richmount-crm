/* Profile page (profile.html) — the signed-in demo user, their access, and user switching */
(function () {
  'use strict';
  const R = window.RM, API = R.API, AUTH = R.AUTH, $ = R.$, icon = R.icon, esc = R.esc;

  R.page({
    id: 'profile',
    permission: 'dashboard.view',
    title: 'My Profile',
    sub: 'The signed-in user, role, branch access and permissions used across the whole application.',
    crumbs: [['My Profile']],
    mount() {
      const me = R.me;
      const branches = me.branches === '*' ? API.branches : API.branches.filter(b => me.branches.includes(b.id));
      const card = $('#profileCard');
      card.querySelector('[data-f=avatar]').textContent = R.initials(me.name);
      card.querySelector('[data-f=avatar]').dataset.role = me.role;
      card.querySelector('[data-f=name]').textContent = me.name;
      card.querySelector('[data-f=email]').textContent = me.email;
      card.querySelector('[data-f=role]').textContent = me.roleLabel;
      card.querySelector('[data-f=branches]').textContent = me.branches === '*' ? 'All Branches' : branches.map(b => `${b.name} (${b.code})`).join(', ');
      card.querySelector('[data-f=scope]').textContent = me.engineerId ? 'Only leads, quotations, projects and cash records assigned to you' : me.branches === '*' ? 'Company-wide' : 'Records of your branch(es)';
      card.querySelector('[data-f=count]').textContent = `${AUTH.permissionsFor(me.role).size} of ${Object.keys(AUTH.PERMISSIONS).length}`;

      const perms = Array.from(AUTH.permissionsFor(me.role));
      $('#permList').innerHTML = perms.map(p => `<li><span class="tick yes">${icon('check', 'ic sm')}</span><span><b>${esc(AUTH.PERMISSIONS[p])}</b><code>${p}</code></span></li>`).join('');

      const tpl = $('#tplUser');
      const host = $('#userSwitch');
      AUTH.USERS.forEach(u => {
        const el = tpl.content.firstElementChild.cloneNode(true);
        el.querySelector('[data-f=avatar]').textContent = R.initials(u.name);
        el.querySelector('[data-f=avatar]').dataset.role = u.role;
        el.querySelector('[data-f=name]').textContent = u.name;
        el.querySelector('[data-f=role]').textContent = u.roleLabel;
        el.querySelector('[data-f=email]').textContent = u.email;
        const b = el.querySelector('button');
        if (u.key === me.key) { el.classList.add('current'); b.disabled = true; b.lastChild.textContent = 'Signed in'; }
        else b.addEventListener('click', () => R.switchRole(u.key));
        host.appendChild(el);
      });
    }
  });
})();
