<?php

namespace MyTeamWork\Models;

use MyTeamWork\Config\Database;
use PDO;
use PDOException;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initialize();
    }

    abstract protected function initialize(): void;

    public function all(int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $this->fetch($stmt);
    }

    public function where(array $conditions, int $page = 1, int $limit = 50): array
    {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClauses) . " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    public function create(array $data): int
    {
        $filteredData = $this->filterFillable($data);
        $columns = array_keys($filteredData);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->getConnection()->prepare($sql);

        foreach ($filteredData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        return (int) $this->db->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $filteredData = $this->filterFillable($data);
        $setClauses = [];

        foreach ($filteredData as $key => $value) {
            $setClauses[] = "$key = :$key";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->getConnection()->prepare($sql);

        foreach ($filteredData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'excluido'";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $sql = "UPDATE {$this->table} SET excluido = NOW() WHERE {$this->primaryKey} = :id";
        } else {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        }

        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->getConnection()->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    protected function filterFillable(array $data): array
    {
        return array_filter($data, fn($key) => in_array($key, $this->fillable), ARRAY_FILTER_USE_KEY);
    }

    protected function fetch(\PDOStatement $stmt): ?array
    {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->applyCasts($result) : null;
    }

    protected function fetchAll(\PDOStatement $stmt): array
    {
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'applyCasts'], $results);
    }

    protected function applyCasts(array $data): array
    {
        foreach ($this->casts as $field => $type) {
            if (isset($data[$field])) {
                switch ($type) {
                    case 'int':
                        $data[$field] = (int) $data[$field];
                        break;
                    case 'float':
                        $data[$field] = (float) $data[$field];
                        break;
                    case 'bool':
                        $data[$field] = (bool) $data[$field];
                        break;
                    case 'json':
                        $data[$field] = json_decode($data[$field], true);
                        break;
                    case 'datetime':
                        $data[$field] = new \DateTime($data[$field]);
                        break;
                }
            }
        }

        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollback();
    }
}