<?php

namespace MyTeamWork\Migrations;

class CreateTeamsTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS teams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                membros INT DEFAULT 0,
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modificado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                excluido TIMESTAMP NULL DEFAULT NULL,
                INDEX idx_nome (nome),
                INDEX idx_excluido (excluido)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS teams";
    }
}