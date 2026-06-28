<?php

namespace MyTeamWork\Controllers;

use MyTeamWork\Models\Task;
use MyTeamWork\Models\TaskAssignment;

class TaskController extends ApiController
{
    private Task $taskModel;
    private TaskAssignment $assignmentModel;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->assignmentModel = new TaskAssignment();
    }

    /**
     * GET /tasks - Lista todas as tarefas
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 50);
        $status = $_GET['status'] ?? null;
        $userId = (int) ($_GET['user_id'] ?? 0);

        if ($userId > 0) {
            $tasks = $this->taskModel->findByUser($userId, $page, $limit);
        } elseif ($status) {
            $tasks = $this->taskModel->findByStatus($status, $page, $limit);
        } else {
            $tasks = $this->taskModel->all($page, $limit);
        }

        $total = $this->taskModel->count();

        $this->success([
            'tasks' => $tasks,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * GET /tasks/{id} - Busca tarefa por ID
     */
    public function show(int $id): void
    {
        $task = $this->taskModel->getWithAssignments($id);

        if (!$task) {
            $this->error('Tarefa não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        $this->success(['task' => $task]);
    }

    /**
     * POST /tasks - Cria uma nova tarefa
     */
    public function store(): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        // Valida campos obrigatórios
        $required = ['usuario_id', 'nome'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        // Valida prioridade
        $validPriorities = ['baixa', 'media', 'alta', 'urgente'];
        if (isset($data['prioridade']) && !in_array($data['prioridade'], $validPriorities)) {
            $this->error('Prioridade inválida', self::STATUS_BAD_REQUEST);
            return;
        }

        // Valida estado
        $validStatuses = ['pendente', 'em_andamento', 'concluida', 'cancelada'];
        if (isset($data['estado']) && !in_array($data['estado'], $validStatuses)) {
            $this->error('Estado inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
            $taskId = $this->taskModel->create($data);
            
            if ($taskId) {
                // Se houver usuários atribuídos
                if (isset($data['assigned_users']) && is_array($data['assigned_users'])) {
                    $this->assignmentModel->assignUsersToTask($taskId, $data['assigned_users']);
                }

                $task = $this->taskModel->getWithAssignments($taskId);
                $this->success(['task' => $task], 'Tarefa criada com sucesso', self::STATUS_CREATED);
            } else {
                $this->error('Erro ao criar tarefa', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao criar tarefa', ['error' => $e->getMessage()]);
            $this->error('Erro interno ao criar tarefa', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * PUT /tasks/{id} - Atualiza uma tarefa
     */
    public function update(int $id): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        $task = $this->taskModel->find($id);
        if (!$task) {
            $this->error('Tarefa não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        // Valida prioridade
        $validPriorities = ['baixa', 'media', 'alta', 'urgente'];
        if (isset($data['prioridade']) && !in_array($data['prioridade'], $validPriorities)) {
            $this->error('Prioridade inválida', self::STATUS_BAD_REQUEST);
            return;
        }

        // Valida estado
        $validStatuses = ['pendente', 'em_andamento', 'concluida', 'cancelada'];
        if (isset($data['estado']) && !in_array($data['estado'], $validStatuses)) {
            $this->error('Estado inválido', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
            $updated = $this->taskModel->update($id, $data);
            
            if ($updated) {
                // Atualiza atribuições se fornecidas
                if (isset($data['assigned_users']) && is_array($data['assigned_users'])) {
                    $this->assignmentModel->assignUsersToTask($id, $data['assigned_users']);
                }

                $task = $this->taskModel->getWithAssignments($id);
                $this->success(['task' => $task], 'Tarefa atualizada com sucesso');
            } else {
                $this->error('Erro ao atualizar tarefa', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao atualizar tarefa', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao atualizar tarefa', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * DELETE /tasks/{id} - Remove uma tarefa
     */
    public function delete(int $id): void
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            $this->error('Tarefa não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        try {
            // Remove atribuições primeiro
            $this->assignmentModel->removeTaskAssignments($id);
            
            $deleted = $this->taskModel->delete($id);
            
            if ($deleted) {
                $this->success([], 'Tarefa removida com sucesso');
            } else {
                $this->error('Erro ao remover tarefa', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao remover tarefa', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao remover tarefa', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * PATCH /tasks/{id}/status - Atualiza apenas o status
     */
    public function updateStatus(int $id): void
    {
        $data = $this->getRequestData();
        
        if (!isset($data['estado'])) {
            $this->error('Campo estado é obrigatório', self::STATUS_BAD_REQUEST);
            return;
        }

        $task = $this->taskModel->find($id);
        if (!$task) {
            $this->error('Tarefa não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        try {
            $updated = $this->taskModel->updateStatus($id, $data['estado']);
            
            if ($updated) {
                $task = $this->taskModel->find($id);
                $this->success(['task' => $task], 'Status atualizado com sucesso');
            } else {
                $this->error('Erro ao atualizar status', self::STATUS_SERVER_ERROR);
            }
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage(), self::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logError('Erro ao atualizar status', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao atualizar status', self::STATUS_SERVER_ERROR);
        }
    }

    /**
     * GET /tasks/stats - Estatísticas gerais
     */
    public function getStats(): void
    {
        $userId = (int) ($_GET['user_id'] ?? 0);
        $stats = [];

        if ($userId > 0) {
            $stats = $this->taskModel->getStats($userId);
        } else {
            // Estatísticas gerais
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN estado = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
                    SUM(CASE WHEN estado = 'concluida' THEN 1 ELSE 0 END) as concluidas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
                FROM tasks
            ";
            
            $db = $this->taskModel->db->getConnection();
            $stmt = $db->query($sql);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $this->success(['stats' => $stats]);
    }
}