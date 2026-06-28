<?php

namespace MyTeamWork\Controllers;

use MyTeamWork\Models\User;

class UserController extends ApiController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * GET /users - Lista todos os usuários
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 50);

        $users = $this->userModel->all($page, $limit);
        $total = $this->userModel->count();

        $this->success([
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * GET /users/{id} - Busca usuário por ID
     */
    public function show(int $id): void
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        $this->success(['user' => $user]);
    }

    /**
     * POST /users - Cria um novo usuário
     */
    public function store(): void
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
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                $user = $this->userModel->find($userId);
                $this->success(['user' => $user], 'Usuário criado com sucesso', self::STATUS_CREATED);
            } else {
                $this->error('Erro ao criar usuário', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao criar usuário', ['error' => $e->getMessage()]);
            $this->error('Erro interno ao criar usuário', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * PUT /users/{id} - Atualiza um usuário
     */
    public function update(int $id): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        // Verifica se usuário existe
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        // Valida email se for alterado
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (!$this->validateEmail($data['email'])) {
                $this->error('Email inválido', self::STATUS_BAD_REQUEST);
                return;
            }

            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] !== $id) {
                $this->error('Email já cadastrado', self::STATUS_BAD_REQUEST);
                return;
            }
        }

        try {
            $updated = $this->userModel->update($id, $data);
            
            if ($updated) {
                $user = $this->userModel->find($id);
                $this->success(['user' => $user], 'Usuário atualizado com sucesso');
            } else {
                $this->error('Erro ao atualizar usuário', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao atualizar usuário', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao atualizar usuário', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * DELETE /users/{id} - Remove um usuário
     */
    public function delete(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        try {
            $deleted = $this->userModel->delete($id);
            
            if ($deleted) {
                $this->success([], 'Usuário removido com sucesso');
            } else {
                $this->error('Erro ao remover usuário', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao remover usuário', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao remover usuário', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * GET /users/{id}/tasks - Busca tarefas do usuário
     */
    public function getTasks(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 50);

        $tasks = $this->userModel->getTasks($id);
        
        $this->success([
            'tasks' => array_slice($tasks, ($page - 1) * $limit, $limit),
            'total' => count($tasks),
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * GET /users/{id}/teams - Busca equipes do usuário
     */
    public function getTeams(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        $teams = $this->userModel->getTeams($id);
        
        $this->success(['teams' => $teams]);
    }

    /**
     * GET /users/{id}/stats - Estatísticas do usuário
     */
    public function getStats(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->error('Usuário não encontrado', self::STATUS_NOT_FOUND);
            return;
        }

        $taskModel = new \MyTeamWork\Models\Task();
        $stats = $taskModel->getStats($id);
        
        $this->success(['stats' => $stats]);
    }
}