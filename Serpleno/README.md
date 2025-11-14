# Serpleno (HTML + Supabase)

Migración en curso del antiguo backend PHP a un frontend 100% estático que consume Supabase.

## Directorios

```
legacy-php/           # Código PHP original para consulta histórica
public/
  *.html              # Pantallas migradas (splash, login, registro, reset, home, planes, plan_detail, content, notifications)
  assets/js/lib/      # Layout, sesión y cliente Supabase
  assets/js/pages/    # Controladores por vista
supabase/
  schema.sql          # Tablas y relaciones requeridas por Supabase
  policies.sql        # RLS + policies
```

## Configuración rápida

1. Crea `.env` copiando `.env.example` (las variables también deben existir en `public/env.js`).
2. Duplica `public/env.example.js` como `public/env.js` y pega las claves reales.
3. Ejecuta `supabase/schema.sql` y `supabase/policies.sql` en tu proyecto Supabase.
4. Sube `public/` a un hosting estático o sirve localmente con `python -m http.server`.

## Flujo soportado actualmente

- Splash (`index.html`) redirige al login si ya hay sesión.
- Autenticación completa (login, registro, reset) usando Supabase Auth.
- Home con carrusel leído desde Supabase Storage.
- Comparativa de planes y detalle con feedback almacenado en `notificaciones` (`tipo = plan_feedback`).
- Activación del plan gratuito vía `usuarios` + `suscripciones`.
- Contenido gratuito (`contenidos` + fallback local).
- Centro de notificaciones que consulta `notificaciones`, `suscripciones` y `reservas` para replicar el panel original.

El resto de pantallas conserva su código PHP dentro de `legacy-php/` mientras se completan las equivalencias HTML/JS.

## Variables de entorno

```
SUPABASE_URL
SUPABASE_ANON_KEY
MERCADOPAGO_PUBLIC_KEY
SUPABASE_STORAGE_SLIDES_BUCKET
SUPABASE_STORAGE_CONTENT_BUCKET
```

Cárgalas tanto en `.env` (para referencia) como en `public/env.js` (leído en el navegador).

## Notas

- `public/assets/js/lib/session.js` centraliza la sesión y aplica los guards por rol.
- Cada archivo en `public/assets/js/pages/` se encarga de leer y escribir directamente en las tablas establecidas (usuarios, planes, caracteristicas_plan, suscripciones, notificaciones, reservas, contenidos, etc.).
- `supabase/schema.sql` usa `uuid_generate_v4()` y referencia `auth.users` para cumplir con Supabase Auth.

