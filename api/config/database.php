<?php

namespace MyTeamWork\Config;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;

    private function __construct()
    {
        $this->config = $this->loadConfig();
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): array
    {
        // Carregar .env manualmente se necessário
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'name' => $_ENV['DB_NAME'] ?? 'defaultdb',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASS'] ?? '',
            'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
            'ssl_ca' => $_ENV['DB_SSL_CA'] ?? null,
            'ssl_cert' => $_ENV['DB_SSL_CERT'] ?? null,
            'ssl_key' => $_ENV['DB_SSL_KEY'] ?? null,
        ];
    }

    private function connect(): void
    {
        try {
            $dsn = $this->buildDsn();
            $options = $this->buildOptions();

            $this->connection = new PDO($dsn, $this->config['user'], $this->config['pass'], $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Verifica conexão SSL
            $stmt = $this->connection->query("SHOW STATUS LIKE 'Ssl_cipher'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['Value']) {
                error_log("✅ SSL Connection established with cipher: " . $result['Value']);
            } else {
                error_log("⚠️ SSL Connection not active");
            }

            $this->connection->exec("SET NAMES utf8mb4");
            $this->connection->exec("SET time_zone = '+00:00'");

        } catch (PDOException $e) {
            error_log("❌ Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    private function buildDsn(): string
    {
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['name']};charset=utf8mb4";

        // Configuração SSL para AIVEN
        if ($this->config['ssl_ca'] && file_exists($this->config['ssl_ca'])) {
            $dsn .= ";sslmode=verify-ca";
            $dsn .= ";sslca={$this->config['ssl_ca']}";
            
            if ($this->config['ssl_cert'] && file_exists($this->config['ssl_cert'])) {
                $dsn .= ";sslcert={$this->config['ssl_cert']}";
            }
            
            if ($this->config['ssl_key'] && file_exists($this->config['ssl_key'])) {
                $dsn .= ";sslkey={$this->config['ssl_key']}";
            }
        } else {
            // Fallback para SSL mode required sem verificação de certificado
            // Usado quando os certificados não estão disponíveis
            $dsn .= ";sslmode=required";
            error_log("⚠️ SSL CA certificate not found, using sslmode=required");
        }

        return $dsn;
    }

    private function buildOptions(): array
    {
        $options = [
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 10, // 10 segundos de timeout
        ];

        if ($this->config['driver'] === 'mysql') {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        return $options;
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    private function __clone() {}
    public function __wakeup() {}
}