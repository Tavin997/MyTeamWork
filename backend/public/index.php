<?php
/**
 * MyTeamWork - Ponto de entrada principal
 * Elefant Framework
 */

// Define o caminho base
define('BASE_PATH', dirname(__DIR__));

// Carrega o autoloader do Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Carrega a configuração
$config = require_once BASE_PATH . '/conf/config.php';

// Inicializa o Elefant
$app = new \Elefant\App($config);

// Rotas da API
$app->route('/api/tasks', 'apps\tasks\handlers\api->get');
$app->route('/api/tasks/create', 'apps\tasks\handlers\api->post');
$app->route('/api/tasks/update', 'apps\tasks\handlers\api->put');
$app->route('/api/tasks/delete', 'apps\tasks\handlers\api->delete');

// Rotas públicas
$app->route('/health', 'apps\health\handlers\index->get');
$app->route('/auth/login', 'apps\auth\handlers\login->get');
$app->route('/auth/login', 'apps\auth\handlers\login->post');
$app->route('/auth/logout', 'apps\auth\handlers\logout->get');

// Rota padrão (frontend SPA)
$app->route('/', function($app) {
    // Se o frontend existir, serve o index.html
    if (file_exists(BASE_PATH . '/public/app/index.html')) {
        readfile(BASE_PATH . '/public/app/index.html');
        return;
    }
    
    // Fallback
    echo "MyTeamWork - Em construção";
});

// Executa a aplicação
$app->run();