<?php

namespace MyTeamWork\Models;

class TeamMember extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'team_members';
        $this->primaryKey = 'id';
        $this->fillable = ['equipe_id', 'usuario_id', 'cargo_id'];
        $this->casts = [
            'id' => 'int',
            'equipe_id' => 'int',
            'usuario_id' => 'int',
            'cargo_id' => 'int',
            'criado' => 'datetime'
        ];
    }

    /**
     * Verifica se usuário é membro da equipe
     */
    public function isMember(int $teamId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE equipe_id = :team_id AND usuario_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $result['count'] > 0;
    }

    /**
     * Busca cargo do membro na equipe
     */
    public function getMemberRole(int $teamId, int $userId): ?array
    {
        $sql = "
            SELECT r.* 
            FROM {$this->table} tm
            JOIN roles r ON r.id = tm.cargo_id
            WHERE tm.equipe_id = :team_id AND tm.usuario_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca todas as equipes de um usuário
     */
    public function getUserTeams(int $userId): array
    {
        $sql = "
            SELECT t.*, tm.cargo_id, r.papel as cargo_nome
            FROM {$this->table} tm
            JOIN teams t ON t.id = tm.equipe_id
            JOIN roles r ON r.id = tm.cargo_id
            WHERE tm.usuario_id = :user_id
            AND t.excluido IS NULL
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}