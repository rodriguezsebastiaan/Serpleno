INSERT INTO users (name,email,password_hash,role,plan) VALUES
('Rodri','rodricasta514@gmail.com','$2y$12$obXzNAGZhuaIGHVp1LZEheHmzevBEDKoESV5jkAa3mmpdXE7NU5PS','cliente','gratuito');

INSERT INTO plans (`key`,name,description) VALUES
('gratuito','Plan Gratuito','Acceso básico'),
('estudiantil','Plan Estudiantil','Plan con descuento'),
('premium','Plan Premium','Plan completo');

INSERT INTO plan_features (plan_key,text) VALUES
('gratuito','Contenido básico'),
('gratuito','Registro básico de perfil'),
('gratuito','Comunidad lectura'),
('gratuito','Recordatorios básicos'),
('gratuito','Eventos abiertos'),
('estudiantil','Todo lo de Gratuito'),
('estudiantil','40% descuento vs premium'),
('estudiantil','Sesiones con menor frecuencia'),
('estudiantil','Plan personalizado básico'),
('estudiantil','Acceso limitado a expertos'),
('estudiantil','Comunidad completa'),
('estudiantil','Acceso parcial a contenido premium'),
('premium','Todo lo de Gratuito'),
('premium','Asignación de profesionales'),
('premium','Sesiones personalizadas'),
('premium','Contenido premium completo'),
('premium','Plan alimentación/ejercicio adaptado'),
('premium','Seguimiento y reportes'),
('premium','Comunidad con interacción directa'),
('premium','Descuentos'),
('premium','Soporte técnico personalizado');

INSERT INTO videos (title,category,url,plan_visibility) VALUES
('Cardio Básico','cardio','https://www.w3schools.com/html/mov_bbb.mp4','gratuito'),
('Rumba Inicial','rumba','https://www.w3schools.com/html/movie.mp4','gratuito'),
('Nutrición Intro','nutricion','https://www.w3schools.com/html/mov_bbb.mp4','gratuito'),
('Psicología Tips','psicologia','https://www.w3schools.com/html/movie.mp4','gratuito');