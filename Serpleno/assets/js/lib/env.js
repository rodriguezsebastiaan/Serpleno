export function loadEnv() {
  if (!window.__ENV__) {
    throw new Error('No se encontró window.__ENV__. Crea env.js a partir de env.example.js en la raíz del proyecto.');
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
