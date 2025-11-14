# Serpleno (HTML + Supabase)

Migración en curso del antiguo backend PHP a un frontend 100% estático que consume Supabase.

## Directorios

```
legacy-php/           # Código PHP original para consulta histórica
*.html                # Pantallas migradas (splash, login, registro, reset, home, planes, plan_detail, content, notifications)
assets/js/lib/        # Layout y helpers de sesión
assets/js/supabaseClient.js  # Único punto de conexión a Supabase
assets/js/pages/      # Controladores por vista
supabase/
  schema.sql          # Tablas y relaciones requeridas por Supabase
  policies.sql        # RLS + policies
```

## Configuración rápida

1. La conexión oficial ya está definida en `assets/js/supabaseClient.js` apuntando a `https://hkvzkvkriiguamjbuqyx.supabase.co`.
2. Ejecuta `supabase/schema.sql` y `supabase/policies.sql` dentro de ese proyecto Supabase.
3. Sube el contenido del repositorio (con `index.html` en la raíz) a un hosting estático o sirve localmente con `python -m http.server`.

## Flujo soportado actualmente

- Splash (`index.html`) redirige al login si ya hay sesión.
- Autenticación completa (login, registro, reset) usando Supabase Auth.
- Home con carrusel leído desde Supabase Storage.
- Comparativa de planes y detalle con feedback almacenado en `notificaciones` (`tipo = plan_feedback`).
- Activación del plan gratuito vía `usuarios` + `suscripciones`.
- Contenido gratuito (`contenidos` + fallback local).
- Centro de notificaciones que consulta `notificaciones`, `suscripciones` y `reservas` para replicar el panel original.

El resto de pantallas conserva su código PHP dentro de `legacy-php/` mientras se completan las equivalencias HTML/JS.

## Notas sobre configuración

- La clave pública y la URL de Supabase ya viven en `assets/js/supabaseClient.js`; no hay otras configuraciones duplicadas.
- Los buckets esperados son `home-slides` (carrusel de la home) y `contenidos` (material restringido).
- Puedes seguir utilizando un archivo `.env` local para anotar valores auxiliares, pero el frontend ya no depende de él.

## Notas

- `assets/js/lib/session.js` centraliza la sesión y aplica los guards por rol.
- Cada archivo en `assets/js/pages/` se encarga de leer y escribir directamente en las tablas establecidas (usuarios, planes, caracteristicas_plan, suscripciones, notificaciones, reservas, contenidos, etc.).
- `supabase/schema.sql` usa `uuid_generate_v4()` y referencia `auth.users` para cumplir con Supabase Auth.

