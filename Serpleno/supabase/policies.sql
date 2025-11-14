alter table usuarios enable row level security;
alter table suscripciones enable row level security;
alter table pagos enable row level security;
alter table notificaciones enable row level security;
alter table disponibilidad_pro enable row level security;
alter table reservas enable row level security;
alter table sesiones_reunion enable row level security;
alter table contenidos enable row level security;
alter table archivos_contenido enable row level security;
alter table cargas_pro enable row level security;
alter table planes enable row level security;
alter table caracteristicas_plan enable row level security;

create policy "Usuarios pueden ver su propio perfil" on usuarios for select
using (auth.uid() = id or exists (select 1 from usuarios u where u.id = auth.uid() and u.rol = 'admin'));

create policy "Usuarios actualizan su perfil" on usuarios for update
using (auth.uid() = id);

create policy "Admin inserta usuarios" on usuarios for insert
with check (exists (select 1 from usuarios u where u.id = auth.uid() and u.rol = 'admin'));

create policy "Lectura publica de planes" on planes for select using (true);
create policy "Lectura publica de caracteristicas" on caracteristicas_plan for select using (true);

create policy "Usuarios gestionan sus suscripciones" on suscripciones for all
using (auth.uid() = usuario_id) with check (auth.uid() = usuario_id);

create policy "Usuarios ven sus pagos" on pagos for select
using (auth.uid() = (select usuario_id from suscripciones where id = suscripcion_id));

create policy "Admin inserta pagos" on pagos for insert
with check (exists (select 1 from usuarios u where u.id = auth.uid() and u.rol = 'admin'));

create policy "Usuarios gestionan notificaciones" on notificaciones for all
using (auth.uid() = usuario_id) with check (auth.uid() = usuario_id);

create policy "Profesionales gestionan disponibilidad" on disponibilidad_pro for all
using (auth.uid() = profesional_id) with check (auth.uid() = profesional_id);

create policy "Clientes crean reservas" on reservas for insert
with check (auth.uid() = cliente_id);

create policy "Usuarios ven sus reservas" on reservas for select
using (auth.uid() = cliente_id or auth.uid() = profesional_id);

create policy "Usuarios actualizan reservas propias" on reservas for update
using (auth.uid() = cliente_id or auth.uid() = profesional_id);

create policy "Usuarios ven contenidos" on contenidos for select using (true);

create policy "Profesionales administran cargas" on cargas_pro for all
using (auth.uid() = profesional_id) with check (auth.uid() = profesional_id);
