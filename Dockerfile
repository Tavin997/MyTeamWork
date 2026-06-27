# ============================================
# MyTeamWork - Dockerfile Production Ready
# Versão: 1.0.0
# Base: PHP 8.2 FPM com extensões otimizadas
# ============================================

FROM php:8.2-fpm

# ============================================
# 1. METADADOS DA IMAGEM
# ============================================
LABEL maintainer="MyTeamWork Team <dev@myteamwork.com>"
LABEL version="1.0.0"
LABEL description="MyTeamWork PHP Backend Environment"

# ============================================
# 2. INSTALAÇÃO DE DEPENDÊNCIAS DO SISTEMA
# ============================================
RUN apt-get update && apt-get install -y \
    # Utilitários essenciais
    git \
    curl \
    wget \
    unzip \
    zip \
    # Extensões PHP necessárias
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    # Drivers de banco de dados
    libpq-dev \
    # Ferramentas de cache
    redis-tools \
    # Limpeza para reduzir tamanho da imagem
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ============================================
# 3. INSTALAÇÃO DE EXTENSÕES PHP
# ============================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    mbstring \
    xml \
    zip \
    opcache \
    bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# ============================================
# 4. CONFIGURAÇÃO DO PHP (php.ini)
# ============================================
RUN echo "upload_max_filesize = 32M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 32M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini \
    && echo "display_errors = Off" > /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/errors.ini

# ============================================
# 5. CONFIGURAÇÃO DE SEGURANÇA (disable_functions)
# ============================================
RUN echo "disable_functions = exec,passthru,shell_exec,system,proc_open,curl_exec,curl_multi_exec,parse_ini_file,show_source,pcntl_exec" > /usr/local/etc/php/conf.d/security.ini

# ============================================
# 6. INSTALAÇÃO DO COMPOSER
# ============================================
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================
# 7. CONFIGURAÇÃO DO OPcache (Performance)
# ============================================
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# ============================================
# 8. CONFIGURAÇÃO DO USER E PERMISSÕES
# ============================================
# Criar usuário não-root para segurança
RUN groupadd -g 1000 -r myteamwork \
    && useradd -r -g myteamwork -u 1000 -m myteamwork

# ============================================
# 9. WORKDIR E COPIA DOS ARQUIVOS
# ============================================
WORKDIR /var/www/html

# Copiar arquivos da aplicação
COPY --chown=myteamwork:myteamwork . .

# Criar diretórios de log com permissões
RUN mkdir -p /var/log/php \
    && touch /var/log/php_errors.log \
    && chown -R myteamwork:myteamwork /var/log/php \
    && chmod -R 755 /var/log/php

# ============================================
# 10. INSTALAÇÃO DAS DEPENDÊNCIAS (Composer)
# ============================================
# Separar para aproveitar cache do Docker
# COPY composer.json composer.lock ./
# RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copiar o resto dos arquivos
COPY . .

# ============================================
# 11. HEALTHCHECK
# ============================================
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# ============================================
# 12. EXPOSE E COMANDO
# ============================================
EXPOSE 9000

# Comando para iniciar o PHP-FPM
CMD ["php-fpm"]

# ============================================
# OTIMIZAÇÃO FINAL
# ============================================
# Reduzir tamanho da imagem
RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*