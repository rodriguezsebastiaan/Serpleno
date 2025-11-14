import { supabase } from '../lib/supabaseClient.js';

const form = document.querySelector('[data-register-form]');
const errorBox = document.querySelector('[data-register-error]');
const successBox = document.querySelector('[data-register-success]');

if (form) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideAlerts();
    const data = new FormData(form);
    const name = data.get('name');
    const email = data.get('email');
    const password = data.get('password');
    if (!name || !email || !password || password.length < 6) {
      showError('Completa todos los campos (mínimo 6 caracteres para la contraseña)');
      return;
    }
    form.querySelector('button[type="submit"]').disabled = true;
    const { error } = await supabase.auth.signUp({
      email,
      password,
      options: {
        data: { nombre: name, rol: 'cliente', plan: 'gratuito' }
      }
    });
    form.querySelector('button[type="submit"]').disabled = false;
    if (error) {
      showError(error.message || 'No se pudo registrar');
      return;
    }
    showSuccess('Registro exitoso. Ahora puedes iniciar sesión.');
    form.reset();
  });
}

function hideAlerts() {
  if (errorBox) errorBox.style.display = 'none';
  if (successBox) successBox.style.display = 'none';
}

function showError(message) {
  if (!errorBox) return;
  errorBox.textContent = message;
  errorBox.style.display = 'block';
}

function showSuccess(message) {
  if (!successBox) return;
  successBox.textContent = message;
  successBox.style.display = 'block';
}
