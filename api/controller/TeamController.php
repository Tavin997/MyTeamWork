<?php

namespace MyTeamWork\Controller;

use MyTeamWork\Models\Team;
use MyTeamWork\Models\TeamMember;
use MyTeamWork\Models\Role;

class TeamController extends ApiController
{
    private Team $teamModel;
    private TeamMember $memberModel;
    private Role $roleModel;

    public function __construct()
    {
        $this->teamModel = new Team();
        $this->memberModel = new TeamMember();
        $this->roleModel = new Role();
    }

    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 50);
        $activeOnly = isset($_GET['active']) && $_GET['active'] === 'true';

        if ($activeOnly) {
            $teams = $this->teamModel->findActive($page, $limit);
        } else {
            $teams = $this->teamModel->all($page, $limit);
        }

        $total = $this->teamModel->count();

        $this->success([
            'teams' => $teams,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function show(int $id): void
    {
        $team = $this->teamModel->find($id);

        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        $members = $this->teamModel->getMembers($id);
        $team['members'] = $members;

        $this->success(['team' => $team]);
    }

    public function store(): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        $required = ['nome'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        try {
            $teamId = $this->teamModel->create($data);
            
            if ($teamId) {
                if (isset($data['admin_user_id'])) {
                    $adminRole = $this->roleModel->findByName('admin');
                    if ($adminRole) {
                        $this->teamModel->addMember($teamId, $data['admin_user_id'], $adminRole['id']);
                    }
                }

                $team = $this->teamModel->find($teamId);
                $this->success(['team' => $team], 'Equipe criada com sucesso', self::STATUS_CREATED);
            } else {
                $this->error('Erro ao criar equipe', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao criar equipe', ['error' => $e->getMessage()]);
            $this->error('Erro interno ao criar equipe', self::STATUS_SERVER_ERROR);
        }
    }

    public function update(int $id): void
    {
        $data = $this->getRequestData();
        $data = $this->sanitizeInput($data);

        $team = $this->teamModel->find($id);
        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        try {
            $updated = $this->teamModel->update($id, $data);
            
            if ($updated) {
                $team = $this->teamModel->find($id);
                $this->success(['team' => $team], 'Equipe atualizada com sucesso');
            } else {
                $this->error('Erro ao atualizar equipe', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao atualizar equipe', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao atualizar equipe', self::STATUS_SERVER_ERROR);
        }
    }

    public function delete(int $id): void
    {
        $team = $this->teamModel->find($id);
        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        try {
            $deleted = $this->teamModel->delete($id);
            
            if ($deleted) {
                $this->success([], 'Equipe removida com sucesso');
            } else {
                $this->error('Erro ao remover equipe', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao remover equipe', ['id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao remover equipe', self::STATUS_SERVER_ERROR);
        }
    }

    public function addMember(int $id): void
    {
        $data = $this->getRequestData();
        
        $required = ['usuario_id', 'cargo_id'];
        $errors = $this->validateRequired($data, $required);

        if ($errors) {
            $this->error('Dados inválidos', self::STATUS_BAD_REQUEST, $errors);
            return;
        }

        $team = $this->teamModel->find($id);
        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        if ($this->memberModel->isMember($id, $data['usuario_id'])) {
            $this->error('Usuário já é membro desta equipe', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
            $added = $this->teamModel->addMember($id, $data['usuario_id'], $data['cargo_id']);
            
            if ($added) {
                $this->success([], 'Membro adicionado com sucesso');
            } else {
                $this->error('Erro ao adicionar membro', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao adicionar membro', ['team_id' => $id, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao adicionar membro', self::STATUS_SERVER_ERROR);
        }
    }

    public function removeMember(int $teamId, int $userId): void
    {
        $team = $this->teamModel->find($teamId);
        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        if (!$this->memberModel->isMember($teamId, $userId)) {
            $this->error('Usuário não é membro desta equipe', self::STATUS_BAD_REQUEST);
            return;
        }

        try {
            $removed = $this->teamModel->removeMember($teamId, $userId);
            
            if ($removed) {
                $this->success([], 'Membro removido com sucesso');
            } else {
                $this->error('Erro ao remover membro', self::STATUS_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao remover membro', ['team_id' => $teamId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            $this->error('Erro interno ao remover membro', self::STATUS_SERVER_ERROR);
        }
    }

    public function getTasks(int $id): void
    {
        $team = $this->teamModel->find($id);
        if (!$team) {
            $this->error('Equipe não encontrada', self::STATUS_NOT_FOUND);
            return;
        }

        $tasks = $this->teamModel->getTeamTasks($id);
        $this->success(['tasks' => $tasks]);
    }
}