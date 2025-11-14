create extension if not exists "uuid-ossp";

create table if not exists planes (
  id uuid primary key default uuid_generate_v4(),
  slug text unique not null,
  nombre text not null,
  descripcion text,
  precio_mensual numeric default 0,
  precio_anual numeric default 0,
  creado_en timestamptz default now()
);

create table if not exists caracteristicas_plan (
  id uuid primary key default uuid_generate_v4(),
  nombre text not null,
  descripcion text,
  planes text[] not null default '{}'::text[]
);

create table if not exists usuarios (
  id uuid primary key references auth.users(id) on delete cascade,
  nombre text,
  email text unique,
  rol text default 'cliente' check (rol in ('cliente','profesional','admin')),
  plan text default 'gratuito',
  area text,
  especialidad text,
  created_at timestamptz default now()
);

create table if not exists suscripciones (
  id uuid primary key default uuid_generate_v4(),
  usuario_id uuid not null references usuarios(id) on delete cascade,
  plan_slug text not null references planes(slug) on delete cascade,
  estado text not null default 'pendiente',
  fecha_inicio timestamptz default now(),
  fecha_fin timestamptz,
  renovacion_auto boolean default false,
  unique (usuario_id)
);

create table if not exists pagos (
  id uuid primary key default uuid_generate_v4(),
  suscripcion_id uuid references suscripciones(id) on delete set null,
  monto numeric not null,
  moneda text default 'COP',
  metodo text default 'mercadopago',
  estado text default 'pendiente',
  referencia text,
  payload jsonb default '{}'::jsonb,
  created_at timestamptz default now()
);

create table if not exists notificaciones (
  id uuid primary key default uuid_generate_v4(),
  usuario_id uuid references usuarios(id) on delete cascade,
  titulo text,
  mensaje text,
  tipo text,
  datos jsonb default '{}'::jsonb,
  leida boolean default false,
  created_at timestamptz default now()
);

create table if not exists disponibilidad_pro (
  id uuid primary key default uuid_generate_v4(),
  profesional_id uuid not null references usuarios(id) on delete cascade,
  fecha date not null,
  hora_inicio time not null,
  hora_fin time not null,
  estado text default 'disponible'
);

create table if not exists reservas (
  id uuid primary key default uuid_generate_v4(),
  cliente_id uuid not null references usuarios(id) on delete cascade,
  profesional_id uuid not null references usuarios(id) on delete cascade,
  disponibilidad_id uuid references disponibilidad_pro(id) on delete set null,
  estado text default 'reservada',
  nota text,
  link_reunion text,
  created_at timestamptz default now()
);

create table if not exists sesiones_reunion (
  id uuid primary key default uuid_generate_v4(),
  reserva_id uuid references reservas(id) on delete cascade,
  url text,
  estado text default 'pendiente',
  inicio timestamptz,
  fin timestamptz
);

create table if not exists contenidos (
  id uuid primary key default uuid_generate_v4(),
  categoria text not null,
  titulo text not null,
  descripcion text,
  dia text,
  tipo text not null default 'video',
  url text not null,
  plan_minimo text default 'gratuito'
);

create table if not exists archivos_contenido (
  id uuid primary key default uuid_generate_v4(),
  contenido_id uuid references contenidos(id) on delete cascade,
  archivo_url text not null,
  tipo text default 'archivo'
);

create table if not exists cargas_pro (
  id uuid primary key default uuid_generate_v4(),
  profesional_id uuid references usuarios(id) on delete cascade,
  categoria text,
  titulo text not null,
  descripcion text,
  archivo_url text,
  tipo text default 'link',
  creado_en timestamptz default now()
);

