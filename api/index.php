<?php

// ============================================
// MyTeamWork - RESTful API Entry Point
// ============================================

// Error reporting (desativar em produção)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Responde preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ============================================
// Roteamento Simples (Router)
// ============================================
$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Router básico
try {
    switch ($route) {
        case 'users':
            require_once __DIR__ . '/routes/api.php';
            break;
        case 'tasks':
            require_once __DIR__ . '/routes/api.php';
            break;
        case 'teams':
            require_once __DIR__ . '/routes/api.php';
            break;
        case 'auth':
            require_once __DIR__ . '/routes/api.php';
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Rota não encontrada',
                'available_routes' => [
                    '/api?route=auth/login',
                    '/api?route=auth/register',
                    '/api?route=users',
                    '/api?route=tasks',
                    '/api?route=teams'
                ]
            ]);
            http_response_code(404);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}