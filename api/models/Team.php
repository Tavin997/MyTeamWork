<?php

namespace MyTeamWork\Models;

class Team extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'teams';
        $this->primaryKey = 'id';
        $this->fillable = ['nome', 'descricao', 'membros'];
        $this->casts = [
            'id' => 'int',
            'membros' => 'int',
            'criado' => 'datetime',
            'modificado' => 'datetime',
            'excluido' => 'datetime'
        ];
    }

    /**
     * Busca equipes ativas (não excluídas)
     */
    public function findActive(int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE excluido IS NULL LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    /**
     * Busca membros da equipe
     */
    public function getMembers(int $teamId): array
    {
        $sql = "
            SELECT u.id, u.nome, u.email, r.papel as cargo
            FROM team_members tm
            JOIN users u ON u.id = tm.usuario_id
            JOIN roles r ON r.id = tm.cargo_id
            WHERE tm.equipe_id = :team_id
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':team_id' => $teamId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona membro à equipe
     */
    public function addMember(int $teamId, int $userId, int $roleId): bool
    {
        $sql = "INSERT INTO team_members (equipe_id, usuario_id, cargo_id) VALUES (:team_id, :user_id, :role_id)";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        $result = $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);

        if ($result) {
            // Atualiza contador de membros
            $this->updateMemberCount($teamId);
        }

        return $result;
    }

    /**
     * Remove membro da equipe
     */
    public function removeMember(int $teamId, int $userId): bool
    {
        $sql = "DELETE FROM team_members WHERE equipe_id = :team_id AND usuario_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        $result = $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId
        ]);

        if ($result) {
            $this->updateMemberCount($teamId);
        }

        return $result;
    }

    /**
     * Atualiza contador de membros
     */
    private function updateMemberCount(int $teamId): void
    {
        $sql = "UPDATE {$this->table} SET membros = (SELECT COUNT(*) FROM team_members WHERE equipe_id = :team_id) WHERE id = :team_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':team_id' => $teamId]);
    }

    /**
     * Busca tarefas da equipe
     */
    public function getTeamTasks(int $teamId): array
    {
        $sql = "
            SELECT t.*, u.nome as criador_nome
            FROM tasks t
            JOIN users u ON u.id = t.usuario_id
            JOIN team_members tm ON tm.usuario_id = u.id
            WHERE tm.equipe_id = :team_id
            ORDER BY t.criado DESC
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':team_id' => $teamId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}