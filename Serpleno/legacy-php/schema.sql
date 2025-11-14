DROP TABLE IF EXISTS plan_features;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('cliente','profesional','admin') DEFAULT 'cliente',
    plan ENUM('gratuito','estudiantil','premium') DEFAULT 'gratuito',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(30) UNIQUE,
    name VARCHAR(60),
    description TEXT
);

CREATE TABLE plan_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_key VARCHAR(30),
    text VARCHAR(200)
);

CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120),
    category ENUM('cardio','rumba','nutricion','psicologia'),
    url TEXT,
    plan_visibility ENUM('gratuito','estudiantil','premium') DEFAULT 'gratuito'
);