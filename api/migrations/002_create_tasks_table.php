<?php

namespace MyTeamWork\Migrations;

class CreateTasksTable
{
    public function up(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                nome VARCHAR(200) NOT NULL,
                descricao TEXT,
                responsavel VARCHAR(100),
                prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
                dificuldade ENUM('facil', 'medio', 'dificil', 'muito_dificil') DEFAULT 'medio',
                estado ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',
                criado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modificado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_usuario (usuario_id),
                INDEX idx_estado (estado),
                INDEX idx_prioridade (prioridade),
                INDEX idx_responsavel (responsavel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    public function down(): string
    {
        return "DROP TABLE IF EXISTS tasks";
    }
}