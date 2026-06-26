#!/bin/bash
set -e

# Função para log com timestamp
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "🚀 Iniciando MyTeamWork..."

# Verifica variáveis de ambiente obrigatórias
if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    log "❌ ERRO: Variáveis de ambiente do banco de dados não configuradas!"
    log "   DB_HOST, DB_NAME, DB_USER, DB_PASS são obrigatórios"
    exit 1
fi

# Aguarda o banco de dados ficar disponível
log "⏳ Aguardando banco de dados em $DB_HOST:3306..."
for i in {1..30}; do
    if nc -z "$DB_HOST" 3306 2>/dev/null; then
        log "✅ Banco de dados disponível!"
        break
    fi
    log "   Tentativa $i/30 - Aguardando 2 segundos..."
    sleep 2
done

if ! nc -z "$DB_HOST" 3306 2>/dev/null; then
    log "⚠️  Banco de dados não respondeu em 60 segundos. Continuando mesmo assim..."
fi

# Cria arquivo de configuração do Elefant dinamicamente
log "📝 Configurando Elefant..."
cat > /var/www/html/conf/config.php <<EOF
<?php

return [
    'General' => [
        'timezone' => 'America/Sao_Paulo',
        'default_handler' => 'tasks/index',
        'locale' => 'pt_BR',
        'debug' => false,
    ],
    
    'Database' => [
        'driver' => 'mysql',
        'host' => '${DB_HOST}',
        'port' => 3306,
        'name' => '${DB_NAME}',
        'user' => '${DB_USER}',
        'pass' => '${DB_PASS}',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'mtw_',
    ],
    
    'Cache' => [
        'driver' => 'file',
        'expire' => 3600,
        'cache_dir' => '/var/www/html/tmp/cache',
    ],
    
    'Auth' => [
        'session_name' => 'mtw_session',
        'login_url' => '/auth/login',
        'logout_url' => '/auth/logout',
        'register_url' => '/auth/register',
        'password_reset_url' => '/auth/reset',
        'password_encryption' => 'bcrypt',
    ],
    
    'Mail' => [
        'smtp_host' => getenv('SMTP_HOST') ?: '',
        'smtp_port' => getenv('SMTP_PORT') ?: 587,
        'smtp_user' => getenv('SMTP_USER') ?: '',
        'smtp_pass' => getenv('SMTP_PASS') ?: '',
        'from_email' => getenv('MAIL_FROM') ?: 'noreply@myteamwork.com',
        'from_name' => 'MyTeamWork',
    ],
];
EOF

# Verifica se o frontend foi copiado
if [ -d "/var/www/html/public/app" ]; then
    log "✅ Frontend encontrado em /var/www/html/public/app"
else
    log "⚠️  Frontend não encontrado. Criando diretório..."
    mkdir -p /var/www/html/public/app
    echo '<!DOCTYPE html><html><head><title>MyTeamWork</title></head><body><h1>MyTeamWork - Em construção</h1></body></html>' > /var/www/html/public/app/index.html
fi

# Executa migrações se existirem
if [ -f "/var/www/html/scripts/migrate.php" ]; then
    log "🔄 Executando migrações do banco de dados..."
    php /var/www/html/scripts/migrate.php || log "⚠️  Erro nas migrações (pode ser normal na primeira execução)"
fi

# Ajusta permissões finais
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/tmp 2>/dev/null || true

log "✅ Configuração concluída! Iniciando Apache..."
log "🌐 Acesse: http://localhost:8080"

# Executa o comando passado (Apache)
exec "$@"