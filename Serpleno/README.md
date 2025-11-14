# Serpleno (HTML + Supabase)

Migración en curso del antiguo backend PHP a un frontend 100% estático (HTML + CSS + JavaScript puro) que consume Supabase para autenticación, base de datos y storage.

> **Importante:** ya no existe el directorio `public/`. Todos los archivos se sirven directamente desde la raíz del repositorio para que puedas abrirlos con cualquier servidor estático.

## Directorios principales

```txt
legacy-php/                 # Código PHP original solo para referencia histórica
assets/js/lib/              # Helpers compartidos (layout, sesión, Supabase client)
assets/js/pages/            # Controladores específicos por pantalla
supabase/schema.sql         # Tablas y relaciones requeridas
supabase/policies.sql       # Row Level Security + policies
*.html                      # Todas las pantallas migradas (login, registro, home, planes, etc.)
styles.css                  # Hoja de estilos original, intacta
env.example.js              # Plantilla para generar tu env.js del navegador
.env.example                # Variables espejo para automatizaciones o CI
```

## Variables de entorno

El frontend lee las llaves desde un archivo **`env.js`** en la raíz del proyecto que expone `window.__ENV__`. Ese archivo no se versiona (está ignorado en `.gitignore`).

1. Copia la plantilla y edítala con tus llaves reales:
   ```bash
   cp env.example.js env.js
   ```
2. Opcionalmente, si necesitas integrar CI/CD o scripts de automatización, también cuentas con `.env.example`, que replica los mismos nombres de variables en formato tradicional.

Campos requeridos:

| Variable | Descripción |
| --- | --- |
| `SUPABASE_URL` | URL del proyecto Supabase (https://XXXX.supabase.co) |
| `SUPABASE_ANON_KEY` | Public anon key del proyecto |
| `MERCADOPAGO_PUBLIC_KEY` | Public key para el checkout JS |
| `MERCADOPAGO_ACCESS_TOKEN` | Access token para webhooks/validación |
| `SUPABASE_STORAGE_SLIDES_BUCKET` | Bucket para el carrusel de la home |
| `SUPABASE_STORAGE_CONTENT_BUCKET` | Bucket para archivos de contenido |

Si `env.js` no existe, la app mostrará un error claro indicando que debes crearlo.

## Configuración de Supabase

1. Crea un nuevo proyecto Supabase.
2. En la sección SQL Editor pega y ejecuta `supabase/schema.sql` y luego `supabase/policies.sql` para crear tablas, relaciones y RLS.
3. En **Storage** crea los buckets indicados en las variables (`home-slides`, `contenidos`) con acceso público de solo lectura.
4. En **Authentication › Providers** deja habilitado el proveedor de Email y personaliza los templates según tus textos actuales.
5. En la tabla `usuarios` sincroniza los roles/planes existentes mediante SQL o cargando tus CSV.

## Ejecutar localmente

Al ser un frontend estático, basta con servir la raíz del repositorio. Algunas opciones:

```bash
# Opción 1: usando Python 3
python -m http.server 4173

# Opción 2: usando npm serve
npm install -g serve
serve .
```

Luego abre `http://localhost:4173/index.html` (o el puerto que utilices). Desde ahí podrás navegar a `login.html`, `home.html`, etc. También puedes abrir los HTML directamente desde el explorador de archivos, siempre y cuando cuentes con `env.js` configurado.

## Flujo general

1. **Autenticación:** gestionada por Supabase Auth. Los helpers en `assets/js/lib/session.js` leen el perfil desde la tabla `usuarios`.
2. **Navegación/Layout:** `assets/js/lib/layout.js` pinta el header/footer originales y controla los enlaces según el rol.
3. **Páginas:** cada HTML carga su controlador desde `assets/js/pages/*.js`, donde se replicó la lógica del antiguo PHP (planes, feedback, notificaciones, etc.).
4. **Base de datos:** todas las operaciones se realizan con el cliente oficial de Supabase (`@supabase/supabase-js@2` importado desde CDN ESM).

## Siguientes pasos

- Completar la migración del resto de pantallas (panel de admin, agenda de profesionales, pagos) siguiendo el mismo patrón.
- Definir policies adicionales según tus reglas específicas (por ejemplo, permitir que el rol `admin` lea todas las tablas).
- Conectar MercadoPago JS al flujo de `plan_detail`/`pay.html` cuando se migre esa pantalla.

Ante cualquier duda consulta el código PHP legado en `legacy-php/` para comparar flujos.
