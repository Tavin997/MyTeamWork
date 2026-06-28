<?php

namespace MyTeamWork\Migrations;

class CreateTaskAssignmentsTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS task_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                tarefa_id INT NOT NULL,
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (tarefa_id) REFERENCES tasks(id) ON DELETE CASCADE,
                UNIQUE KEY unique_assignment (usuario_id, tarefa_id),
                INDEX idx_usuario (usuario_id),
                INDEX idx_tarefa (tarefa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS task_assignments";
    }
}