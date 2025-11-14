# Serpleno (HTML + Supabase)

Migración en curso del antiguo backend PHP a un frontend 100% estático que consume Supabase.

## Directorios

```txt
legacy-php/           # Código PHP original para consulta histórica

*.html                # Pantallas migradas (splash, login, registro, reset, home, planes, plan_detail, content, notifications)
assets/js/lib/        # Layout y helpers de sesión
assets/js/supabaseClient.js  # Único punto de conexión a Supabase
assets/js/pages/      # Controladores por vista

supabase/
  schema.sql          # Tablas y relaciones requeridas por Supabase
  policies.sql        # RLS + policies


