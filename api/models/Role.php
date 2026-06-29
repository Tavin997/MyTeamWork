<?php

namespace MyTeamWork\Models;

class Role extends BaseModel
{
    protected function initialize(): void
    {
        $this->table = 'roles';
        $this->primaryKey = 'id';
        $this->fillable = ['papel'];
        $this->casts = [
            'id' => 'int',
            'criado' => 'datetime'
        ];
    }

    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE papel = :papel LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':papel' => $name]);
        return $this->fetch($stmt);
    }

    public function getDefaultRoles(): array
    {
        return [
            'admin',
            'gerente',
            'desenvolvedor',
            'designer',
            'analista',
            'convidado'
        ];
    }

    public function seedDefaultRoles(): void
    {
        $defaultRoles = $this->getDefaultRoles();
        
        foreach ($defaultRoles as $role) {
            $existing = $this->findByName($role);
            if (!$existing) {
                $this->create(['papel' => $role]);
            }
        }
    }
}