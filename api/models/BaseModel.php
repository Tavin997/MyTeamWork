<?php

namespace MyTeamWork\Models;

use MyTeamWork\Config\Database;
use PDO;
use PDOException;

/**
 * BaseModel - Abstract class com métodos CRUD genéricos
 */
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

    /**
     * Método a ser sobrescrito pelas classes filhas para definir tabela e campos
     */
    abstract protected function initialize(): void;

    /**
     * Busca todos os registros com paginação
     */
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

    /**
     * Busca um registro por ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $this->fetch($stmt);
    }

    /**
     * Busca registros com condições
     */
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

    /**
     * Cria um novo registro
     */
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

    /**
     * Atualiza um registro
     */
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

    /**
     * Delete (soft delete se a coluna 'excluido' existir)
     */
    public function delete(int $id): bool
    {
        // Verifica se a tabela tem soft delete
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'excluido'";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Soft delete
            $sql = "UPDATE {$this->table} SET excluido = NOW() WHERE {$this->primaryKey} = :id";
        } else {
            // Hard delete
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        }

        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Conta o total de registros
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->getConnection()->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Filtra apenas campos fillable
     */
    protected function filterFillable(array $data): array
    {
        return array_filter($data, fn($key) => in_array($key, $this->fillable), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Fetch single result com casts
     */
    protected function fetch(\PDOStatement $stmt): ?array
    {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->applyCasts($result) : null;
    }

    /**
     * Fetch all results com casts
     */
    protected function fetchAll(\PDOStatement $stmt): array
    {
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'applyCasts'], $results);
    }

    /**
     * Aplica casts aos campos
     */
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

        // Remove campos hidden
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit da transação
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback da transação
     */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }
}