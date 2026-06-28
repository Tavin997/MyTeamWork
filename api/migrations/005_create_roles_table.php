<?php

namespace MyTeamWork\Migrations;

class CreateRolesTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                papel VARCHAR(50) NOT NULL UNIQUE,
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_papel (papel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS roles";
    }
}