# Multi-stage build para otimização
FROM php:8.2-apache AS backend

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    netcat-openbsd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configura Apache
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires

# Configuração do Apache inline
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Instala Node.js para build do Astro
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# Copia composer.json primeiro para cache
COPY backend/composer.json ./

# Instala dependências (incluindo Elefant)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copia o resto do backend
COPY backend/ .

# Cria diretórios necessários
RUN mkdir -p tmp/cache tmp/logs public/app \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 tmp

# --- STAGE DE BUILD DO FRONTEND ---
FROM node:18-alpine AS frontend-builder

WORKDIR /app

# Copia package.json
COPY frontend/package*.json ./

# Instala dependências
RUN npm ci --only=production 2>/dev/null || npm install --production

# Copia código fonte
COPY frontend/ .

# Build do Astro
RUN npm run build || (echo "⚠️ Build falhou" && mkdir -p dist)

# --- STAGE FINAL ---
FROM backend

# Copia o build do frontend
COPY --from=frontend-builder /app/dist /var/www/html/public/app

# Copia script de entrada
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configura variáveis de ambiente
ENV PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_MAX_FILESIZE=50M \
    PHP_POST_MAX_SIZE=50M

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]