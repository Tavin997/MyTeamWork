<?php

namespace MyTeamWork\Migrations;

use MyTeamWork\Config\Database;
use PDOException;

class MigrationRunner
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        try {
            $this->createMigrationsTable();
            $executedMigrations = $this->getExecutedMigrations();

            $migrations = [
                '001_create_users_table.php' => 'CreateUsersTable',
                '002_create_tasks_table.php' => 'CreateTasksTable',
                '003_create_teams_table.php' => 'CreateTeamsTable',
                '005_create_roles_table.php' => 'CreateRolesTable',
                '004_create_team_members_table.php' => 'CreateTeamMembersTable',
                '006_create_task_assignments_table.php' => 'CreateTaskAssignmentsTable',
            ];

            foreach ($migrations as $file => $className) {
                if (!in_array($file, $executedMigrations)) {
                    $this->executeMigration($file, $className);
                    $this->markMigrationAsExecuted($file);
                }
            }

            echo "✅ All migrations executed successfully!\n";

        } catch (PDOException $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $this->db->getConnection()->exec($sql);
    }

    private function getExecutedMigrations(): array
    {
        $sql = "SELECT migration FROM migrations ORDER BY id ASC";
        $stmt = $this->db->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function executeMigration(string $file, string $className): void
    {
        $fullClassName = "MyTeamWork\\Migrations\\$className";
        $migration = new $fullClassName();
        $sql = $migration->up();

        $this->db->getConnection()->exec($sql);
        echo "✅ Executed migration: $file\n";
    }

    private function markMigrationAsExecuted(string $file): void
    {
        $sql = "INSERT INTO migrations (migration) VALUES (:migration)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':migration' => $file]);
    }

    public function rollback(): void
    {
        try {
            $sql = "SELECT migration FROM migrations ORDER BY id DESC LIMIT 1";
            $stmt = $this->db->getConnection()->query($sql);
            $lastMigration = $stmt->fetchColumn();

            if ($lastMigration) {
                $className = $this->getClassNameFromFile($lastMigration);
                $fullClassName = "MyTeamWork\\Migrations\\$className";
                $migration = new $fullClassName();
                $sql = $migration->down();

                $this->db->getConnection()->exec($sql);

                $deleteSql = "DELETE FROM migrations WHERE migration = :migration";
                $deleteStmt = $this->db->getConnection()->prepare($deleteSql);
                $deleteStmt->execute([':migration' => $lastMigration]);

                echo "✅ Rollback executed successfully: $lastMigration\n";
            } else {
                echo "ℹ️ No migrations to rollback.\n";
            }
        } catch (PDOException $e) {
            echo "❌ Rollback failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function getClassNameFromFile(string $file): string
    {
        $parts = explode('_', $file);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return str_replace('.php', '', $className);
    }
}

// Execução
$runner = new MigrationRunner();

if (isset($argv[1]) && $argv[1] === '--rollback') {
    $runner->rollback();
} else {
    $runner->run();
}