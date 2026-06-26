# Versão ultra-robusta que cria os arquivos se não existirem
FROM php:8.2-apache AS backend

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip netcat-openbsd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite headers expires

# Config Apache
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# COPIA TUDO DE UMA VEZ (simplificado)
COPY backend/ .

# Se não tiver composer.lock, cria
RUN if [ ! -f composer.lock ]; then \
        composer install --no-dev --optimize-autoloader; \
    else \
        composer install --no-dev --optimize-autoloader; \
    fi

# Cria diretórios
RUN mkdir -p tmp/cache tmp/logs public/app \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 tmp

# --- FRONTEND ---
FROM node:18-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install --production
COPY frontend/ .
RUN npm run build || mkdir -p dist

# --- FINAL ---
FROM backend
COPY --from=frontend-builder /app/dist /var/www/html/public/app
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]