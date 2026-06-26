// backend/apps/tasks/handlers/api.php
<?php

namespace apps\tasks\handlers;

class api {
    public function get($app) {
        $action = $_GET['action'] ?? 'list';
        $taskModel = new \apps\tasks\models\Task();
        
        switch($action) {
            case 'list':
                $tasks = $taskModel->getTasksByProject($_GET['project_id'] ?? 0);
                return $app->json($tasks);
                
            case 'create':
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $taskModel->create($data);
                return $app->json(['id' => $id, 'success' => true]);
                
            case 'update':
                $data = json_decode(file_get_contents('php://input'), true);
                $taskModel->update($data['id'], $data);
                return $app->json(['success' => true]);
                
            case 'delete':
                $taskModel->delete($_GET['id']);
                return $app->json(['success' => true]);
        }
    }
}