<?php

namespace MyTeamWork\Migrations;

class CreateTeamMembersTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS team_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipe_id INT NOT NULL,
                usuario_id INT NOT NULL,
                cargo_id INT NOT NULL,
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (equipe_id) REFERENCES teams(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (cargo_id) REFERENCES roles(id) ON DELETE CASCADE,
                UNIQUE KEY unique_member (equipe_id, usuario_id),
                INDEX idx_equipe (equipe_id),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS team_members";
    }
}