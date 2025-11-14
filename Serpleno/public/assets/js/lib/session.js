import { supabase } from '../supabaseClient.js';

let cachedUser = null;
let cachedProfile = null;
let profilePromise = null;

export function onAuthChanged(callback) {
  supabase.auth.onAuthStateChange(async () => {
    cachedUser = null;
    cachedProfile = null;
    profilePromise = null;
    callback(await getCurrentProfile());
  });
}

export async function getCurrentProfile() {
  if (profilePromise) {
    return profilePromise;
  }
  profilePromise = (async () => {
    const { data } = await supabase.auth.getUser();
    cachedUser = data?.user ?? null;
    if (!cachedUser) {
      cachedProfile = null;
      return null;
    }
    if (cachedProfile) return cachedProfile;
    const { data: profile, error } = await supabase
      .from('usuarios')
      .select('*')
      .eq('id', cachedUser.id)
      .single();
    if (error) {
      console.error('No se pudo obtener perfil', error);
      cachedProfile = {
        id: cachedUser.id,
        email: cachedUser.email,
        rol: 'cliente',
        plan: 'gratuito'
      };
      return cachedProfile;
    }
    cachedProfile = profile;
    return cachedProfile;
  })();
  return profilePromise;
}

export function isRole(role, profile) {
  const current = (profile?.rol || '').toLowerCase();
  return current === role;
}

export async function signOut() {
  await supabase.auth.signOut();
  cachedUser = null;
  cachedProfile = null;
  profilePromise = null;
  window.location.href = 'login.html';
}

export async function enforceGuards() {
  const body = document.body;
  const needsAuth = body.dataset.requireAuth === 'true';
  const allowed = (body.dataset.allowedRoles || '').split(',').map(r => r.trim()).filter(Boolean);
  const redirect = body.dataset.redirectIfAuthed;
  const profile = await getCurrentProfile();

  if (!needsAuth && profile && redirect) {
    window.location.href = redirect;
    return;
  }

  if (needsAuth && !profile) {
    window.location.href = 'login.html';
    return;
  }

  if (needsAuth && allowed.length && profile && !allowed.includes((profile.rol || 'cliente').toLowerCase())) {
    if (profile.rol === 'admin') {
      window.location.href = 'admin/dashboard.html';
    } else if ((profile.rol || '').startsWith('pro')) {
      window.location.href = 'pro/dashboard.html';
    } else {
      window.location.href = 'home.html';
    }
  }
}

export function getRoleLanding(role) {
  switch ((role || '').toLowerCase()) {
    case 'admin':
      return 'admin/dashboard.html';
    case 'profesional':
    case 'pro':
      return 'pro/dashboard.html';
    default:
      return 'home.html';
  }
}
