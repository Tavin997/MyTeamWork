<?php

namespace MyTeamWork\Routes;

use MyTeamWork\Controllers\AuthController;
use MyTeamWork\Controllers\UserController;
use MyTeamWork\Controllers\TaskController;
use MyTeamWork\Controllers\TeamController;

/**
 * MyTeamWork - API Routes
 * Rotas RESTful com autenticação
 */

// ============================================
// ROTAS PÚBLICAS (Sem autenticação)
// ============================================

$router = [
    'auth' => [
        'POST' => [
            '/login' => [AuthController::class, 'login'],
            '/register' => [AuthController::class, 'register'],
        ],
    ],
];

// ============================================
// ROTAS PROTEGIDAS (Com autenticação)
// ============================================

$protectedRoutes = [
    'auth' => [
        'POST' => [
            '/logout' => [AuthController::class, 'logout'],
            '/refresh' => [AuthController::class, 'refresh'],
        ],
        'GET' => [
            '/me' => [AuthController::class, 'me'],
        ],
    ],
    'users' => [
        'GET' => [
            '/' => [UserController::class, 'index'],
            '/{id}' => [UserController::class, 'show'],
            '/{id}/tasks' => [UserController::class, 'getTasks'],
            '/{id}/teams' => [UserController::class, 'getTeams'],
            '/{id}/stats' => [UserController::class, 'getStats'],
        ],
        'POST' => [
            '/' => [UserController::class, 'store'],
        ],
        'PUT' => [
            '/{id}' => [UserController::class, 'update'],
        ],
        'DELETE' => [
            '/{id}' => [UserController::class, 'delete'],
        ],
    ],
    'tasks' => [
        'GET' => [
            '/' => [TaskController::class, 'index'],
            '/stats' => [TaskController::class, 'getStats'],
            '/{id}' => [TaskController::class, 'show'],
        ],
        'POST' => [
            '/' => [TaskController::class, 'store'],
        ],
        'PUT' => [
            '/{id}' => [TaskController::class, 'update'],
        ],
        'PATCH' => [
            '/{id}/status' => [TaskController::class, 'updateStatus'],
        ],
        'DELETE' => [
            '/{id}' => [TaskController::class, 'delete'],
        ],
    ],
    'teams' => [
        'GET' => [
            '/' => [TeamController::class, 'index'],
            '/{id}' => [TeamController::class, 'show'],
            '/{id}/tasks' => [TeamController::class, 'getTasks'],
        ],
        'POST' => [
            '/' => [TeamController::class, 'store'],
            '/{id}/members' => [TeamController::class, 'addMember'],
        ],
        'PUT' => [
            '/{id}' => [TeamController::class, 'update'],
        ],
        'DELETE' => [
            '/{id}' => [TeamController::class, 'delete'],
            '/{id}/members/{user_id}' => [TeamController::class, 'removeMember'],
        ],
    ],
];

// ============================================
// ROTEADOR
// ============================================

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['route'] ?? '';
$path = '/' . ltrim($path, '/');

// Busca parâmetros na URL (ex: /users/123)
$params = [];
$pathParts = explode('/', trim($path, '/'));
$routePattern = '';

foreach ($pathParts as $part) {
    if (is_numeric($part)) {
        $routePattern .= '/{id}';
        $params['id'] = (int) $part;
    } else {
        $routePattern .= "/$part";
    }
}

if (empty($routePattern)) {
    $routePattern = '/';
}

// Procura rota pública
$found = false;
foreach ($router as $routeGroup => $methods) {
    foreach ($methods as $methodType => $routes) {
        if ($method !== $methodType) continue;
        
        foreach ($routes as $route => $handler) {
            if ($route === $routePattern) {
                $found = true;
                try {
                    // Verifica autenticação se for rota protegida
                    if (in_array($routeGroup, array_keys($protectedRoutes))) {
                        $user = AuthController::authenticate();
                        if (!$user) {
                            http_response_code(401);
                            echo json_encode([
                                'success' => false,
                                'message' => 'Token inválido ou não fornecido'
                            ]);
                            exit;
                        }
                    }
                    
                    $controller = new $handler[0]();
                    $method = $handler[1];
                    
                    if (!empty($params)) {
                        $controller->$method(...array_values($params));
                    } else {
                        $controller->$method();
                    }
                } catch (\Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erro interno do servidor',
                        'error' => $e->getMessage()
                    ]);
                }
                break 3;
            }
        }
    }
}

// Tenta rota protegida
if (!$found) {
    foreach ($protectedRoutes as $routeGroup => $methods) {
        foreach ($methods as $methodType => $routes) {
            if ($method !== $methodType) continue;
            
            foreach ($routes as $route => $handler) {
                if ($route === $routePattern) {
                    $found = true;
                    
                    // Verifica autenticação
                    $user = AuthController::authenticate();
                    if (!$user) {
                        http_response_code(401);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Token inválido ou não fornecido'
                        ]);
                        exit;
                    }
                    
                    try {
                        $controller = new $handler[0]();
                        $method = $handler[1];
                        
                        if (!empty($params)) {
                            $controller->$method(...array_values($params));
                        } else {
                            $controller->$method();
                        }
                    } catch (\Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Erro interno do servidor',
                            'error' => $e->getMessage()
                        ]);
                    }
                    break 3;
                }
            }
        }
    }
}

// Rota não encontrada
if (!$found) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Rota não encontrada',
        'available_routes' => [
            'POST /auth/login',
            'POST /auth/register',
            'GET /auth/me',
            'GET /users',
            'GET /users/{id}',
            'POST /users',
            'PUT /users/{id}',
            'DELETE /users/{id}',
            'GET /tasks',
            'GET /tasks/{id}',
            'POST /tasks',
            'PUT /tasks/{id}',
            'DELETE /tasks/{id}',
            'GET /teams',
            'GET /teams/{id}',
            'POST /teams',
            'PUT /teams/{id}',
            'DELETE /teams/{id}',
            'POST /teams/{id}/members',
            'DELETE /teams/{id}/members/{user_id}'
        ]
    ]);
}