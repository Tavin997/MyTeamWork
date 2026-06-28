<?php

namespace MyTeamWork\Models;

use MyTeamWork\Utils\Password;

class User extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'users';
        $this->primaryKey = 'id';
        $this->fillable = ['nome', 'email', 'senha', 'estado'];
        $this->hidden = ['senha'];
        $this->casts = [
            'id' => 'int',
            'criado' => 'datetime',
            'modificado' => 'datetime'
        ];
    }

    /**
     * Busca usuário por email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $this->fetch($stmt);
    }

    /**
     * Cria usuário com senha hash
     */
    public function create(array $data): int
    {
        if (isset($data['senha'])) {
            $data['senha'] = Password::hash($data['senha']);
        }

        return parent::create($data);
    }

    /**
     * Atualiza usuário com senha hash se fornecida
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['senha'])) {
            $data['senha'] = Password::hash($data['senha']);
        }

        return parent::update($id, $data);
    }

    /**
     * Verifica credenciais
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user && Password::verify($password, $user['senha'])) {
            return $user;
        }

        return null;
    }

    /**
     * Busca tarefas atribuídas ao usuário
     */
    public function getTasks(int $userId): array
    {
        $sql = "
            SELECT t.* 
            FROM tasks t
            JOIN task_assignments ta ON ta.tarefa_id = t.id
            WHERE ta.usuario_id = :user_id
            ORDER BY t.criado DESC
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca equipes do usuário
     */
    public function getTeams(int $userId): array
    {
        $sql = "
            SELECT t.*, tm.cargo_id
            FROM teams t
            JOIN team_members tm ON tm.equipe_id = t.id
            WHERE tm.usuario_id = :user_id
            AND t.excluido IS NULL
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}