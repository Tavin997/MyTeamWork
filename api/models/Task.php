<?php

namespace MyTeamWork\Models;

class Task extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'tasks';
        $this->primaryKey = 'id';
        $this->fillable = [
            'usuario_id', 'nome', 'descricao', 'responsavel',
            'prioridade', 'dificuldade', 'estado'
        ];
        $this->casts = [
            'id' => 'int',
            'usuario_id' => 'int',
            'criado' => 'datetime',
            'modificado' => 'datetime'
        ];
    }

    /**
     * Busca tarefas por usuário
     */
    public function findByUser(int $userId, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE usuario_id = :user_id LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    /**
     * Busca tarefas por estado
     */
    public function findByStatus(string $status, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE estado = :status LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    /**
     * Busca tarefas por prioridade
     */
    public function findByPriority(string $priority, int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE prioridade = :priority LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':priority', $priority);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    /**
     * Atualiza o estado da tarefa
     */
    public function updateStatus(int $id, string $status): bool
    {
        $validStatuses = ['pendente', 'em_andamento', 'concluida', 'cancelada'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Status inválido: $status");
        }

        $sql = "UPDATE {$this->table} SET estado = :status WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * Busca tarefas com atribuições
     */
    public function getWithAssignments(int $taskId): ?array
    {
        $sql = "
            SELECT t.*, 
                   GROUP_CONCAT(DISTINCT u.id) as assigned_user_ids,
                   GROUP_CONCAT(DISTINCT u.nome) as assigned_user_names
            FROM {$this->table} t
            LEFT JOIN task_assignments ta ON ta.tarefa_id = t.id
            LEFT JOIN users u ON u.id = ta.usuario_id
            WHERE t.id = :id
            GROUP BY t.id
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':id' => $taskId]);
        return $this->fetch($stmt);
    }

    /**
     * Estatísticas de tarefas por usuário
     */
    public function getStats(int $userId): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN estado = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
                SUM(CASE WHEN estado = 'concluida' THEN 1 ELSE 0 END) as concluidas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                AVG(CASE WHEN dificuldade = 'facil' THEN 1 
                         WHEN dificuldade = 'medio' THEN 2 
                         WHEN dificuldade = 'dificil' THEN 3 
                         WHEN dificuldade = 'muito_dificil' THEN 4 END) as media_dificuldade
            FROM {$this->table}
            WHERE usuario_id = :user_id
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}