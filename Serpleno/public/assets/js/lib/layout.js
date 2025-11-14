import { getCurrentProfile, signOut } from './session.js';

const navLinks = [
  { href: 'home.html', label: 'Inicio', roles: ['cliente','admin','pro','profesional'] },
  { href: 'plans.html', label: 'Planes', roles: ['cliente','admin','pro','profesional'] },
  { href: 'notifications.html', label: 'Notificaciones', roles: ['cliente','admin','pro','profesional'] },
];

export async function renderLayout() {
  const [header, footer] = [document.querySelector('[data-app-header]'), document.querySelector('[data-app-footer]')];
  if (!header || !footer) return;
  const profile = await getCurrentProfile();
  const isAuthPage = document.body.dataset.isAuthPage === 'true';

  header.innerHTML = `
    <nav class="navbar">
      <a class="brand" href="${profile ? 'home.html' : 'login.html'}">
        <img class="brand-logo" src="logo%20empresa.png" alt="Serpleno" width="22" height="22" style="width:22px;height:auto;max-height:24px;object-fit:contain;">
        <span class="brand-text">SERPLENO</span>
      </a>
      <ul></ul>
    </nav>
  `;

  const list = header.querySelector('ul');
  if (!isAuthPage) {
    if (profile) {
      for (const link of navLinks) {
        if (link.roles.includes((profile.rol || 'cliente').toLowerCase())) {
          const li = document.createElement('li');
          const a = document.createElement('a');
          a.href = link.href;
          a.textContent = link.label;
          li.appendChild(a);
          list.appendChild(li);
        }
      }
      const logoutLi = document.createElement('li');
      const logoutBtn = document.createElement('button');
      logoutBtn.className = 'link-button';
      logoutBtn.textContent = 'Salir';
      logoutBtn.addEventListener('click', signOut);
      logoutLi.appendChild(logoutBtn);
      list.appendChild(logoutLi);
    } else {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = 'login.html';
      a.textContent = 'Ingresar';
      li.appendChild(a);
      list.appendChild(li);
    }
  }

  footer.innerHTML = `<p>Todos los derechos reservados. © ${new Date().getFullYear()} Serpleno S.A.S — NIT 900.123.456-7.</p>`;
}
