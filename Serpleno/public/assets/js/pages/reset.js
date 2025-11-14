import { supabase } from '../lib/supabaseClient.js';

const form = document.querySelector('[data-reset-form]');
const errorBox = document.querySelector('[data-reset-error]');
const successBox = document.querySelector('[data-reset-success]');
const params = new URLSearchParams(window.location.hash.replace('#', '?'));
const isRecovery = params.get('type') === 'recovery';

if (form) {
  if (!isRecovery) {
    form.querySelector('label:nth-child(2)').style.display = 'none';
    form.querySelector('label:nth-child(3)').style.display = 'none';
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    hideAlerts();
    const data = new FormData(form);
    const email = data.get('email');
    const pass1 = data.get('pass1');
    const pass2 = data.get('pass2');

    if (!email) {
      showError('Todos los campos son obligatorios');
      return;
    }

    if (!isRecovery) {
      await requestReset(email);
    } else {
      if (!pass1 || !pass2 || pass1 !== pass2 || pass1.length < 6) {
        showError('Las contraseñas deben coincidir y tener mínimo 6 caracteres');
        return;
      }
      await updatePassword(pass1);
    }
  });
}

async function requestReset(email) {
  const { error } = await supabase.auth.resetPasswordForEmail(email, {
    redirectTo: window.location.origin + window.location.pathname
  });
  if (error) {
    showError(error.message || 'No se pudo enviar el correo de recuperación');
    return;
  }
  showSuccess('Si el correo existe, recibirás un enlace para continuar.');
}

async function updatePassword(newPassword) {
  const accessToken = params.get('access_token');
  const refreshToken = params.get('refresh_token');
  if (!accessToken || !refreshToken) {
    showError('El enlace de recuperación es inválido o expiró.');
    return;
  }
  await supabase.auth.setSession({ access_token: accessToken, refresh_token: refreshToken });
  const { error } = await supabase.auth.updateUser({ password: newPassword });
  if (error) {
    showError(error.message || 'No se pudo actualizar la contraseña');
    return;
  }
  showSuccess('Contraseña actualizada. Ya puedes iniciar sesión.');
  window.location.hash = '';
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

function hideAlerts() {
  if (errorBox) errorBox.style.display = 'none';
  if (successBox) successBox.style.display = 'none';
}
