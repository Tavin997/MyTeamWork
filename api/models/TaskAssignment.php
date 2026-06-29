<?php

namespace MyTeamWork\Models;

class TaskAssignment extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'task_assignments';
        $this->primaryKey = 'id';
        $this->fillable = ['usuario_id', 'tarefa_id'];
        $this->casts = [
            'id' => 'int',
            'usuario_id' => 'int',
            'tarefa_id' => 'int',
            'criado' => 'datetime'
        ];
    }

    public function getTaskAssignments(int $taskId): array
    {
        $sql = "
            SELECT u.id, u.nome, u.email
            FROM {$this->table} ta
            JOIN users u ON u.id = ta.usuario_id
            WHERE ta.tarefa_id = :task_id
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserTasks(int $userId, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT t.* 
            FROM {$this->table} ta
            JOIN tasks t ON t.id = ta.tarefa_id
            WHERE ta.usuario_id = :user_id
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function removeTaskAssignments(int $taskId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE tarefa_id = :task_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':task_id' => $taskId]);
    }

    public function assignUsersToTask(int $taskId, array $userIds): bool
    {
        $this->beginTransaction();

        try {
            $this->removeTaskAssignments($taskId);

            $sql = "INSERT INTO {$this->table} (usuario_id, tarefa_id) VALUES (:user_id, :task_id)";
            $stmt = $this->db->getConnection()->prepare($sql);

            foreach ($userIds as $userId) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':task_id' => $taskId
                ]);
            }

            $this->commit();
            return true;

        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}