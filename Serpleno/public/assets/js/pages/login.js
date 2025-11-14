import { supabase } from '../lib/supabaseClient.js';
import { getRoleLanding } from '../lib/session.js';

const form = document.querySelector('[data-login-form]');
const errorBox = document.querySelector('[data-login-error]');

if (form) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideError();
    const data = new FormData(form);
    const email = data.get('email');
    const password = data.get('password');
    if (!email || !password) {
      showError('Email y contraseña son obligatorios');
      return;
    }
    form.querySelector('button[type="submit"]').disabled = true;
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    form.querySelector('button[type="submit"]').disabled = false;
    if (error) {
      showError('Email o contraseña incorrectos');
      return;
    }
    const { data } = await supabase.auth.getUser();
    const roleMetadata = data?.user?.user_metadata?.rol;
    window.location.href = getRoleLanding(roleMetadata || 'cliente');
  });
}

function showError(message) {
  if (!errorBox) return;
  errorBox.textContent = message;
  errorBox.style.display = 'block';
}

function hideError() {
  if (!errorBox) return;
  errorBox.style.display = 'none';
}
