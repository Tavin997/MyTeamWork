<?php

namespace MyTeamWork\Controller;

use MyTeamWork\Models\User;
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

    public function login(): void
    {
        $data = $this->getRequestData();
        
        $required = ['email', 'senha'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        if (!$this->validateEmail($data['email'])) {
            $this->error('Email inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        $user = $this->userModel->authenticate($data['email'], $data['senha']);

        if (!$user) {
            $this->error('Credenciais inválidas', self::STATUS_UNAUTHORIZED);
            return;
        }

        if ($user['estado'] !== 'ativo') {
            $this->error('Usuário inativo ou bloqueado', self::STATUS_FORBIDDEN);
            return;
        }

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

    public function register(): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        $required = ['nome', 'email', 'senha'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        if (!$this->validateEmail($data['email'])) {
            $this->error('Email inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser) {
            $this->error('Email já cadastrado', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
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

    public function logout(): void
    {
        $this->success([], 'Logout realizado com sucesso');
    }

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
            $user = $this->userModel->find($decoded->user_id);
            
            if (!$user || $user['estado'] !== 'ativo') {
                $this->error('Usuário não encontrado ou inativo', self::STATUS_UNAUTHORIZED);
                return;
            }

            $newToken = $this->generateToken($user);

            $this->success([
                'token' => $newToken,
                'expires_in' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
            ], 'Token renovado com sucesso');

        } catch (\Exception $e) {
            $this->error('Token inválido ou expirado', self::STATUS_UNAUTHORIZED);
        }
    }

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