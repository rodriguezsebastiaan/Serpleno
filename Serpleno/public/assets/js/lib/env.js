export function loadEnv() {
  if (!window.__ENV__) {
    throw new Error('No se encontró window.__ENV__. Crea public/env.js a partir de public/env.example.js.');
  }
  const required = [
    'SUPABASE_URL',
    'SUPABASE_ANON_KEY',
    'MERCADOPAGO_PUBLIC_KEY',
    'SUPABASE_STORAGE_SLIDES_BUCKET',
    'SUPABASE_STORAGE_CONTENT_BUCKET'
  ];
  for (const key of required) {
    if (!window.__ENV__[key]) {
      throw new Error(`Falta la variable ${key} en env.js`);
    }
  }
  return window.__ENV__;
}
