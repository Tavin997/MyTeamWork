#!/bin/bash

# Espera o banco de dados estar pronto
echo "Aguardando banco de dados..."
while ! nc -z ${DB_HOST} 3306; do
  sleep 1
done
echo "Banco de dados pronto!"

# Executa migrações se existirem
if [ -f "/var/www/html/scripts/migrate.php" ]; then
    php /var/www/html/scripts/migrate.php
fi

# Inicia o Apache
exec "$@"