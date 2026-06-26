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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configura Apache
RUN a2enmod rewrite
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Instala Node.js para build do Astro
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# WORKDIR para backend
WORKDIR /var/www/html

# Copia e instala dependências do backend (Elefant)
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copia código do backend
COPY backend/ .

# Configura permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# --- STAGE DE BUILD DO FRONTEND ---
FROM node:18-alpine AS frontend-builder

WORKDIR /app

# Copia e instala dependências do frontend (Astro)
COPY frontend/package*.json ./
RUN npm ci --only=production

# Copia código fonte do frontend
COPY frontend/ .

# Build do Astro
RUN npm run build

# --- STAGE FINAL ---
FROM backend

# Copia o build do frontend para o diretório público do backend
COPY --from=frontend-builder /app/dist /var/www/html/public/app

# Script de entrada para iniciar a aplicação
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]