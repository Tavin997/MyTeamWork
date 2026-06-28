<?php

namespace MyTeamWork\Migrations;

class CreateUsersTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                estado ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modificado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS users";
    }
}