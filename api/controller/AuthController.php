<?php

namespace MyTeamWork\Controllers;

use MyTeamWork\Models\User;
use MyTeamWork\Models\Role;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends ApiController
{
    private User $userModel;
    private string $jwtSecret;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-me';
    }

    /**
     * POST /auth/login - Autentica usuário
     */
    public function login(): void
    {
        $data = $this->getRequestData();
        
        // Valida campos obrigatórios
        $required = ['email', 'senha'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        // Valida email
        if (!$this->validateEmail($data['email'])) {
            $this->error('Email inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        // Autentica
        $user = $this->userModel->authenticate($data['email'], $data['senha']);

        if (!$user) {
            $this->error('Credenciais inválidas', self::STATUS_UNAUTHORIZED);
            return;
        }

        // Verifica se usuário está ativo
        if ($user['estado'] !== 'ativo') {
            $this->error('Usuário inativo ou bloqueado', self::STATUS_FORBIDDEN);
            return;
        }

        // Gera JWT
        $token = $this->generateToken($user);

        $this->success([
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'estado' => $user['estado']
            ],
            'token' => $token,
            'expires_in' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
        ], 'Login realizado com sucesso');
    }

    /**
     * POST /auth/register - Registra novo usuário
     */
    public function register(): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        // Valida campos obrigatórios
        $required = ['nome', 'email', 'senha'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        // Valida email
        if (!$this->validateEmail($data['email'])) {
            $this->error('Email inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        // Verifica se email já existe
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser) {
            $this->error('Email já cadastrado', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
            // Define estado como ativo por padrão
            $data['estado'] = 'ativo';
            
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                $user = $this->userModel->find($userId);
                $token = $this->generateToken($user);

                $this->success([
                    'user' => [
                        'id' => $user['id'],
                        'nome' => $user['nome'],
                        'email' => $user['email'],
                        'estado' => $user['estado']
                    ],
                    'token' => $token,
                    'expires_in' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
                ], 'Usuário registrado com sucesso', self::STATUS_CREATED);
            } else {
                $this->error('Erro ao registrar usuário', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao registrar usuário', ['error' => $e->getMessage()]);
            $this->error('Erro interno ao registrar usuário', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * POST /auth/logout - Logout (invalida token)
     */
    public function logout(): void
    {
        // Em JWT stateless, o logout é feito pelo cliente descartando o token
        // Podemos implementar blacklist se necessário
        
        $this->success([], 'Logout realizado com sucesso');
    }

    /**
     * POST /auth/refresh - Renova token
     */
    public function refresh(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            $this->error('Token não fornecido', self::STATUS_UNAUTHORIZED);
            return;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Busca usuário atualizado
            $user = $this->userModel->find($decoded->user_id);
            
            if (!$user) {
                $this->error('Usuário não encontrado', self::STATUS_UNAUTHORIZED);
                return;
            }

            if ($user['estado'] !== 'ativo') {
                $this->error('Usuário inativo', self::STATUS_FORBIDDEN);
                return;
            }

            // Gera novo token
            $newToken = $this->generateToken($user);

            $this->success([
                'token' => $newToken,
                'expires_in' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
            ], 'Token renovado com sucesso');

        } catch (\Exception $e) {
            $this->error('Token inválido ou expirado', self::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * GET /auth/me - Dados do usuário logado
     */
    public function me(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            $this->error('Token não fornecido', self::STATUS_UNAUTHORIZED);
            return;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            $user = $this->userModel->find($decoded->user_id);
            
            if (!$user) {
                $this->error('Usuário não encontrado', self::STATUS_UNAUTHORIZED);
                return;
            }

            unset($user['senha']);

            $this->success(['user' => $user]);

        } catch (\Exception $e) {
            $this->error('Token inválido ou expirado', self::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * Gera token JWT
     */
    private function generateToken(array $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (int) ($_ENV['JWT_EXPIRY'] ?? 3600);

        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'nome' => $user['nome'],
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => $_ENV['APP_URL'] ?? 'myteamwork.com'
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Middleware - Verifica token
     */
    public static function authenticate(): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-me';

        try {
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}