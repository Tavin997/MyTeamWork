-- Criação das tabelas (opcional - pode ser feito via migrações)
CREATE DATABASE IF NOT EXISTS myteamwork;
USE myteamwork;

-- Usuário já será criado pelo docker-compose
-- As tabelas serão criadas pelo Elefant ou migrações

-- Grants
GRANT ALL PRIVILEGES ON myteamwork.* TO 'myteamwork_user'@'%';
FLUSH PRIVILEGES;