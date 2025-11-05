# PHP 8.2 CLI
FROM php:8.2-cli

# Extensiones para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Directorio de trabajo
WORKDIR /app

# Copiar el proyecto
COPY . .

# Puerto donde corre el servidor embebido
EXPOSE 10000

# Iniciar servidor PHP sirviendo /app 
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/app"]
