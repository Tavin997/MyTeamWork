FROM php:8.2-apache

# Atualiza e instala dependências
# RUN apt-get update && apt-get install -y \
#     libssl-dev \
#     ca-certificates \
#     && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP
RUN docker-php-ext-install pdo_mysql mysqli

# Habilita mod_rewrite
RUN a2enmod rewrite

# Configura o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos
COPY . .

# Configura permissões
RUN chown -R www-data:www-data /var/www/html

# Ativa o SSL no PHP para conexão com Aiven
#RUN echo "extension=openssl" > /usr/local/etc/php/conf.d/openssl.ini

# Porta para o Render
EXPOSE 10000

# Comando para iniciar o servidor
CMD ["apache2-foreground"]