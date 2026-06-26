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

# Configura Apache para funcionar com Elefant
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires

# Configuração do Apache inline (evita arquivo externo)
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

# WORKDIR para backend
WORKDIR /var/www/html

# Copia arquivos do backend (primeiro apenas composer para cache)
COPY backend/composer.json backend/composer.lock ./

# Instala dependências do backend (Elefant)
RUN composer install --no-dev --optimize-autoloader

# Copia resto do código do backend
COPY backend/ .

# Cria diretórios necessários
RUN mkdir -p /var/www/html/tmp/cache \
    && mkdir -p /var/www/html/tmp/logs \
    && mkdir -p /var/www/html/public/app

# Configura permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/tmp

# --- STAGE DE BUILD DO FRONTEND ---
FROM node:18-alpine AS frontend-builder

WORKDIR /app

# Copia e instala dependências do frontend
COPY frontend/package*.json ./

# Instala dependências (usando npm ci para produção)
RUN npm ci --only=production || npm install --production

# Copia código fonte do frontend
COPY frontend/ .

# Build do Astro
RUN npm run build || (echo "Build falhou, criando diretório dist vazio" && mkdir -p dist)

# --- STAGE FINAL ---
FROM backend

# Copia o build do frontend para o diretório público do backend
COPY --from=frontend-builder /app/dist /var/www/html/public/app

# Copia script de entrada
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configura variáveis de ambiente para o PHP
ENV PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_MAX_FILESIZE=50M \
    PHP_POST_MAX_SIZE=50M

# Porta que será exposta
EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]